<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSecret;
use App\Models\Product;
use App\Models\App;
use Illuminate\Http\Request;

class SecretController extends Controller
{
    /**
     * Display the secrets management page
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get products from database with their apps
        $products = Product::with(['apps' => function($query) {
            $query->orderBy('platform');
        }])
        ->active()
        ->orderBy('name')
        ->get();

        // Get all secrets grouped by app identifier
        $allSecrets = AppSecret::orderBy('app_identifier')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('app_identifier');

        // Build the data structure for the view
        $productSecrets = [];

        foreach ($products as $product) {
            // Check user access (admins see all, others see their accessible products)
            if (!$user->isAdmin()) {
                $accessibleProducts = $user->accessibleProducts();
                if (!in_array($product->slug, $accessibleProducts)) {
                    continue;
                }
            }

            $productSecrets[$product->slug] = [
                'name' => $product->name,
                'color' => $product->color ?? '#666',
                'icon' => $product->icon ?? 'fa-cube',
                'subProducts' => [],
            ];

            foreach ($product->apps as $app) {
                $secrets = $allSecrets->get($app->identifier, collect());

                $productSecrets[$product->slug]['subProducts'][$app->identifier] = [
                    'name' => $app->name,
                    'icon' => $app->platform_icon,
                    'secrets' => $secrets->map(function ($secret) {
                        return [
                            'id' => $secret->id,
                            'label' => $secret->label,
                            'created_at' => $secret->created_at->format('M d, Y g:i A'),
                            'expires_at' => $secret->expires_at?->format('M d, Y g:i A'),
                            'is_active' => $secret->isActive(),
                            'is_expiring_soon' => $secret->isExpiringSoon(),
                            'days_until_expiry' => $secret->expires_at ? (int) now()->diffInDays($secret->expires_at, false) : null,
                        ];
                    }),
                    'has_active_secret' => $secrets->filter->isActive()->isNotEmpty(),
                ];
            }
        }

        return view('admin.secrets.index', [
            'productSecrets' => $productSecrets,
        ]);
    }

    /**
     * Generate a new secret for an app
     */
    public function generate(Request $request, string $appIdentifier)
    {
        // Verify user has access to this app's product
        if (!$this->userCanAccessApp($appIdentifier)) {
            abort(403);
        }

        $gracePeriod = $request->input('grace_period', 30);

        // Check if there are existing active secrets
        $existingSecrets = AppSecret::findActiveForApp($appIdentifier);

        if ($existingSecrets->isEmpty()) {
            // First secret - just generate
            $result = AppSecret::generateForApp($appIdentifier);
        } else {
            // Rotate - set expiry on existing
            $result = AppSecret::rotateForApp($appIdentifier, $gracePeriod);
        }

        return redirect()->back()
            ->with('success', 'New secret generated successfully!')
            ->with('new_secret', $result['plainSecret'])
            ->with('new_secret_app', $appIdentifier);
    }

    /**
     * Extend expiry for a secret
     */
    public function extend(Request $request, AppSecret $secret)
    {
        // Verify user has access
        if (!$this->userCanAccessApp($secret->app_identifier)) {
            abort(403);
        }

        $days = $request->input('days', 30);
        $secret->extendExpiry($days);

        return redirect()->back()
            ->with('success', "Secret expiry extended by {$days} days.");
    }

    /**
     * Revoke a secret immediately
     */
    public function revoke(AppSecret $secret)
    {
        // Verify user has access
        if (!$this->userCanAccessApp($secret->app_identifier)) {
            abort(403);
        }

        $secret->revoke();

        return redirect()->back()
            ->with('success', 'Secret revoked successfully.');
    }

    /**
     * Check if current user can access the product that owns this app
     */
    private function userCanAccessApp(string $appIdentifier): bool
    {
        $user = auth()->user();
        
        // Admins can access all
        if ($user->isAdmin()) {
            return App::where('identifier', $appIdentifier)->exists();
        }

        // Find the app and its product
        $app = App::where('identifier', $appIdentifier)
            ->with('product')
            ->first();

        if (!$app) {
            return false;
        }

        $accessibleProducts = $user->accessibleProducts();
        return in_array($app->product->slug, $accessibleProducts);
    }
}
