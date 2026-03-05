# Vitalytics Laravel Integration Guide

## Overview

Vitalytics is a real-time application health monitoring service. This guide covers integrating Vitalytics into a Laravel application to track errors, exceptions, warnings, and application health.

**Dashboard:** https://your-vitalytics-server.com
**API Endpoint:** https://api.vitalytics.app/api/v1/health/events

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Authentication](#authentication)
5. [Service Class](#service-class)
6. [Exception Handler Integration](#exception-handler-integration)
7. [Logging Events](#logging-events)
8. [Queue Integration](#queue-integration)
9. [Artisan Commands](#artisan-commands)
10. [Testing](#testing)

---

## Quick Start

```bash
# 1. Add configuration to .env
VITALYTICS_APP_SECRET=your-app-secret-here
VITALYTICS_APP_IDENTIFIER=myapp-api-dev
VITALYTICS_ENVIRONMENT=development
VITALYTICS_IS_TEST=true
VITALYTICS_DEVICE_ID=           # Generated on first run, copy from logs

# 2. Create the service class (see below)
# 3. Register in AppServiceProvider
# 4. Update Exception Handler
# 5. Start logging events
# 6. Copy generated VITALYTICS_DEVICE_ID from logs to .env
```

---

## Installation

No external packages required. The integration uses Laravel's built-in HTTP client and queue system.

### Required Files to Create:

```
app/
├── Services/
│   └── VitalyticsService.php
├── Jobs/
│   └── SendVitalyticsEvents.php
config/
└── vitalytics.php
```

---

## Configuration

### 1. Create config file: `config/vitalytics.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vitalytics Configuration
    |--------------------------------------------------------------------------
    */

    // App secret for authentication (retrieve API key dynamically)
    'app_secret' => env('VITALYTICS_APP_SECRET'),

    // Unique identifier for this application
    'app_identifier' => env('VITALYTICS_APP_IDENTIFIER', 'myapp-api-dev'),

    // Environment (production, staging, development)
    'environment' => env('VITALYTICS_ENVIRONMENT', 'production'),

    // Mark events as test data (filtered in dashboard)
    'is_test' => env('VITALYTICS_IS_TEST', false),

    // API endpoints
    'api_base_url' => env('VITALYTICS_API_URL', 'https://api.vitalytics.app/api/v1'),

    // Enable/disable Vitalytics reporting
    'enabled' => env('VITALYTICS_ENABLED', true),

    // Queue connection for async event sending
    'queue_connection' => env('VITALYTICS_QUEUE', 'redis'),

    // Persistent device/server ID (MUST be stored in .env to survive cache clears)
    // If empty, a new ID will be generated and logged for you to copy to .env
    'device_id' => env('VITALYTICS_DEVICE_ID'),

    // Batch settings
    'batch_size' => 10,           // Send when queue reaches this size
    'flush_interval' => 30,       // Seconds between flushes
];
```

### 2. Add to `.env`:

```env
# Vitalytics Configuration
VITALYTICS_APP_SECRET=your-app-secret-here
VITALYTICS_APP_IDENTIFIER=myapp-api-dev
VITALYTICS_ENVIRONMENT=development
VITALYTICS_IS_TEST=true
VITALYTICS_ENABLED=true
VITALYTICS_QUEUE=redis
VITALYTICS_DEVICE_ID=
```

**Important:**
- For DEV environment, set `VITALYTICS_IS_TEST=true` so events can be filtered in dashboard
- For PRODUCTION, set `VITALYTICS_IS_TEST=false`
- Get your `VITALYTICS_APP_SECRET` from the Vitalytics admin (Secrets page)

### Device ID Configuration

The `VITALYTICS_DEVICE_ID` is a persistent identifier for this server instance. **This value must be stored in `.env` to survive cache clears and deployments.**

**First-time setup:**
1. Leave `VITALYTICS_DEVICE_ID=` empty on first run
2. The service will generate a unique ID and log it to `storage/logs/laravel.log`
3. Copy the generated ID from the log and add it to your `.env` file
4. The log message will look like:
   ```
   Vitalytics: Generated new device ID. Add to .env: VITALYTICS_DEVICE_ID=srv-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
   ```

**Why this matters:** If the device ID is only stored in cache, clearing the cache (e.g., `php artisan cache:clear`, deployments, Redis flush) will generate a new device ID, causing the same server to appear as multiple devices in your dashboard.

---

## Authentication

Vitalytics uses a two-tier auth system:
1. **App Secret** - Bundled with your app, used to retrieve API keys
2. **API Key** - Time-limited (24h), used for event submission

The service class handles this automatically with caching.

---

## Service Class

### Create `app/Services/VitalyticsService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VitalyticsService
{
    private string $appSecret;
    private string $appIdentifier;
    private string $environment;
    private bool $isTest;
    private string $apiBaseUrl;
    private bool $enabled;

    private array $eventQueue = [];
    private ?string $deviceId = null;

    public function __construct()
    {
        $this->appSecret = config('vitalytics.app_secret', '');
        $this->appIdentifier = config('vitalytics.app_identifier', 'laravel-app');
        $this->environment = config('vitalytics.environment', 'production');
        $this->isTest = config('vitalytics.is_test', false);
        $this->apiBaseUrl = config('vitalytics.api_base_url', 'https://api.vitalytics.app/api/v1');
        $this->enabled = config('vitalytics.enabled', true);

        $this->deviceId = $this->getOrCreateDeviceId();
    }

    /**
     * Get or create a persistent device/server ID
     *
     * IMPORTANT: The device ID should be stored in .env (VITALYTICS_DEVICE_ID)
     * to survive cache clears. If not configured, a new ID will be generated
     * and logged so you can copy it to your .env file.
     */
    private function getOrCreateDeviceId(): string
    {
        // First, check if device ID is configured in .env (preferred)
        $configuredId = config('vitalytics.device_id');
        if (!empty($configuredId)) {
            return $configuredId;
        }

        // Fallback: generate and cache (with warning to configure in .env)
        $cacheKey = 'vitalytics_device_id';

        $cachedId = Cache::get($cacheKey);
        if ($cachedId) {
            return $cachedId;
        }

        // Generate new ID
        $newId = 'srv-' . Str::uuid()->toString();
        Cache::forever($cacheKey, $newId);

        // Log warning so developer knows to add to .env
        Log::warning("Vitalytics: Generated new device ID. Add to .env: VITALYTICS_DEVICE_ID={$newId}");

        return $newId;
    }

    /**
     * Get API key (cached, auto-refreshes when expired)
     */
    public function getApiKey(): ?string
    {
        if (!$this->enabled || empty($this->appSecret)) {
            return null;
        }

        $cacheKey = "vitalytics_api_key_{$this->appIdentifier}";

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Fetch new API key
        try {
            $response = Http::withHeaders([
                'X-App-Secret' => $this->appSecret,
            ])->get("{$this->apiBaseUrl}/auth/key/{$this->appIdentifier}");

            if ($response->successful()) {
                $data = $response->json();
                $apiKey = $data['api_key'];
                $expiresIn = $data['expires_in'] ?? 86400;

                // Cache with 5-minute buffer before expiry
                Cache::put($cacheKey, $apiKey, now()->addSeconds($expiresIn - 300));

                return $apiKey;
            }

            Log::warning('Vitalytics: Failed to get API key', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Vitalytics: Exception getting API key', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get device info for this server
     */
    private function getDeviceInfo(): array
    {
        return [
            'deviceId' => $this->deviceId,
            'deviceModel' => php_uname('n'), // hostname
            'osVersion' => php_uname('s') . ' ' . php_uname('r'),
            'appVersion' => config('app.version', '1.0.0'),
            'buildNumber' => config('app.build', null),
            'platform' => 'laravel',
        ];
    }

    /**
     * Log a crash/fatal error
     */
    public function logCrash(string $message, ?array $stackTrace = null, ?array $context = null): void
    {
        $this->log('crash', $message, $stackTrace, $context);
        $this->flush(); // Immediately flush crashes
    }

    /**
     * Log an error
     */
    public function logError(string $message, ?array $stackTrace = null, ?array $context = null): void
    {
        $this->log('error', $message, $stackTrace, $context);
    }

    /**
     * Log a warning
     */
    public function logWarning(string $message, ?array $context = null): void
    {
        $this->log('warning', $message, null, $context);
    }

    /**
     * Log a network error
     */
    public function logNetworkError(string $message, ?array $context = null): void
    {
        $this->log('networkError', $message, null, $context);
    }

    /**
     * Log an info event
     */
    public function logInfo(string $message, ?array $context = null): void
    {
        $this->log('info', $message, null, $context);
    }

    /**
     * Log a heartbeat
     */
    public function logHeartbeat(?array $context = null): void
    {
        $this->log('heartbeat', 'Heartbeat', null, $context);
    }

    /**
     * Log an exception
     */
    public function logException(\Throwable $exception, ?array $context = null): void
    {
        $level = $this->getExceptionLevel($exception);
        $stackTrace = $this->formatStackTrace($exception);

        $context = array_merge($context ?? [], [
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
        ]);

        $this->log($level, $exception->getMessage(), $stackTrace, $context);

        // Flush immediately for errors and crashes
        if (in_array($level, ['crash', 'error'])) {
            $this->flush();
        }
    }

    /**
     * Determine event level based on exception type
     */
    private function getExceptionLevel(\Throwable $exception): string
    {
        // Fatal/crash level exceptions
        if ($exception instanceof \Error ||
            $exception instanceof \ParseError ||
            $exception instanceof \TypeError) {
            return 'crash';
        }

        // Network-related exceptions
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException ||
            $exception instanceof \GuzzleHttp\Exception\ConnectException) {
            return 'networkError';
        }

        // Default to error
        return 'error';
    }

    /**
     * Format exception stack trace
     */
    private function formatStackTrace(\Throwable $exception): array
    {
        $frames = [];
        $frames[] = $exception->getFile() . ':' . $exception->getLine();

        foreach ($exception->getTrace() as $frame) {
            $line = '';
            if (isset($frame['file'])) {
                $line .= $frame['file'];
                if (isset($frame['line'])) {
                    $line .= ':' . $frame['line'];
                }
            }
            if (isset($frame['class'])) {
                $line .= ' ' . $frame['class'] . $frame['type'] . $frame['function'] . '()';
            } elseif (isset($frame['function'])) {
                $line .= ' ' . $frame['function'] . '()';
            }
            if (!empty($line)) {
                $frames[] = $line;
            }
        }

        return array_slice($frames, 0, 50); // Limit stack trace size
    }

    /**
     * Internal log method
     */
    private function log(string $level, string $message, ?array $stackTrace = null, ?array $context = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $event = [
            'id' => 'evt-' . Str::uuid()->toString(),
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => Str::limit($message, 1000),
            'context' => $context,
            'stackTrace' => $stackTrace,
        ];

        $this->eventQueue[] = $event;

        // Auto-flush on critical events or large queue
        $batchSize = config('vitalytics.batch_size', 10);
        if ($level === 'crash' || $level === 'error' || count($this->eventQueue) >= $batchSize) {
            $this->flush();
        }
    }

    /**
     * Flush event queue to API
     */
    public function flush(): void
    {
        if (empty($this->eventQueue) || !$this->enabled) {
            return;
        }

        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            Log::warning('Vitalytics: Cannot flush, no API key available');
            return;
        }

        $events = $this->eventQueue;
        $this->eventQueue = [];

        $batch = [
            'batchId' => Str::uuid()->toString(),
            'deviceInfo' => $this->getDeviceInfo(),
            'appIdentifier' => $this->appIdentifier,
            'environment' => $this->environment,
            'isTest' => $this->isTest,
            'events' => $events,
            'sentAt' => now()->toISOString(),
        ];

        // Send asynchronously via job if queue is configured
        if (config('vitalytics.queue_connection')) {
            \App\Jobs\SendVitalyticsEvents::dispatch($batch, $apiKey);
        } else {
            $this->sendBatch($batch, $apiKey);
        }
    }

    /**
     * Send batch to API (can be called directly or from job)
     */
    public function sendBatch(array $batch, string $apiKey): bool
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'X-App-Identifier' => $this->appIdentifier,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiBaseUrl}/health/events", $batch);

            if (!$response->successful()) {
                Log::warning('Vitalytics: Failed to send events', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Vitalytics: Exception sending events', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if Vitalytics is enabled and configured
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->appSecret);
    }
}
```

---

## Queue Job

### Create `app/Jobs/SendVitalyticsEvents.php`:

```php
<?php

namespace App\Jobs;

use App\Services\VitalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendVitalyticsEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private array $batch,
        private string $apiKey
    ) {
        $this->onQueue(config('vitalytics.queue_connection', 'default'));
    }

    public function handle(VitalyticsService $vitalytics): void
    {
        $vitalytics->sendBatch($this->batch, $this->apiKey);
    }
}
```

---

## Exception Handler Integration

### Update `app/Exceptions/Handler.php`:

```php
<?php

namespace App\Exceptions;

use App\Services\VitalyticsService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types that are not reported to Vitalytics.
     */
    protected $dontReportToVitalytics = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Session\TokenMismatchException::class,
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e)
    {
        // Report to Vitalytics
        $this->reportToVitalytics($e);

        parent::report($e);
    }

    /**
     * Report exception to Vitalytics
     */
    protected function reportToVitalytics(Throwable $e): void
    {
        // Skip if exception type is in ignore list
        foreach ($this->dontReportToVitalytics as $type) {
            if ($e instanceof $type) {
                return;
            }
        }

        try {
            $vitalytics = app(VitalyticsService::class);

            if ($vitalytics->isEnabled()) {
                $context = [
                    'url' => request()?->fullUrl(),
                    'method' => request()?->method(),
                    'user_id' => auth()->id(),
                    'ip' => request()?->ip(),
                ];

                $vitalytics->logException($e, $context);
            }
        } catch (\Exception $ex) {
            // Don't let Vitalytics errors break the app
            \Log::error('Vitalytics reporting failed: ' . $ex->getMessage());
        }
    }
}
```

---

## Service Provider Registration

### Update `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Services\VitalyticsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register as singleton so event queue persists
        $this->app->singleton(VitalyticsService::class, function ($app) {
            return new VitalyticsService();
        });
    }

    public function boot(): void
    {
        // Send heartbeat on application termination
        if (config('vitalytics.enabled')) {
            $this->app->terminating(function () {
                try {
                    app(VitalyticsService::class)->flush();
                } catch (\Exception $e) {
                    // Silently fail
                }
            });
        }
    }
}
```

---

## Logging Events

### Manual Event Logging Examples:

```php
use App\Services\VitalyticsService;

class SomeController extends Controller
{
    public function __construct(
        private VitalyticsService $vitalytics
    ) {}

    public function processPayment(Request $request)
    {
        try {
            // Process payment...

            // Log success
            $this->vitalytics->logInfo('Payment processed successfully', [
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'payment_method' => $request->method,
            ]);

        } catch (PaymentFailedException $e) {
            // Log payment error
            $this->vitalytics->logError('Payment failed', null, [
                'user_id' => auth()->id(),
                'error_code' => $e->getCode(),
                'payment_method' => $request->method,
            ]);

            throw $e;
        }
    }

    public function callExternalApi()
    {
        try {
            $response = Http::get('https://api.example.com/data');

            if (!$response->successful()) {
                $this->vitalytics->logNetworkError('External API returned error', [
                    'endpoint' => 'https://api.example.com/data',
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

        } catch (ConnectionException $e) {
            $this->vitalytics->logNetworkError('External API connection failed', [
                'endpoint' => 'https://api.example.com/data',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### Using Facade (Optional):

Create `app/Facades/Vitalytics.php`:

```php
<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Vitalytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\VitalyticsService::class;
    }
}
```

Register in `config/app.php` aliases:

```php
'aliases' => [
    // ...
    'Vitalytics' => App\Facades\Vitalytics::class,
],
```

Usage:

```php
use Vitalytics;

Vitalytics::logError('Something went wrong', null, ['context' => 'data']);
Vitalytics::logWarning('Deprecated feature used');
Vitalytics::logInfo('User signed up', ['user_id' => $user->id]);
Vitalytics::logHeartbeat(['queue_size' => Queue::size()]);
```

---

## Artisan Commands

### Create Heartbeat Command: `app/Console/Commands/VitalyticsHeartbeat.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\VitalyticsService;
use Illuminate\Console\Command;

class VitalyticsHeartbeat extends Command
{
    protected $signature = 'vitalytics:heartbeat';
    protected $description = 'Send a heartbeat to Vitalytics';

    public function handle(VitalyticsService $vitalytics): int
    {
        if (!$vitalytics->isEnabled()) {
            $this->warn('Vitalytics is not enabled');
            return 1;
        }

        $vitalytics->logHeartbeat([
            'queue_size' => \Queue::size(),
            'memory_usage' => memory_get_usage(true),
            'php_version' => PHP_VERSION,
        ]);

        $vitalytics->flush();

        $this->info('Heartbeat sent successfully');
        return 0;
    }
}
```

### Schedule Heartbeat in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Send heartbeat every 5 minutes
    $schedule->command('vitalytics:heartbeat')->everyFiveMinutes();
}
```

---

## Testing

### Test Configuration:

In `.env.testing`:

```env
VITALYTICS_ENABLED=false
```

Or mock in tests:

```php
use App\Services\VitalyticsService;

public function test_something()
{
    $this->mock(VitalyticsService::class, function ($mock) {
        $mock->shouldReceive('logError')->once();
        $mock->shouldReceive('flush')->once();
    });

    // Test code...
}
```

---

## Event Levels Reference

| Level | Description | When to Use |
|-------|-------------|-------------|
| `crash` | Fatal error | Unhandled exceptions, fatal PHP errors |
| `error` | Error condition | Caught exceptions, failed operations |
| `warning` | Warning | Deprecations, degraded performance |
| `networkError` | Network issue | API failures, timeouts, connection errors |
| `info` | Informational | User actions, milestones, feature usage |
| `heartbeat` | Health check | Scheduled "I'm alive" signals |

---

## Checklist

- [ ] Create `config/vitalytics.php`
- [ ] Add environment variables to `.env`
- [ ] Create `VitalyticsService.php`
- [ ] Create `SendVitalyticsEvents.php` job
- [ ] Update `Handler.php` exception handler
- [ ] Register singleton in `AppServiceProvider`
- [ ] (Optional) Create Facade
- [ ] (Optional) Create heartbeat command
- [ ] (Optional) Schedule heartbeat
- [ ] Get app secret from Vitalytics admin
- [ ] Test with `VITALYTICS_IS_TEST=true`
- [ ] Copy generated `VITALYTICS_DEVICE_ID` from logs to `.env` (after first run)

---

## Support

- **Dashboard:** https://your-vitalytics-server.com
- **Help:** https://your-vitalytics-server.com/help/integrations

---

*Document generated for My App DEV integration*
*App Identifier: `myapp-api-dev`*
