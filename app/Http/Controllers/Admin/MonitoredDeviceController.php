<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceHeartbeat;
use App\Models\Product;
use Illuminate\Http\Request;

class MonitoredDeviceController extends Controller
{
    /**
     * Display a list of all monitored devices
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $product = $request->input('product');
        $search = $request->input('search');

        $query = DeviceHeartbeat::with('product')
            ->orderBy('last_heartbeat_at', 'desc');

        if ($status === 'active') {
            $query->monitored()->notSnoozed();
        } elseif ($status === 'snoozed') {
            $query->monitored()->snoozed();
        } elseif ($status === 'archived') {
            $query->where('is_monitoring', false);
        }

        if ($product) {
            $query->where('app_identifier', 'like', $product . '%');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('device_id', 'like', "%{$search}%")
                    ->orWhere('device_name', 'like', "%{$search}%")
                    ->orWhere('device_model', 'like', "%{$search}%");
            });
        }

        $devices = $query->paginate(25)->withQueryString();

        // Get products for filter dropdown
        $products = Product::orderBy('name')->get();

        // Stats
        $stats = [
            'total' => DeviceHeartbeat::count(),
            'active' => DeviceHeartbeat::monitored()->notSnoozed()->count(),
            'snoozed' => DeviceHeartbeat::monitored()->snoozed()->count(),
            'archived' => DeviceHeartbeat::where('is_monitoring', false)->count(),
        ];

        return view('admin.devices.index', [
            'devices' => $devices,
            'products' => $products,
            'status' => $status,
            'product' => $product,
            'search' => $search,
            'stats' => $stats,
        ]);
    }

    /**
     * Archive a device (stop monitoring)
     */
    public function archive(DeviceHeartbeat $device)
    {
        $device->update(['is_monitoring' => false]);

        return redirect()->back()
            ->with('success', "Device '{$device->display_name}' has been archived and will no longer trigger heartbeat alerts.");
    }

    /**
     * Reactivate a device (resume monitoring)
     */
    public function activate(DeviceHeartbeat $device)
    {
        $device->update(['is_monitoring' => true]);

        return redirect()->back()
            ->with('success', "Device '{$device->display_name}' has been reactivated and will now be monitored for heartbeats.");
    }

    /**
     * Update device details (name)
     */
    public function update(Request $request, DeviceHeartbeat $device)
    {
        $validated = $request->validate([
            'device_name' => 'nullable|string|max:255',
        ]);

        $device->update($validated);

        return redirect()->back()
            ->with('success', 'Device updated successfully.');
    }

    /**
     * Delete a device permanently
     */
    public function destroy(DeviceHeartbeat $device)
    {
        $name = $device->display_name;
        $device->delete();

        return redirect()->route('admin.devices.index')
            ->with('success', "Device '{$name}' has been deleted.");
    }

    /**
     * Archive multiple devices at once
     */
    public function bulkArchive(Request $request)
    {
        $validated = $request->validate([
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:device_heartbeats,id',
        ]);

        DeviceHeartbeat::whereIn('id', $validated['device_ids'])
            ->update(['is_monitoring' => false]);

        $count = count($validated['device_ids']);

        return redirect()->back()
            ->with('success', "{$count} device(s) have been archived.");
    }

    /**
     * Snooze a device for a specified duration
     */
    public function snooze(Request $request, DeviceHeartbeat $device)
    {
        $validated = $request->validate([
            'hours' => 'required|integer|min:1|max:168', // max 7 days
        ]);

        $device->snoozeForHours($validated['hours']);

        $until = $device->fresh()->snoozed_until->setTimezone(config('app.timezone'));

        return redirect()->back()
            ->with('success', "Device '{$device->display_name}' snoozed until {$until->format('M j, g:i A')}.");
    }

    /**
     * Cancel snooze for a device
     */
    public function cancelSnooze(DeviceHeartbeat $device)
    {
        $device->cancelSnooze();

        return redirect()->back()
            ->with('success', "Snooze cancelled for '{$device->display_name}'. Monitoring resumed.");
    }
}
