<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceNotification;
use App\Models\Product;
use Illuminate\Http\Request;

class MaintenanceNotificationController extends Controller
{
    /**
     * Display list of all maintenance notifications
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        $query = MaintenanceNotification::with(['products', 'creator'])
            ->orderByDesc('created_at');

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'expired') {
            $query->expired();
        }

        $notifications = $query->paginate(20);

        return view('admin.maintenance.index', [
            'notifications' => $notifications,
            'status' => $status,
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $products = Product::active()->ordered()->get();

        return view('admin.maintenance.create', [
            'products' => $products,
            'severities' => ['info', 'warning', 'critical'],
        ]);
    }

    /**
     * Store new notification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'products' => 'required|array|min:1',
            'products.*' => 'exists:products,id',
            'severity' => 'required|in:info,warning,critical',
            'dismissible' => 'boolean',
            'is_active' => 'boolean',
            'is_test' => 'boolean',
        ]);

        $notification = MaintenanceNotification::create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'severity' => $validated['severity'],
            'dismissible' => $request->boolean('dismissible', true),
            'is_active' => $request->boolean('is_active', true),
            'is_test' => $request->boolean('is_test', false),
            'created_by' => auth()->id(),
        ]);

        $notification->products()->sync($validated['products']);

        return redirect()
            ->route('admin.maintenance.index')
            ->with('success', 'Maintenance notification created successfully.');
    }

    /**
     * Show edit form
     */
    public function edit(MaintenanceNotification $maintenance)
    {
        $products = Product::active()->ordered()->get();

        return view('admin.maintenance.edit', [
            'notification' => $maintenance,
            'products' => $products,
            'severities' => ['info', 'warning', 'critical'],
            'selectedProducts' => $maintenance->products->pluck('id')->toArray(),
        ]);
    }

    /**
     * Update notification
     */
    public function update(Request $request, MaintenanceNotification $maintenance)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'products' => 'required|array|min:1',
            'products.*' => 'exists:products,id',
            'severity' => 'required|in:info,warning,critical',
            'dismissible' => 'boolean',
            'is_active' => 'boolean',
            'is_test' => 'boolean',
        ]);

        $maintenance->update([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'severity' => $validated['severity'],
            'dismissible' => $request->boolean('dismissible', true),
            'is_active' => $request->boolean('is_active', true),
            'is_test' => $request->boolean('is_test', false),
        ]);

        $maintenance->products()->sync($validated['products']);

        return redirect()
            ->route('admin.maintenance.edit', $maintenance)
            ->with('success', 'Maintenance notification updated.');
    }

    /**
     * Delete notification
     */
    public function destroy(MaintenanceNotification $maintenance)
    {
        $maintenance->delete();

        return redirect()
            ->route('admin.maintenance.index')
            ->with('success', 'Maintenance notification deleted.');
    }

    /**
     * Toggle active status
     */
    public function toggle(MaintenanceNotification $maintenance)
    {
        $maintenance->update(['is_active' => !$maintenance->is_active]);

        $status = $maintenance->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Notification {$status}.");
    }
}
