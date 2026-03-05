<?php

namespace App\Jobs;

use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckHealthAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $appIdentifier,
        private array $events
    ) {}

    public function handle(AlertService $alertService): void
    {
        foreach ($this->events as $event) {
            try {
                $alertService->processEvent($event, $this->appIdentifier);
            } catch (\Exception $e) {
                Log::error('CheckHealthAlerts: Failed to process event', [
                    'app' => $this->appIdentifier,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
