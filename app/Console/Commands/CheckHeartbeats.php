<?php

namespace App\Console\Commands;

use App\Models\DeviceHeartbeat;
use App\Models\ProductAlertSetting;
use App\Services\AlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckHeartbeats extends Command
{
    protected $signature = 'heartbeats:check';
    protected $description = 'Check for missing heartbeats and send alerts';

    public function handle(AlertService $alertService): int
    {
        $this->info('Checking for missing heartbeats...');

        // Get all products with heartbeat monitoring enabled
        $settings = ProductAlertSetting::with('product')
            ->where('heartbeat_enabled', true)
            ->get();

        $alertCount = 0;

        foreach ($settings as $setting) {
            // Skip if outside business hours
            if (!$setting->isWithinBusinessHours()) {
                $this->line("Skipping {$setting->product->name}: outside business hours");
                continue;
            }

            $timeoutMinutes = $setting->heartbeat_timeout_minutes;

            // Find devices that have missed their heartbeat and haven't been alerted recently
            $missedDevices = DeviceHeartbeat::where('product_id', $setting->product_id)
                ->monitored()
                ->notSnoozed()
                ->missedHeartbeat($timeoutMinutes)
                ->notRecentlyAlerted($setting->critical_cooldown_minutes)
                ->with('product')
                ->get();

            foreach ($missedDevices as $device) {
                $this->warn("Device {$device->device_id} ({$device->app_identifier}) has missed heartbeat");

                // Send alert via AlertService
                $alertService->sendHeartbeatAlert($device, $setting);
                $device->markAlerted();
                $alertCount++;
            }
        }

        $this->info("Completed. Sent {$alertCount} heartbeat alerts.");

        return Command::SUCCESS;
    }
}
