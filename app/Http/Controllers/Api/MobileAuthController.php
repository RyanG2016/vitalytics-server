<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    /**
     * Login and issue a Sanctum token for mobile apps.
     *
     * POST /api/v1/auth/login
     *
     * Request body:
     * {
     *   "email": "user@example.com",
     *   "password": "password",
     *   "device_name": "iPhone 15 Pro" (optional, defaults to "Mobile App")
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "token": "1|abc123...",
     *   "user": { ... }
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        // Rate limiting: 5 attempts per email+IP combination
        $throttleKey = Str::transliterate(Str::lower($request->email) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                'code' => 'RATE_LIMITED',
                'retry_after' => $seconds,
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey);

            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
                'code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($throttleKey);

        // Create Sanctum token
        $deviceName = $request->input('device_name', 'Mobile App');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Logout and revoke the current token.
     *
     * POST /api/v1/auth/logout
     *
     * Requires: Authorization: Bearer {token}
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Logout from all devices by revoking all tokens.
     *
     * POST /api/v1/auth/logout-all
     *
     * Requires: Authorization: Bearer {token}
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Successfully logged out from all devices"
     * }
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // Revoke all tokens for the user
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out from all devices.',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     *
     * GET /api/v1/auth/user
     *
     * Requires: Authorization: Bearer {token}
     *
     * Response:
     * {
     *   "success": true,
     *   "user": { ... }
     * }
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $this->formatUser($request->user()),
        ]);
    }

    /**
     * Send a password reset link to the user's email.
     *
     * POST /api/v1/auth/forgot-password
     *
     * Request body:
     * {
     *   "email": "user@example.com"
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Password reset link sent to your email."
     * }
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Rate limiting: 3 password reset requests per email per 5 minutes
        $throttleKey = 'password_reset:' . Str::lower($request->email);

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'success' => false,
                'message' => "Too many password reset requests. Please try again in {$seconds} seconds.",
                'code' => 'RATE_LIMITED',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($throttleKey, 300); // 5 minute decay

        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Always return success to prevent email enumeration attacks
        // The actual status is logged server-side
        if ($status !== Password::RESET_LINK_SENT) {
            \Log::info('Password reset requested for non-existent email: ' . $request->email);
        }

        return response()->json([
            'success' => true,
            'message' => 'If an account exists with that email, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset the user's password using a token.
     *
     * POST /api/v1/auth/reset-password
     *
     * Request body:
     * {
     *   "email": "user@example.com",
     *   "token": "reset-token-from-email",
     *   "password": "newpassword",
     *   "password_confirmation": "newpassword"
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Your password has been reset."
     * }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all existing tokens for security
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => $this->getPasswordResetErrorMessage($status),
                'code' => 'PASSWORD_RESET_FAILED',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your password has been reset. Please login with your new password.',
        ]);
    }

    /**
     * Refresh the current token (issue a new token and revoke the old one).
     *
     * POST /api/v1/auth/refresh
     *
     * Requires: Authorization: Bearer {token}
     *
     * Response:
     * {
     *   "success": true,
     *   "token": "2|xyz789..."
     * }
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $tokenName = $currentToken->name ?? 'Mobile App';

        // Delete the current token
        $currentToken->delete();

        // Create a new token
        $newToken = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $newToken,
        ]);
    }

    /**
     * Format user data for API response.
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'is_admin' => $user->isAdmin(),
            'has_health_access' => $user->canAccessHealth(),
            'has_analytics_access' => $user->canAccessAnalytics(),
            'accessible_products' => $user->accessibleProducts(),
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }

    /**
     * Get a user-friendly error message for password reset failures.
     */
    private function getPasswordResetErrorMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'We could not find a user with that email address.',
            Password::INVALID_TOKEN => 'This password reset token is invalid or has expired.',
            Password::RESET_THROTTLED => 'Please wait before retrying.',
            default => 'Unable to reset password. Please try again.',
        };
    }

    /**
     * Create a short-lived, one-time-use token for WebView authentication.
     *
     * POST /api/v1/auth/web-session-token
     *
     * Requires: Authorization: Bearer {token}
     *
     * Request body (optional):
     * {
     *   "redirect": "/analytics"  // Optional path to redirect to after login
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "token": "ws_abc123...",
     *   "url": "https://your-vitalytics-server.com/auth/token-login?token=ws_abc123...",
     *   "expires_in": 60
     * }
     *
     * Usage:
     * 1. Call this endpoint with Bearer token to get a web session token
     * 2. Open the returned URL in WebView
     * 3. Server validates token, creates session cookie, redirects to dashboard
     */
    public function createWebSessionToken(Request $request): JsonResponse
    {
        $request->validate([
            'redirect' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $redirect = $request->input('redirect', '/');

        // Validate redirect path (must be relative, no external URLs)
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/' . $redirect;
        }

        // Generate a secure one-time token
        $token = 'ws_' . Str::random(64);
        $cacheKey = 'web_session_token:' . hash('sha256', $token);

        // Store token data in cache (60 second expiry)
        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'redirect' => $redirect,
            'created_at' => now()->toIso8601String(),
        ], 60);

        $url = url('/auth/token-login') . '?' . http_build_query(['token' => $token]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'url' => $url,
            'expires_in' => 60,
        ]);
    }

    /**
     * Exchange a web session token for a session cookie and redirect.
     *
     * GET /auth/token-login?token=ws_abc123...
     *
     * This is a WEB route (not API) that:
     * 1. Validates the one-time token
     * 2. Logs the user into the web session
     * 3. Redirects to the dashboard (or specified path)
     *
     * The token is invalidated after use (one-time only).
     */
    public function exchangeWebSessionToken(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        if (empty($token)) {
            return redirect()->route('login')
                ->withErrors(['token' => 'Missing authentication token.']);
        }

        // Validate token format
        if (!str_starts_with($token, 'ws_')) {
            return redirect()->route('login')
                ->withErrors(['token' => 'Invalid token format.']);
        }

        $cacheKey = 'web_session_token:' . hash('sha256', $token);
        $tokenData = Cache::pull($cacheKey); // pull = get and delete (one-time use)

        if (!$tokenData) {
            return redirect()->route('login')
                ->withErrors(['token' => 'Invalid or expired token. Please try again.']);
        }

        $user = User::find($tokenData['user_id']);

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['token' => 'User not found.']);
        }

        // Log the user into the web session
        Auth::login($user);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // Redirect to the specified path or dashboard
        $redirect = $tokenData['redirect'] ?? '/';

        return redirect($redirect);
    }
}
