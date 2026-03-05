<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-microsoft mr-2 text-blue-600"></i>.NET Health Monitoring
    </h2>
    <p class="text-gray-600 mb-6">Track crashes, errors, heartbeats, and health events in your .NET applications.</p>

    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <p class="text-green-800"><strong>New SDK:</strong> Health monitoring is now part of the unified Vitalytics .NET SDK with automatic exception capture and heartbeat support.</p>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-800"><strong>Always Active:</strong> Health monitoring (errors, crashes, heartbeats) is independent of analytics consent. Even when analytics tracking requires user consent, health data is always sent. This ensures you can monitor application stability regardless of user tracking preferences.</p>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Installation</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code># Add nuget.config with Vitalytics feed first (see Analytics docs)

# For ASP.NET Core (includes ILogger integration)
dotnet add package Vitalytics.SDK.AspNetCore

# For desktop/console apps
dotnet add package Vitalytics.SDK</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start - Desktop Apps</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>using Vitalytics.SDK;
using Vitalytics.SDK.Common;

// Initialize at startup
Vitalytics.Initialize(new VitalyticsOptions
{
    AppId = "{{ $product->identifier ?? 'your-app-id' }}",
    AppSecret = "your-secret",

    Health = new HealthOptions
    {
        EnableHeartbeat = true,                      // Send "I'm alive" every 5 minutes
        HeartbeatInterval = TimeSpan.FromMinutes(5),
        CaptureUnhandledExceptions = true,           // Auto-capture crashes
        IncludeStackTrace = true
    }
});</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start - ASP.NET Core</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Program.cs
builder.Services.AddVitalytics(options =&gt;
{
    options.AppId = "{{ $product->identifier ?? 'your-app-id' }}";
    options.AppSecret = "your-secret";

    options.Health.EnableHeartbeat = true;
    options.Health.CaptureUnhandledExceptions = true;
    options.Health.IntegrateWithILogger = true;  // Auto-capture Error/Critical logs
});

// Add ILogger integration
builder.Logging.AddVitalytics();

var app = builder.Build();
app.UseVitalytics();</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Sending Health Events</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Report an error with exception
try
{
    await LoadUserDataAsync();
}
catch (Exception ex)
{
    Vitalytics.Health.ReportError(ex, new Dictionary&lt;string, object&gt;
    {
        ["userId"] = "123",
        ["endpoint"] = "/api/users"
    });
}

// Report an error without exception
Vitalytics.Health.ReportError("API rate limit exceeded", metadata: new Dictionary&lt;string, object&gt;
{
    ["endpoint"] = "/api/transcribe",
    ["retryAfter"] = 60
});

// Report a warning
Vitalytics.Health.ReportWarning("Slow API response", new Dictionary&lt;string, object&gt;
{
    ["duration_ms"] = 3500,
    ["endpoint"] = "/api/search"
});

// Report informational event
Vitalytics.Health.ReportInfo("User completed onboarding", new Dictionary&lt;string, object&gt;
{
    ["steps_completed"] = 5,
    ["duration_seconds"] = 120
});

// Manually send heartbeat with custom metadata
await Vitalytics.Health.SendHeartbeatAsync(new Dictionary&lt;string, object&gt;
{
    ["active_users"] = 42,
    ["memory_mb"] = 512
});</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-bolt mr-2 text-yellow-500"></i>Automatic Exception Capture
    </h3>
    <p class="text-gray-600 mb-3">When <code>CaptureUnhandledExceptions = true</code>, the SDK automatically captures:</p>
    <ul class="list-disc list-inside text-gray-600 mb-4">
        <li>AppDomain.UnhandledException (all unhandled exceptions)</li>
        <li>Crash events are sent immediately with full stack trace</li>
        <li>Events are flushed synchronously to ensure delivery before app terminates</li>
    </ul>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">WPF Additional Exception Handlers</h4>
    <p class="text-gray-600 mb-3">For WPF apps, you may want to add UI thread exception handling:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// App.xaml.cs
using Vitalytics.SDK;
using Vitalytics.SDK.Common;

protected override void OnStartup(StartupEventArgs e)
{
    base.OnStartup(e);

    // Initialize Vitalytics
    Vitalytics.Initialize(options);

    // Handle UI thread exceptions (in addition to automatic capture)
    DispatcherUnhandledException += (sender, args) =&gt;
    {
        Vitalytics.Health.ReportError(args.Exception, new Dictionary&lt;string, object&gt;
        {
            ["source"] = "UI Thread",
            ["handled"] = true
        });
        args.Handled = true; // Prevent app crash if desired
    };

    // Handle task exceptions
    TaskScheduler.UnobservedTaskException += (sender, args) =&gt;
    {
        Vitalytics.Health.ReportWarning("Unobserved Task Exception", new Dictionary&lt;string, object&gt;
        {
            ["message"] = args.Exception.Message
        });
        args.SetObserved();
    };
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-heartbeat mr-2 text-red-500"></i>Heartbeat Monitoring
    </h3>
    <p class="text-gray-600 mb-3">Heartbeats help you know your application is running and healthy:</p>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Feature</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-semibold">Automatic heartbeats</td>
                    <td class="px-4 py-2">Sent at configured interval (default: 5 minutes)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold">Startup heartbeat</td>
                    <td class="px-4 py-2">Sent immediately when SDK initializes</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold">Missing heartbeat alerts</td>
                    <td class="px-4 py-2">Dashboard alerts when heartbeats stop</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold">Custom metadata</td>
                    <td class="px-4 py-2">Include memory usage, active users, etc.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-file-alt mr-2 text-blue-500"></i>ILogger Integration (ASP.NET Core)
    </h3>
    <p class="text-gray-600 mb-3">When enabled, Error and Critical level logs are automatically sent to Vitalytics:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// These automatically get sent to Vitalytics Health
_logger.LogError(ex, "Payment failed for order {OrderId}", orderId);
_logger.LogCritical("Database connection lost");

// These don't get sent (below threshold)
_logger.LogWarning("Slow query detected");
_logger.LogInformation("User logged in");

// Change minimum level in options
options.Health.MinimumLogLevel = "Warning"; // Now warnings are sent too</code></pre>
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
                    <td class="px-4 py-2"><code>ReportCrash(exception)</code></td>
                    <td class="px-4 py-2">Unhandled exceptions (auto-captured)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-orange-600">error</td>
                    <td class="px-4 py-2"><code>ReportError(exception/message)</code></td>
                    <td class="px-4 py-2">API failures, handled exceptions</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-yellow-600">warning</td>
                    <td class="px-4 py-2"><code>ReportWarning(message)</code></td>
                    <td class="px-4 py-2">Deprecations, slow operations</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-blue-600">info</td>
                    <td class="px-4 py-2"><code>ReportInfo(message)</code></td>
                    <td class="px-4 py-2">Milestones, successful operations</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-green-600">heartbeat</td>
                    <td class="px-4 py-2"><code>SendHeartbeatAsync()</code></td>
                    <td class="px-4 py-2">Application alive signal</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-800"><strong>Best Practice:</strong> Use <code>ReportError()</code> for caught exceptions you handle, and let the SDK auto-capture unhandled exceptions as crashes.</p>
    </div>
</div>
