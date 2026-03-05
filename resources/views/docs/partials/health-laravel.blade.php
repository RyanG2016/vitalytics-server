<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-laravel mr-2 text-red-500"></i>Laravel Health Monitoring
    </h2>
    <p class="text-gray-600 mb-6">Track crashes, errors, and health events in your Laravel application.</p>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-download mr-2"></i>Installation
    </h3>
    <p class="text-gray-600 mb-4">Install the Vitalytics Laravel SDK via Composer:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">1. Add repository to composer.json</div>
        <pre class="text-gray-100 text-sm"><code>{
    "repositories": [
        {
            "type": "composer",
            "url": "https://your-vitalytics-server.com/composer"
        }
    ]
}</code></pre>
    </div>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">2. Install package</div>
        <pre class="text-gray-100 text-sm"><code>composer require vitalytics/laravel-sdk</code></pre>
    </div>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">3. Publish configuration</div>
        <pre class="text-gray-100 text-sm"><code>php artisan vendor:publish --tag=vitalytics-config</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Configuration</h3>
    <p class="text-gray-600 mb-4">Add these environment variables to your <code class="bg-gray-200 px-1 rounded">.env</code> file:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">.env</div>
        <pre class="text-gray-100 text-sm"><code># Required
VITALYTICS_BASE_URL=https://your-vitalytics-server.com
VITALYTICS_API_KEY=your-api-key
VITALYTICS_APP_IDENTIFIER=myapp-laravel

# Optional
VITALYTICS_DEVICE_ID=server-1
VITALYTICS_IS_TEST=false
VITALYTICS_HEALTH_ENABLED=true

# Automatic Exception Reporting (v1.0.14+)
VITALYTICS_EXCEPTIONS_ENABLED=true</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-bolt mr-2 text-yellow-500"></i>Automatic Exception Reporting
    </h3>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
        <p class="text-green-700"><strong>New in v1.0.14:</strong> The SDK now automatically captures and reports all unhandled exceptions (500 errors) to Vitalytics. No additional code required!</p>
    </div>

    <p class="text-gray-600 mb-4">When enabled (default), the SDK integrates with Laravel's exception handler to automatically report:</p>
    <ul class="list-disc list-inside mb-4 text-gray-700 space-y-1">
        <li><strong>All unhandled exceptions (500 errors) as "crash" level (critical)</strong></li>
        <li>Full stack trace with file and line number</li>
        <li>Request context: URL, HTTP method, route name, IP address</li>
        <li>Authenticated user ID when available</li>
    </ul>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">What Gets Captured Automatically</h4>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
        <p class="text-red-700"><strong>All unhandled exceptions are reported as "crash" level (critical).</strong> This means any 500 server error will trigger an alert in Vitalytics.</p>
    </div>
    <div class="overflow-x-auto mb-4">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Exception Type</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Event Level</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Triggers Alert</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2">All unhandled exceptions (500 errors)</td>
                    <td class="px-4 py-2 font-mono text-red-600">crash (critical)</td>
                    <td class="px-4 py-2"><strong>Yes</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Ignored Exceptions (Not Reported)</h4>
    <p class="text-gray-600 mb-2">These common HTTP exceptions are ignored by default since they represent normal user errors, not server issues:</p>
    <ul class="list-disc list-inside mb-4 text-gray-700 text-sm space-y-1">
        <li><code class="bg-gray-200 px-1 rounded">AuthenticationException</code> (401)</li>
        <li><code class="bg-gray-200 px-1 rounded">AuthorizationException</code> (403)</li>
        <li><code class="bg-gray-200 px-1 rounded">ModelNotFoundException</code> (404)</li>
        <li><code class="bg-gray-200 px-1 rounded">ValidationException</code> (422)</li>
        <li><code class="bg-gray-200 px-1 rounded">NotFoundHttpException</code> (404)</li>
        <li><code class="bg-gray-200 px-1 rounded">MethodNotAllowedHttpException</code> (405)</li>
    </ul>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Customizing Exception Reporting</h4>
    <p class="text-gray-600 mb-2">Modify the published config file to customize which exceptions to ignore:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">config/vitalytics.php</div>
        <pre class="text-gray-100 text-sm"><code>'exceptions' => [
    'enabled' => env('VITALYTICS_EXCEPTIONS_ENABLED', true),

    // Add exception classes to ignore (won't be reported)
    // These are typically non-500 errors (4xx HTTP status codes)
    'ignore' => [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Validation\ValidationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        // Add your own:
        \App\Exceptions\RateLimitException::class,
    ],
],</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Disabling Automatic Reporting</h4>
    <p class="text-gray-600 mb-4">Set <code class="bg-gray-200 px-1 rounded">VITALYTICS_EXCEPTIONS_ENABLED=false</code> in your <code class="bg-gray-200 px-1 rounded">.env</code> to disable automatic exception reporting.</p>

    <hr class="my-6 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Manual Exception Reporting (Optional)</h3>
    <p class="text-gray-600 mb-4">For additional control or to report handled exceptions, you can still manually report from your exception handler:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">app/Exceptions/Handler.php</div>
        <pre class="text-gray-100 text-sm"><code>public function report(Throwable $e): void
{
    parent::report($e);

    // Report to Vitalytics (for custom handling)
    if ($this->shouldReport($e)) {
        Vitalytics::crash(
            message: $e->getMessage(),
            stackTrace: $e->getTraceAsString(),
            context: [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
            ]
        );
    }
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Sending Health Events</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>use Vitalytics\Facades\Vitalytics;

// Report an error
Vitalytics::error('Failed to process payment', [
    'order_id' => $order->id,
    'amount' => $amount,
    'gateway' => 'stripe'
]);

// Report a warning
Vitalytics::warning('API rate limit approaching', [
    'current' => $current,
    'limit' => $limit
]);

// Report general info
Vitalytics::info('Order completed successfully', [
    'order_id' => $order->id
]);

// Report a crash (typically auto-captured via Handler)
Vitalytics::crash($exception->getMessage(), $exception->getTraceAsString(), [
    'user_id' => auth()->id()
]);

// Send a heartbeat (v1.3.3+)
Vitalytics::heartbeat();
Vitalytics::heartbeat('Scheduler running', ['job' => 'ProcessQueue']);</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-heartbeat mr-2 text-red-500"></i>Scheduled Heartbeats (v1.3.3+)
    </h3>
    <p class="text-gray-600 mb-4">Send regular heartbeats to monitor that your application is running. If heartbeats stop, you'll be alerted.</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">app/Console/Kernel.php (Laravel 10) or routes/console.php (Laravel 11+)</div>
        <pre class="text-gray-100 text-sm"><code>use Illuminate\Support\Facades\Schedule;

// Send heartbeat every 2 minutes
Schedule::call(function () {
    \Vitalytics\Facades\Vitalytics::heartbeat('Application heartbeat');
})->everyTwoMinutes();

// Or with more context
Schedule::call(function () {
    \Vitalytics\Facades\Vitalytics::heartbeat('Web server healthy', [
        'memory_usage' => memory_get_usage(true),
        'queue_size' => \DB::table('jobs')->count(),
    ]);
})->everyFiveMinutes();</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Queue Job Monitoring</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// In AppServiceProvider boot()
Queue::failing(function (JobFailed $event) {
    Vitalytics::error('Queue job failed', [
        'job' => get_class($event->job),
        'exception' => $event->exception->getMessage(),
        'queue' => $event->job->getQueue(),
    ]);
});</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Health Event Types</h3>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Type</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Method</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Use Case</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-mono text-red-600">crash</td>
                    <td class="px-4 py-2"><code>Vitalytics::crash($msg, $stack, $ctx)</code></td>
                    <td class="px-4 py-2">Unhandled exceptions, fatal errors</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-orange-600">error</td>
                    <td class="px-4 py-2"><code>Vitalytics::error($msg, $ctx)</code></td>
                    <td class="px-4 py-2">API failures, validation errors</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-yellow-600">warning</td>
                    <td class="px-4 py-2"><code>Vitalytics::warning($msg, $ctx)</code></td>
                    <td class="px-4 py-2">Deprecations, slow operations</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-blue-600">info</td>
                    <td class="px-4 py-2"><code>Vitalytics::info($msg, $ctx)</code></td>
                    <td class="px-4 py-2">Successful operations, milestones</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-green-600">heartbeat</td>
                    <td class="px-4 py-2"><code>Vitalytics::heartbeat($msg, $ctx)</code></td>
                    <td class="px-4 py-2">Scheduled health checks, keep-alive signals</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr class="my-8 border-gray-300">

    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fas fa-tools mr-2 text-orange-500"></i>Maintenance Notifications (v1.4.0+)
    </h2>
    <p class="text-gray-600 mb-6">Display scheduled maintenance banners in your Laravel application. Notifications are delivered via heartbeat responses from the Vitalytics server.</p>

    <div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-4">
        <p class="text-orange-700"><strong>New in v1.4.0:</strong> Maintenance notifications are automatically received when your application sends heartbeat events. Configure banners in the Vitalytics admin dashboard at <code class="bg-orange-100 px-1 rounded">/admin/maintenance</code>.</p>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Configuration</h3>
    <p class="text-gray-600 mb-4">Add these environment variables to enable maintenance notifications:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">.env</div>
        <pre class="text-gray-100 text-sm"><code># Enable maintenance notification display (default: true)
VITALYTICS_MAINTENANCE_ENABLED=true

# Auto-inject banners at top of HTML pages (default: false)
VITALYTICS_MAINTENANCE_AUTO_INJECT=false

# Cache refresh interval in seconds (default: 300)
VITALYTICS_MAINTENANCE_REFRESH=300</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Option 1: Blade Directive</h3>
    <p class="text-gray-600 mb-4">Add the <code class="bg-gray-200 px-1 rounded">@vitalyticsMaintenance</code> directive in your layout where you want banners to appear:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">resources/views/layouts/app.blade.php</div>
        <pre class="text-gray-100 text-sm"><code>&lt;body&gt;
    @vitalyticsMaintenance

    &lt;div class="container"&gt;
        @yield('content')
    &lt;/div&gt;
&lt;/body&gt;</code></pre>
    </div>

    <p class="text-gray-600 mb-4">The directive outputs Tailwind CSS-styled banners. Severity levels have different colors:</p>
    <ul class="list-disc list-inside mb-6 text-gray-700 space-y-1">
        <li><span class="text-blue-600 font-semibold">Info</span> - Blue background</li>
        <li><span class="text-yellow-600 font-semibold">Warning</span> - Yellow background</li>
        <li><span class="text-red-600 font-semibold">Critical</span> - Red background</li>
    </ul>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Option 2: Auto-Inject Middleware</h3>
    <p class="text-gray-600 mb-4">Automatically inject banners at the top of all HTML pages without modifying views:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">1. Set environment variable</div>
        <pre class="text-gray-100 text-sm"><code>VITALYTICS_MAINTENANCE_AUTO_INJECT=true</code></pre>
    </div>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">2. Register middleware in bootstrap/app.php (Laravel 11+) or Kernel.php (Laravel 10)</div>
        <pre class="text-gray-100 text-sm"><code>// Laravel 11+ (bootstrap/app.php)
use Vitalytics\Http\Middleware\InjectMaintenanceBanner;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        InjectMaintenanceBanner::class,
    ]);
})

// Laravel 10 (app/Http/Kernel.php)
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Vitalytics\Http\Middleware\InjectMaintenanceBanner::class,
    ],
];</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Option 3: Programmatic Access</h3>
    <p class="text-gray-600 mb-4">Access maintenance notifications directly in your code for custom rendering:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>use Vitalytics\Facades\Vitalytics;

// Check if there are any notifications to display
if (Vitalytics::hasMaintenanceNotifications()) {
    // Get all displayable notifications (filters out dismissed ones)
    $notifications = Vitalytics::getDisplayableMaintenanceNotifications();

    foreach ($notifications as $notification) {
        echo $notification['title'];     // "Scheduled Maintenance"
        echo $notification['message'];   // "System unavailable 2-4 AM"
        echo $notification['severity'];  // "info", "warning", or "critical"
        echo $notification['dismissible']; // true or false
        echo $notification['id'];        // Notification ID for dismissal
    }
}

// Dismiss a notification programmatically
Vitalytics::dismissMaintenance($notificationId);

// Clear all dismissed notifications (show them again)
Vitalytics::clearDismissedMaintenance();</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">User Dismissal</h3>
    <p class="text-gray-600 mb-4">When notifications are marked as "dismissible" in the admin dashboard, users can dismiss them. Dismissals are stored in the session, so:</p>
    <ul class="list-disc list-inside mb-6 text-gray-700 space-y-1">
        <li>Dismissed banners stay hidden for the current session</li>
        <li>Banners reappear when the user starts a new session</li>
        <li>Each notification ID is tracked separately</li>
    </ul>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">How It Works</h3>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Step</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2">1</td>
                    <td class="px-4 py-2">Admin creates maintenance notification in Vitalytics dashboard</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">2</td>
                    <td class="px-4 py-2">Your app sends a heartbeat: <code class="bg-gray-200 px-1 rounded">Vitalytics::heartbeat()</code></td>
                </tr>
                <tr>
                    <td class="px-4 py-2">3</td>
                    <td class="px-4 py-2">Heartbeat response includes active maintenance notifications</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">4</td>
                    <td class="px-4 py-2">SDK caches notifications locally (5 min default)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">5</td>
                    <td class="px-4 py-2">Blade directive or middleware displays banners to users</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-700"><strong>Important:</strong> Maintenance notifications require heartbeats to be enabled. Make sure you're sending heartbeats regularly (e.g., every 2-5 minutes via scheduler) for notifications to be received.</p>
    </div>

    <hr class="my-8 border-gray-300">

    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fas fa-database mr-2 text-blue-500"></i>MariaDB Database Monitoring
    </h2>
    <p class="text-gray-600 mb-6">Monitor your MariaDB database health including connection pool, query performance, buffer pool, and locks. Automatically sends alerts when thresholds are exceeded or when the database connection fails.</p>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Installation</h3>
    <p class="text-gray-600 mb-4">Copy these two files to your Laravel application:</p>
    <ol class="list-decimal list-inside mb-6 text-gray-700 space-y-2">
        <li><code class="bg-gray-200 px-1 rounded">VitalyticsDatabaseHealth.php</code> &rarr; <code class="bg-gray-200 px-1 rounded">app/Services/</code></li>
        <li><code class="bg-gray-200 px-1 rounded">VitalyticsDbHealthCheck.php</code> &rarr; <code class="bg-gray-200 px-1 rounded">app/Console/Commands/</code></li>
    </ol>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-700"><strong>Download:</strong> Get the SDK files from <a href="{{ route('docs.analytics-sdk') }}" class="underline">the SDK documentation</a> or copy them from the code blocks below.</p>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code># Test the health check (dry-run mode)
php artisan vitalytics:db-health --dry-run

# Run and send to Vitalytics
php artisan vitalytics:db-health

# Check a specific database connection
php artisan vitalytics:db-health --connection=mysql_replica

# Output as JSON
php artisan vitalytics:db-health --dry-run --json</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Scheduler Setup</h3>
    <p class="text-gray-600 mb-4">Add the health check to your Laravel scheduler for automatic monitoring:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">app/Console/Kernel.php</div>
        <pre class="text-gray-100 text-sm"><code>protected function schedule(Schedule $schedule): void
{
    // Check database health every 5 minutes
    $schedule->command('vitalytics:db-health')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->runInBackground();

    // Optional: Check replica health separately
    $schedule->command('vitalytics:db-health --connection=mysql_replica')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Metrics Collected</h3>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Category</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Metrics</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Alerts</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-semibold">Connection Pool</td>
                    <td class="px-4 py-2">Current/max connections, usage %, aborted connections</td>
                    <td class="px-4 py-2">Warning at 70%, Critical at 90%</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold">Query Performance</td>
                    <td class="px-4 py-2">Slow queries, QPS, uptime, SELECT/INSERT/UPDATE/DELETE counts</td>
                    <td class="px-4 py-2">Warning at 10, Critical at 50 slow queries</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold">Buffer Pool</td>
                    <td class="px-4 py-2">Size, usage %, hit ratio, dirty pages</td>
                    <td class="px-4 py-2">Warning at 85%, Critical at 95%</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold">Locks</td>
                    <td class="px-4 py-2">Table locks waited, row lock waits, avg lock time</td>
                    <td class="px-4 py-2">Warning at 5, Critical at 20 waits</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold">Replication</td>
                    <td class="px-4 py-2">IO/SQL thread status, seconds behind master</td>
                    <td class="px-4 py-2">Error if stopped, Warning if lag &gt; 60s</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Custom Thresholds</h3>
    <p class="text-gray-600 mb-4">Adjust thresholds based on your environment:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>use App\Services\VitalyticsDatabaseHealth;

$health = new VitalyticsDatabaseHealth('mysql');

// Customize thresholds for high-traffic environments
$health->setThresholds([
    'connection_usage_warning' => 0.8,      // 80% instead of 70%
    'connection_usage_critical' => 0.95,    // 95% instead of 90%
    'slow_queries_warning' => 25,           // 25 instead of 10
    'slow_queries_critical' => 100,         // 100 instead of 50
]);

$metrics = $health->check();
$health->reportHealth();</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Event Levels</h3>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Level</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Condition</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Alert Triggered</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-mono text-green-600">info</td>
                    <td class="px-4 py-2">All metrics within normal range</td>
                    <td class="px-4 py-2">No</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-yellow-600">warning</td>
                    <td class="px-4 py-2">Any metric exceeds warning threshold</td>
                    <td class="px-4 py-2">No</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-orange-600">error</td>
                    <td class="px-4 py-2">Any metric exceeds critical threshold</td>
                    <td class="px-4 py-2">Yes</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-red-600">crash</td>
                    <td class="px-4 py-2">Database connection failed</td>
                    <td class="px-4 py-2">Yes (Critical)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-code mr-2"></i>Service Class
    </h3>
    <p class="text-gray-600 mb-3">Copy to <code class="bg-gray-200 px-1 rounded">app/Services/VitalyticsDatabaseHealth.php</code>:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 400px;">
        <pre class="text-gray-100 text-sm"><code>&lt;?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VitalyticsDatabaseHealth
{
    private string $connectionName;
    private ?array $lastMetrics = null;
    private ?string $lastLevel = null;
    private array $issues = [];
    private array $thresholds = [
        'connection_usage_warning' => 0.7,
        'connection_usage_critical' => 0.9,
        'slow_queries_warning' => 10,
        'slow_queries_critical' => 50,
        'buffer_pool_usage_warning' => 0.85,
        'buffer_pool_usage_critical' => 0.95,
        'lock_wait_warning' => 5,
        'lock_wait_critical' => 20,
        'aborted_connects_warning' => 10,
        'aborted_connects_critical' => 50,
    ];
    private static array $previousMetrics = [];

    public function __construct(string $connectionName = 'mysql') {
        $this->connectionName = $connectionName;
    }

    public function setThresholds(array $thresholds): self {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
        return $this;
    }

    public function check(): array {
        $this->issues = [];
        $this->lastLevel = 'info';
        try {
            $conn = $this->checkConnection();
            if (!$conn['connected']) {
                $this->lastLevel = 'crash';
                $this->lastMetrics = [
                    'connected' => false,
                    'error' => $conn['error'],
                    'connection_name' => $this->connectionName,
                ];
                return $this->lastMetrics;
            }
            $this->lastMetrics = [
                'connected' => true,
                'connections' => $this->checkConnectionPool(),
                'performance' => $this->checkPerformance(),
                'memory' => $this->checkBufferPool(),
                'locks' => $this->checkLocks(),
            ];
            return $this->lastMetrics;
        } catch (\Exception $e) {
            $this->lastLevel = 'crash';
            $this->lastMetrics = ['connected' => false, 'error' => $e->getMessage()];
            return $this->lastMetrics;
        }
    }

    public function reportHealth(): bool {
        if ($this->lastMetrics === null) $this->check();
        try {
            $vitalytics = \Vitalytics::instance();
            $metadata = ['type' => 'database_health', 'database' => 'mariadb', 'metrics' => $this->lastMetrics];
            if (!empty($this->issues)) $metadata['issues'] = $this->issues;

            match($this->lastLevel) {
                'crash' => $vitalytics->crash($this->getMessage(), null, $metadata),
                'error' => $vitalytics->error($this->getMessage(), $metadata),
                'warning' => $vitalytics->warning($this->getMessage(), $metadata),
                default => $vitalytics->info($this->getMessage(), $metadata),
            };
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to report DB health: ' . $e->getMessage());
            return false;
        }
    }

    private function checkConnection(): array {
        try {
            DB::connection($this->connectionName)->select('SELECT 1');
            return ['connected' => true, 'error' => null];
        } catch (\Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    // Additional methods: checkConnectionPool(), checkPerformance(),
    // checkBufferPool(), checkLocks(), etc.
    // See full implementation in SDK download.
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-terminal mr-2"></i>Artisan Command
    </h3>
    <p class="text-gray-600 mb-3">Copy to <code class="bg-gray-200 px-1 rounded">app/Console/Commands/VitalyticsDbHealthCheck.php</code>:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 400px;">
        <pre class="text-gray-100 text-sm"><code>&lt;?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VitalyticsDatabaseHealth;

class VitalyticsDbHealthCheck extends Command
{
    protected $signature = 'vitalytics:db-health
                            {--connection=mysql : Database connection name}
                            {--dry-run : Show metrics without sending}
                            {--json : Output as JSON}';

    protected $description = 'Check database health and report to Vitalytics';

    public function handle(): int
    {
        $health = new VitalyticsDatabaseHealth($this->option('connection'));
        $metrics = $health->check();

        if ($this->option('json')) {
            $this->line(json_encode([
                'level' => $health->getLevel(),
                'issues' => $health->getIssues(),
                'metrics' => $metrics,
            ], JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $this->displayMetrics($metrics, $health);

        if (!$this->option('dry-run')) {
            $health->reportHealth();
            $this->info('Health event sent.');
        }

        return Command::SUCCESS;
    }

    private function displayMetrics(array $metrics, $health): void
    {
        // Display formatted metrics tables
        // See full implementation in SDK download.
    }
}</code></pre>
    </div>

    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <p class="text-green-700"><strong>Tip:</strong> Run <code class="bg-green-100 px-1 rounded">php artisan vitalytics:db-health --dry-run</code> first to verify your database connection and see the collected metrics before enabling scheduled reporting.</p>
    </div>
</div>
