<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAlertSetting;
use App\Models\AlertSubscriber;
use App\Models\AlertHistory;
use App\Models\User;
use App\Models\DeviceHeartbeat;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Display alert settings for all products
     */
    public function index()
    {
        $products = Product::with(['alertSettings', 'alertSettings.subscribers'])
            ->active()
            ->get()
            ->map(function ($product) {
                $settings = ProductAlertSetting::forProduct($product->id);
                $subscribers = AlertSubscriber::where('product_id', $product->id)
                    ->with('user')
                    ->get();
                $activeAlerts = AlertHistory::where('product_id', $product->id)
                    ->active()
                    ->count();

                return [
                    'product' => $product,
                    'settings' => $settings,
                    'subscribers' => $subscribers,
                    'activeAlerts' => $activeAlerts,
            'deviceHeartbeats' => DeviceHeartbeat::where('product_id', $product->id)->orderBy('last_heartbeat_at', 'desc')->get(),
                ];
            });

        return view('admin.alerts.index', [
            'products' => $products,
        ]);
    }

    /**
     * Show alert settings for a specific product
     */
    public function edit(Product $product)
    {
        $settings = ProductAlertSetting::forProduct($product->id);
        $subscribers = AlertSubscriber::where('product_id', $product->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        $users = User::orderBy('name')->get();
        $activeAlerts = AlertHistory::where('product_id', $product->id)
            ->active()
            ->orderBy('last_occurrence_at', 'desc')
            ->get();

        return view('admin.alerts.edit', [
            'product' => $product,
            'settings' => $settings,
            'subscribers' => $subscribers,
            'users' => $users,
            'activeAlerts' => $activeAlerts,
            'deviceHeartbeats' => DeviceHeartbeat::where('product_id', $product->id)->orderBy('last_heartbeat_at', 'desc')->get(),
        ]);
    }

    /**
     * Update alert settings for a product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'teams_webhook_url' => 'nullable|url|max:1000',
            'teams_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'heartbeat_enabled' => 'boolean',
            'heartbeat_timeout_minutes' => 'nullable|integer|min:5|max:1440',
            'ai_analysis_enabled' => 'boolean',
            'ai_analysis_hour' => 'nullable|integer|min:0|max:23',
            'alert_on_test_data' => 'boolean',
            'critical_cooldown_minutes' => 'required|integer|min:5|max:1440',
            'critical_reminder_hours' => 'required|integer|min:1|max:24',
            'noncritical_threshold' => 'required|integer|min:1|max:100',
            'noncritical_window_minutes' => 'required|integer|min:5|max:1440',
            'noncritical_cooldown_hours' => 'required|integer|min:1|max:48',
            'business_hours_only' => 'boolean',
            'business_hours_start' => 'nullable|date_format:H:i',
            'business_hours_end' => 'nullable|date_format:H:i',
            'exclude_weekends' => 'boolean',
            'timezone' => 'required|string|max:50',
        ]);

        $settings = ProductAlertSetting::forProduct($product->id);
        $settings->update($validated);

        return redirect()->route('admin.alerts.edit', $product)
            ->with('success', 'Alert settings updated successfully.');
    }

    /**
     * Add a subscriber to a product
     */
    public function addSubscriber(Request $request, Product $product)
    {
        $validated = $request->validate([
            'subscriber_type' => 'required|in:user,external',
            'user_id' => 'required_if:subscriber_type,user|nullable|exists:users,id',
            'email' => 'required_if:subscriber_type,external|nullable|email|max:255',
            'name' => 'nullable|string|max:255',
            'receive_critical' => 'boolean',
            'receive_noncritical' => 'boolean',
        ]);

        // Check for duplicate
        $query = AlertSubscriber::where('product_id', $product->id);
        if ($validated['subscriber_type'] === 'user') {
            $exists = $query->where('user_id', $validated['user_id'])->exists();
        } else {
            $exists = $query->where('email', $validated['email'])->exists();
        }

        if ($exists) {
            return redirect()->route('admin.alerts.edit', $product)
                ->with('error', 'This subscriber already exists for this product.');
        }

        AlertSubscriber::create([
            'product_id' => $product->id,
            'user_id' => $validated['subscriber_type'] === 'user' ? $validated['user_id'] : null,
            'email' => $validated['subscriber_type'] === 'external' ? $validated['email'] : null,
            'name' => $validated['name'],
            'receive_critical' => $validated['receive_critical'] ?? true,
            'receive_noncritical' => $validated['receive_noncritical'] ?? false,
            'is_enabled' => true,
        ]);

        return redirect()->route('admin.alerts.edit', $product)
            ->with('success', 'Subscriber added successfully.');
    }

    /**
     * Update a subscriber
     */
    public function updateSubscriber(Request $request, AlertSubscriber $subscriber)
    {
        $validated = $request->validate([
            'receive_critical' => 'boolean',
            'receive_noncritical' => 'boolean',
            'is_enabled' => 'boolean',
        ]);

        $subscriber->update($validated);

        return redirect()->route('admin.alerts.edit', $subscriber->product_id)
            ->with('success', 'Subscriber updated successfully.');
    }

    /**
     * Remove a subscriber
     */
    public function removeSubscriber(AlertSubscriber $subscriber)
    {
        $productId = $subscriber->product_id;
        $subscriber->delete();

        return redirect()->route('admin.alerts.edit', $productId)
            ->with('success', 'Subscriber removed successfully.');
    }

    /**
     * Clear an alert (mark as resolved)
     */
    public function clearAlert(AlertHistory $alert)
    {
        $productId = $alert->product_id;
        $alert->clear();

        return redirect()->route('admin.alerts.edit', $productId)
            ->with('success', 'Alert cleared successfully.');
    }

    /**
     * Clear all alerts for a product
     */
    public function clearAllAlerts(Product $product)
    {
        AlertHistory::where('product_id', $product->id)
            ->active()
            ->update(['cleared_at' => now()]);

        return redirect()->route('admin.alerts.edit', $product)
            ->with('success', 'All alerts cleared successfully.');
    }

    /**
     * Test webhook by sending a test alert
     */
    public function testWebhook(Request $request, Product $product)
    {
        $settings = ProductAlertSetting::forProduct($product->id);

        if (!$settings->teams_webhook_url) {
            return redirect()->route('admin.alerts.edit', $product)
                ->with('error', 'No Teams webhook URL configured.');
        }

        $payload = [
            'type' => 'message',
            'attachments' => [[
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'content' => [
                    'type' => 'AdaptiveCard',
                    'version' => '1.4',
                    '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                    'body' => [
                        [
                            'type' => 'Container',
                            'style' => 'good',
                            'items' => [[
                                'type' => 'TextBlock',
                                'text' => '✅ Test Alert - Connection Successful',
                                'weight' => 'bolder',
                                'size' => 'large',
                            ]],
                        ],
                        [
                            'type' => 'FactSet',
                            'facts' => [
                                ['title' => 'Product', 'value' => $product->name],
                                ['title' => 'Message', 'value' => 'This is a test alert from Vitalytics'],
                                ['title' => 'Time', 'value' => now()->setTimezone($settings->timezone)->format('M j, Y g:i A T')],
                            ],
                        ],
                    ],
                ],
            ]],
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->post($settings->teams_webhook_url, $payload);

            if ($response->successful()) {
                return redirect()->route('admin.alerts.edit', $product)
                    ->with('success', 'Test alert sent successfully! Check your Teams channel.');
            } else {
                return redirect()->route('admin.alerts.edit', $product)
                    ->with('error', 'Webhook returned error: ' . $response->status());
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.alerts.edit', $product)
                ->with('error', 'Failed to send test: ' . $e->getMessage());
        }
    }
}
