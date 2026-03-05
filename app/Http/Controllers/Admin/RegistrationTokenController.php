<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\RegistrationToken;
use App\Models\DeviceAuditLog;
use Illuminate\Http\Request;

class RegistrationTokenController extends Controller
{
    /**
     * Display list of all registration tokens
     */
    public function index(Request $request)
    {
        $appFilter = $request->input('app');
        $statusFilter = $request->input('status');

        $query = RegistrationToken::with(['creator', 'deviceApiKeys'])
            ->orderByDesc('created_at');

        if ($appFilter) {
            $query->where('app_identifier', $appFilter);
        }

        if ($statusFilter) {
            switch ($statusFilter) {
                case 'active':
                    $query->active();
                    break;
                case 'expired':
                    $query->where('expires_at', '<=', now());
                    break;
                case 'revoked':
                    $query->where('is_revoked', true);
                    break;
                case 'exhausted':
                    $query->whereColumn('uses_count', '>=', 'max_uses')
                        ->whereNotNull('max_uses');
                    break;
            }
        }

        $tokens = $query->paginate(25);

        // Get unique app identifiers for filter dropdown
        $apps = App::select('identifier', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.registration-tokens.index', [
            'tokens' => $tokens,
            'apps' => $apps,
            'appFilter' => $appFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Show create token form
     */
    public function create()
    {
        $apps = App::select('identifier', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.registration-tokens.create', [
            'apps' => $apps,
        ]);
    }

    /**
     * Store new registration token
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'app_identifier' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'expires_in_hours' => 'required|integer|min:1|max:720', // Max 30 days
            'max_uses' => 'nullable|integer|min:1|max:1000',
        ]);

        $result = RegistrationToken::generate(
            $validated['app_identifier'],
            auth()->id(),
            $validated['expires_in_hours'],
            $validated['max_uses'] ?? null,
            $validated['name'] ?? null
        );

        // Log token creation
        DeviceAuditLog::logTokenCreated(
            $result['model'],
            auth()->id(),
            request()->ip(),
            request()->userAgent()
        );

        return redirect()->route('admin.registration-tokens.show', $result['model'])
            ->with('token', $result['token'])
            ->with('success', 'Registration token created. Copy it now - it will not be shown again!');
    }

    /**
     * Show token details (right after creation to show full token)
     */
    public function show(RegistrationToken $registrationToken)
    {
        $registrationToken->load(['creator', 'deviceApiKeys.auditLogs']);

        return view('admin.registration-tokens.show', [
            'token' => $registrationToken,
            'plainToken' => session('token'),
        ]);
    }

    /**
     * Revoke a token
     */
    public function destroy(RegistrationToken $registrationToken)
    {
        if ($registrationToken->is_revoked) {
            return back()->with('error', 'Token is already revoked.');
        }

        $registrationToken->revoke();

        // Log revocation
        DeviceAuditLog::logTokenRevoked(
            $registrationToken,
            auth()->id(),
            request()->ip()
        );

        return redirect()->route('admin.registration-tokens.index')
            ->with('success', "Token revoked successfully.");
    }

    /**
     * View audit logs for a token
     */
    public function auditLogs(RegistrationToken $registrationToken)
    {
        $logs = DeviceAuditLog::where('registration_token_id', $registrationToken->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.registration-tokens.audit-logs', [
            'token' => $registrationToken,
            'logs' => $logs,
        ]);
    }
}
