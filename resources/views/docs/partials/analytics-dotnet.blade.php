<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-microsoft mr-2 text-blue-600"></i>.NET SDK
    </h2>
    <p class="text-gray-600 mb-6">Complete health monitoring and analytics tracking for .NET applications including ASP.NET Core, WPF, WinForms, MAUI, and console apps.</p>

    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <p class="text-green-800"><strong>New NuGet Package:</strong> The Vitalytics .NET SDK is now available as a NuGet package with auto-tracking support for ASP.NET Core applications.</p>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Installation</h3>
    <p class="text-gray-600 mb-3">Add the Vitalytics NuGet feed to your project:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>&lt;!-- nuget.config in your solution root --&gt;
&lt;?xml version="1.0" encoding="utf-8"?&gt;
&lt;configuration&gt;
  &lt;packageSources&gt;
    &lt;add key="nuget.org" value="https://api.nuget.org/v3/index.json" /&gt;
    &lt;add key="Vitalytics" value="https://your-vitalytics-server.com/nuget/v3/index.json" /&gt;
  &lt;/packageSources&gt;
&lt;/configuration&gt;</code></pre>
    </div>

    <p class="text-gray-600 mb-3">Install the package:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code># For ASP.NET Core applications (includes auto-tracking)
dotnet add package Vitalytics.SDK.AspNetCore

# For desktop/console applications (WPF, WinForms, MAUI, Console)
dotnet add package Vitalytics.SDK</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-server mr-2 text-indigo-500"></i>ASP.NET Core Quick Start
    </h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Program.cs
var builder = WebApplication.CreateBuilder(args);

// Add Vitalytics with full configuration
builder.Services.AddVitalytics(options =&gt;
{
    options.AppId = "{{ $product->identifier ?? 'your-app-id' }}";
    options.AppSecret = "your-app-secret";

    // Health Monitoring
    options.Health.EnableHeartbeat = true;
    options.Health.HeartbeatInterval = TimeSpan.FromMinutes(5);
    options.Health.CaptureUnhandledExceptions = true;
    options.Health.IntegrateWithILogger = true;

    // Analytics Tracking
    options.Analytics.AutoTrackRequests = true;
    options.Analytics.AutoTrackUserIdentity = true;
});

// Optional: Add ILogger integration (auto-sends Error/Critical logs)
builder.Logging.AddVitalytics();

var app = builder.Build();

// Add Vitalytics middleware (must be early in pipeline)
app.UseVitalytics();

// ... rest of your middleware
app.Run();</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-desktop mr-2 text-purple-500"></i>Desktop Application Quick Start (WPF/WinForms)
    </h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>using Vitalytics.SDK;
using Vitalytics.SDK.Common;

// Initialize at application startup (App.xaml.cs or Program.cs)
Vitalytics.Initialize(new VitalyticsOptions
{
    AppId = "{{ $product->identifier ?? 'myapp-ambient' }}",
    AppSecret = "your-secret",

    Health = new HealthOptions
    {
        EnableHeartbeat = true,
        HeartbeatInterval = TimeSpan.FromMinutes(5),
        CaptureUnhandledExceptions = true
    },

    Analytics = new AnalyticsOptions
    {
        Enabled = true,
        SessionTimeout = TimeSpan.FromMinutes(30)
    }
});

// Track screen views
Vitalytics.Analytics.TrackScreen("MainWindow", "Main Window");

// Track button clicks
Vitalytics.Analytics.TrackClick("start_recording_btn", "Start Recording");

// Track feature usage
Vitalytics.Analytics.TrackFeature("voice_transcription", properties: new Dictionary&lt;string, object&gt;
{
    ["duration"] = 120,
    ["language"] = "en-US"
});

// Report errors manually
try
{
    // Your code
}
catch (Exception ex)
{
    Vitalytics.Health.ReportError(ex, new Dictionary&lt;string, object&gt;
    {
        ["context"] = "processing_audio"
    });
}

// Flush events before shutdown
await Vitalytics.FlushAsync();
Vitalytics.Shutdown();</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-shield-alt mr-2 text-yellow-500"></i>Analytics Collection Modes (v1.2.0+)
    </h3>

    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-4">
        <p class="text-purple-800"><strong>New in v1.2.0:</strong> Choose between <strong>Standard Analytics</strong> (with device ID) and <strong>Privacy Mode</strong> (no device ID). Use consent-with-fallback to always collect analytics while respecting user choice.</p>
    </div>

    <div class="overflow-x-auto mb-4">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Mode</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Device ID</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Cross-Session</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Consent</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr><td class="px-4 py-2 font-semibold text-purple-600">Standard Analytics</td><td class="px-4 py-2">Yes</td><td class="px-4 py-2">Yes</td><td class="px-4 py-2">Usually required</td></tr>
                <tr><td class="px-4 py-2 font-semibold text-gray-600">Privacy Mode</td><td class="px-4 py-2">No</td><td class="px-4 py-2">No</td><td class="px-4 py-2">Usually not required</td></tr>
            </tbody>
        </table>
    </div>

    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
        <p class="text-green-800"><strong>Health Data Always Sent:</strong> Health monitoring (errors, crashes, heartbeats) is always active regardless of analytics consent.</p>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Recommended: Consent with Fallback</h4>
    <p class="text-gray-600 mb-3">Analytics are <strong>always collected</strong> - user consent just determines Standard vs Privacy mode:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Initialize with consent-with-fallback
Vitalytics.Initialize(new VitalyticsOptions
{
    AppId = "your-app-id",
    AppSecret = "your-secret",
    Analytics = new AnalyticsOptions
    {
        ConsentMode = ConsentMode.StandardWithPrivacyFallback
    }
});

// When user makes their choice:
Vitalytics.Analytics.SetConsent(true);   // → Standard Analytics (with device ID)
Vitalytics.Analytics.SetConsent(false);  // → Privacy Mode (still tracks, no device ID!)

// Check current mode
if (Vitalytics.Analytics.IsStandardMode)
    Console.WriteLine("Using Standard Analytics");
else if (Vitalytics.Analytics.IsPrivacyMode)
    Console.WriteLine("Using Privacy Mode");

// Tracking works in BOTH modes!
Vitalytics.Analytics.TrackScreen("MainWindow");</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Complete Desktop Consent Flow</h4>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// App.xaml.cs
protected override void OnStartup(StartupEventArgs e)
{
    base.OnStartup(e);

    Vitalytics.Initialize(new VitalyticsOptions
    {
        AppId = "your-app-id",
        AppSecret = Environment.GetEnvironmentVariable("VITALYTICS_SECRET")!,
        Analytics = new AnalyticsOptions
        {
            ConsentMode = ConsentMode.StandardWithPrivacyFallback
        }
    });

    // Check for saved preference
    var savedConsent = Properties["analytics_consent"] as bool?;

    if (savedConsent.HasValue)
    {
        // Apply saved preference
        Vitalytics.Analytics.SetConsent(savedConsent.Value);
    }
    else
    {
        // First run - show consent dialog
        var dialog = new ConsentDialog();
        if (dialog.ShowDialog() == true)
        {
            Properties["analytics_consent"] = dialog.UserAccepted;
            Vitalytics.Analytics.SetConsent(dialog.UserAccepted);
        }
    }

    // Analytics work immediately - even before consent dialog!
    // (defaults to Privacy Mode until SetConsent is called)
    Vitalytics.Analytics.TrackScreen("Startup");
}</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Alternative: Direct Mode Selection</h4>
    <p class="text-gray-600 mb-3">For internal tools or when consent isn't needed:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Always use Privacy Mode (no device ID, no consent needed)
Analytics = new AnalyticsOptions
{
    CollectionMode = DataCollectionMode.Anonymous
}

// Always use Standard Analytics (assumes consent)
Analytics = new AnalyticsOptions
{
    CollectionMode = DataCollectionMode.Identified
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-hospital mr-2 text-red-500"></i>PHI-Safe Mode (HIPAA Compliance)
    </h3>
    <p class="text-gray-600 mb-3">For healthcare applications, enable PHI-Safe mode to automatically exclude potentially sensitive data:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>options.Analytics.PhiSafeMode = true;</code></pre>
    </div>
    <p class="text-gray-600 mb-6">This will automatically exclude: user IDs, email, phone, address, SSN, DOB, name, patient, MRN from properties and query strings.</p>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-tag mr-2 text-purple-500"></i>Friendly Labels
    </h3>
    <p class="text-gray-600 mb-3">Add human-readable labels that appear in the Vitalytics dashboard:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Screen with label
Vitalytics.Analytics.TrackScreen("PatientDetailWindow", "Patient Details");

// Click with element label
Vitalytics.Analytics.TrackClick("btn_save", "Save Patient", screen: "PatientDetails");

// Feature with properties
Vitalytics.Analytics.TrackFeature("voice_transcription", category: "ai",
    properties: new Dictionary&lt;string, object&gt;
    {
        ["duration_seconds"] = 120,
        ["language"] = "en-US"
    });</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-heartbeat mr-2 text-red-500"></i>Health Monitoring
    </h3>
    <p class="text-gray-600 mb-3">The SDK automatically captures unhandled exceptions and sends periodic heartbeats. You can also report errors manually:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Report an error with exception
try
{
    await ProcessAudioAsync(audioFile);
}
catch (AudioProcessingException ex)
{
    Vitalytics.Health.ReportError(ex, new Dictionary&lt;string, object&gt;
    {
        ["file_size"] = audioFile.Length,
        ["format"] = audioFile.Format
    });
    throw;
}

// Report a warning
Vitalytics.Health.ReportWarning("Slow API response detected", new Dictionary&lt;string, object&gt;
{
    ["endpoint"] = "/api/transcribe",
    ["duration_ms"] = 5000
});

// Report informational event
Vitalytics.Health.ReportInfo("User completed onboarding", new Dictionary&lt;string, object&gt;
{
    ["steps_completed"] = 5
});</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-comments mr-2 text-amber-500"></i>User Feedback (v1.4.0+)
    </h3>
    <p class="text-gray-600 mb-3">Collect user feedback directly from your application - bug reports, feature requests, and general feedback:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Submit general feedback
var result = await Vitalytics.Feedback.SubmitAsync("Love the new dark mode!", new FeedbackSubmitOptions
{
    Category = FeedbackCategory.Praise,
    Rating = 5,
    Email = "user@example.com"
});

if (result.Success)
{
    Console.WriteLine("Feedback submitted!");
}

// Convenience methods for common categories
await Vitalytics.Feedback.SubmitBugReportAsync("Login button not working", new FeedbackSubmitOptions
{
    Email = "user@example.com",
    Screen = "LoginScreen",
    Metadata = new Dictionary&lt;string, object&gt;
    {
        ["os_version"] = Environment.OSVersion.ToString()
    }
});

await Vitalytics.Feedback.SubmitFeatureRequestAsync("Add keyboard shortcuts for common actions");

await Vitalytics.Feedback.SubmitPraiseAsync("Great application, very intuitive!");</code></pre>
    </div>

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Category</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Enum Value</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Use Case</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-mono text-sm bg-gray-100">General</td>
                    <td class="px-4 py-2"><code>FeedbackCategory.General</code></td>
                    <td class="px-4 py-2">Default category for general feedback</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-sm text-red-600">Bug</td>
                    <td class="px-4 py-2"><code>FeedbackCategory.Bug</code></td>
                    <td class="px-4 py-2">Bug reports and issues</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-sm text-purple-600">FeatureRequest</td>
                    <td class="px-4 py-2"><code>FeedbackCategory.FeatureRequest</code></td>
                    <td class="px-4 py-2">Feature suggestions and requests</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-sm text-green-600">Praise</td>
                    <td class="px-4 py-2"><code>FeedbackCategory.Praise</code></td>
                    <td class="px-4 py-2">Positive feedback and compliments</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-syringe mr-2 text-green-500"></i>Dependency Injection
    </h3>
    <p class="text-gray-600 mb-3">Inject the client into your services:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>public class TranscriptionService
{
    private readonly IVitalyticsClient _vitalytics;

    public TranscriptionService(IVitalyticsClient vitalytics)
    {
        _vitalytics = vitalytics;
    }

    public async Task&lt;string&gt; TranscribeAsync(AudioFile audio)
    {
        var stopwatch = Stopwatch.StartNew();

        try
        {
            var result = await _transcriptionEngine.ProcessAsync(audio);

            _vitalytics.Analytics.TrackFeature("transcription", properties: new Dictionary&lt;string, object&gt;
            {
                ["duration_ms"] = stopwatch.ElapsedMilliseconds,
                ["audio_length"] = audio.Duration,
                ["word_count"] = result.WordCount
            });

            return result.Text;
        }
        catch (Exception ex)
        {
            _vitalytics.Health.ReportError(ex, new Dictionary&lt;string, object&gt;
            {
                ["audio_format"] = audio.Format
            });
            throw;
        }
    }
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">WPF Integration Example</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// App.xaml.cs
using Vitalytics.SDK;
using Vitalytics.SDK.Common;

public partial class App : Application
{
    protected override void OnStartup(StartupEventArgs e)
    {
        base.OnStartup(e);

        Vitalytics.Initialize(new VitalyticsOptions
        {
            AppId = "myapp-ambient",
            AppSecret = Environment.GetEnvironmentVariable("VITALYTICS_SECRET") ?? "dev-secret",
            AppVersion = Assembly.GetExecutingAssembly().GetName().Version?.ToString()
        });
    }

    protected override void OnExit(ExitEventArgs e)
    {
        // Ensure all events are sent before exit
        Vitalytics.FlushAsync().Wait(TimeSpan.FromSeconds(5));
        Vitalytics.Shutdown();
        base.OnExit(e);
    }
}

// MainWindow.xaml.cs
public partial class MainWindow : Window
{
    private void Window_Loaded(object sender, RoutedEventArgs e)
    {
        Vitalytics.Analytics.TrackScreen("MainWindow", "Main Window");
    }

    private void StartRecording_Click(object sender, RoutedEventArgs e)
    {
        Vitalytics.Analytics.TrackClick("start_recording", "Start Recording");
        StartRecording();
    }
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Configuration Reference</h3>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Option</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Default</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr><td class="px-4 py-2 font-mono">AppId</td><td class="px-4 py-2">Required</td><td class="px-4 py-2">Your application identifier</td></tr>
                <tr><td class="px-4 py-2 font-mono">AppSecret</td><td class="px-4 py-2">Required</td><td class="px-4 py-2">Your application secret</td></tr>
                <tr><td class="px-4 py-2 font-mono">BaseUrl</td><td class="px-4 py-2">your-vitalytics-server.com</td><td class="px-4 py-2">Vitalytics API URL</td></tr>
                <tr><td class="px-4 py-2 font-mono">Health.EnableHeartbeat</td><td class="px-4 py-2">true</td><td class="px-4 py-2">Send periodic heartbeats</td></tr>
                <tr><td class="px-4 py-2 font-mono">Health.HeartbeatInterval</td><td class="px-4 py-2">5 minutes</td><td class="px-4 py-2">Heartbeat frequency</td></tr>
                <tr><td class="px-4 py-2 font-mono">Health.CaptureUnhandledExceptions</td><td class="px-4 py-2">true</td><td class="px-4 py-2">Auto-capture crashes</td></tr>
                <tr><td class="px-4 py-2 font-mono">Health.IntegrateWithILogger</td><td class="px-4 py-2">true</td><td class="px-4 py-2">Capture Error/Critical logs</td></tr>
                <tr><td class="px-4 py-2 font-mono">Analytics.AutoTrackRequests</td><td class="px-4 py-2">true</td><td class="px-4 py-2">Auto-track HTTP requests</td></tr>
                <tr><td class="px-4 py-2 font-mono">Analytics.ConsentMode</td><td class="px-4 py-2">None</td><td class="px-4 py-2">Consent handling: None or StandardWithPrivacyFallback</td></tr>
                <tr><td class="px-4 py-2 font-mono">Analytics.CollectionMode</td><td class="px-4 py-2">Anonymous</td><td class="px-4 py-2">Direct mode: Anonymous (Privacy) or Identified (Standard)</td></tr>
                <tr><td class="px-4 py-2 font-mono">Analytics.PhiSafeMode</td><td class="px-4 py-2">false</td><td class="px-4 py-2">HIPAA-compliant mode</td></tr>
                <tr><td class="px-4 py-2 font-mono">Analytics.SessionTimeout</td><td class="px-4 py-2">30 minutes</td><td class="px-4 py-2">Session idle timeout</td></tr>
            </tbody>
        </table>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-800"><strong>Pro Tip:</strong> Store your AppSecret in environment variables or a secure configuration provider, never hardcode it in source files.</p>
    </div>
</div>
