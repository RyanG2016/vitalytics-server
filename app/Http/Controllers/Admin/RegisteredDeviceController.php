<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\DeviceApiKey;
use App\Models\DeviceAuditLog;
use Illuminate\Http\Request;

class RegisteredDeviceController extends Controller
{
    /**
     * Display list of all registered devices
     */
    public function index(Request $request)
    {
        $appFilter = $request->input('app');
        $statusFilter = $request->input('status');
        $search = $request->input('search');

        $query = DeviceApiKey::with(['registrationToken.creator'])
            ->orderByDesc('created_at');

        if ($appFilter) {
            $query->where('app_identifier', $appFilter);
        }

        if ($statusFilter) {
            if ($statusFilter === 'active') {
                $query->active();
            } elseif ($statusFilter === 'revoked') {
                $query->revoked();
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('device_id', 'like', "%{$search}%")
                    ->orWhere('device_name', 'like', "%{$search}%")
                    ->orWhere('device_hostname', 'like', "%{$search}%");
            });
        }

        $devices = $query->paginate(25);

        // Get unique app identifiers for filter dropdown
        $apps = App::select('identifier', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.registered-devices.index', [
            'devices' => $devices,
            'apps' => $apps,
            'appFilter' => $appFilter,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    /**
     * Show device details
     */
    public function show(DeviceApiKey $registeredDevice)
    {
        $registeredDevice->load(['registrationToken.creator', 'revokedByUser']);

        // Get recent audit logs for this device
        $logs = DeviceAuditLog::where('device_api_key_id', $registeredDevice->id)
            ->orWhere('device_id', $registeredDevice->device_id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin.registered-devices.show', [
            'device' => $registeredDevice,
            'logs' => $logs,
        ]);
    }

    /**
     * Revoke device API key
     */
    public function revoke(Request $request, DeviceApiKey $registeredDevice)
    {
        if ($registeredDevice->is_revoked) {
            return back()->with('error', 'Device is already revoked.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $registeredDevice->revoke(auth()->id(), $validated['reason'] ?? null);

        // Log revocation
        DeviceAuditLog::logKeyRevoked(
            $registeredDevice,
            auth()->id(),
            $validated['reason'] ?? null,
            request()->ip()
        );

        return redirect()->route('admin.registered-devices.index')
            ->with('success', "Device '{$registeredDevice->display_name}' API key revoked.");
    }

    /**
     * Regenerate API key for a device
     */
    public function regenerateKey(DeviceApiKey $registeredDevice)
    {
        // Revoke old key
        $oldKeyId = $registeredDevice->id;

        $registeredDevice->revoke(auth()->id(), 'Key regenerated');

        // Generate new key
        $result = DeviceApiKey::generate(
            $registeredDevice->app_identifier,
            $registeredDevice->device_id,
            $registeredDevice->registration_token_id,
            request()->ip(),
            $registeredDevice->device_name,
            $registeredDevice->device_hostname,
            $registeredDevice->device_os
        );

        // Log regeneration
        DeviceAuditLog::create([
            'event_type' => DeviceAuditLog::EVENT_KEY_REGENERATED,
            'app_identifier' => $registeredDevice->app_identifier,
            'device_id' => $registeredDevice->device_id,
            'device_api_key_id' => $result['model']->id,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'details' => [
                'old_key_id' => $oldKeyId,
            ],
        ]);

        return redirect()->route('admin.registered-devices.show', $result['model'])
            ->with('newApiKey', $result['key'])
            ->with('success', 'New API key generated. Copy it now - it will not be shown again!');
    }

    /**
     * Delete device record entirely
     */
    public function destroy(DeviceApiKey $registeredDevice)
    {
        $displayName = $registeredDevice->display_name;

        // Log before deletion
        DeviceAuditLog::create([
            'event_type' => 'device_deleted',
            'app_identifier' => $registeredDevice->app_identifier,
            'device_id' => $registeredDevice->device_id,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'details' => [
                'device_name' => $registeredDevice->device_name,
                'device_hostname' => $registeredDevice->device_hostname,
            ],
        ]);

        $registeredDevice->delete();

        return redirect()->route('admin.registered-devices.index')
            ->with('success', "Device '{$displayName}' deleted. It will need to re-register to access configs.");
    }
}
