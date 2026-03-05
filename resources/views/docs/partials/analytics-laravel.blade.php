<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-laravel mr-2 text-red-500"></i>Laravel Analytics
    </h2>
    <p class="text-gray-600 mb-6">True auto-tracking for Laravel applications. Track all user interactions with zero code changes.</p>

    <div class="bg-green-100 border-2 border-green-500 rounded-xl p-6 mb-6">
        <h3 class="text-green-800 font-bold text-xl mb-3"><i class="fas fa-magic mr-2"></i>True Auto-Tracking</h3>
        <p class="text-green-700 mb-3">SDK v1.0.18+ automatically tracks:</p>
        <ul class="text-green-700 space-y-2">
            <li><i class="fas fa-check-circle mr-2"></i><strong>ALL button clicks</strong> - No data attributes needed</li>
            <li><i class="fas fa-check-circle mr-2"></i><strong>ALL link clicks</strong> - Including navigation and external links</li>
            <li><i class="fas fa-check-circle mr-2"></i><strong>ALL form submissions</strong> - Automatically detected</li>
            <li><i class="fas fa-check-circle mr-2"></i><strong>ALL page views</strong> - Including Livewire/SPA navigation</li>
        </ul>
        <p class="text-green-700 mt-3 font-semibold">Just add the script tag - everything else is automatic!</p>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-rocket mr-2 text-purple-500"></i>Quick Setup (3 Steps)</h3>

    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-4">
        <h4 class="text-purple-800 font-semibold mb-2">Step 1: Install/Update the SDK</h4>
        <div class="bg-gray-900 rounded-lg p-3">
            <pre class="text-gray-100 text-sm"><code># New installation
composer config repositories.vitalytics composer https://your-vitalytics-server.com/composer
composer require vitalytics/laravel-sdk
php artisan vendor:publish --tag=vitalytics-config

# Existing installation - update to v1.0.18+
composer update vitalytics/laravel-sdk
php artisan vendor:publish --tag=vitalytics-assets --force</code></pre>
        </div>
    </div>

    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-4">
        <h4 class="text-purple-800 font-semibold mb-2">Step 2: Add to .env</h4>
        <div class="bg-gray-900 rounded-lg p-3">
            <pre class="text-gray-100 text-sm"><code>VITALYTICS_ANALYTICS_ENABLED=true
VITALYTICS_ANALYTICS_IS_TEST=false</code></pre>
        </div>
    </div>

    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-6">
        <h4 class="text-purple-800 font-semibold mb-2">Step 3: Add tracker to your layout</h4>
        <p class="text-purple-700 text-sm mb-2">Add to your main layout (e.g., <code class="bg-purple-100 px-1 rounded">resources/views/layouts/app.blade.php</code>):</p>
        <div class="bg-gray-900 rounded-lg p-3">
            <pre class="text-gray-100 text-sm"><code>&lt;!-- In &lt;head&gt; section --&gt;
&lt;meta name="csrf-token" content="{&lcub; csrf_token() &rcub;}"&gt;

&lt;!-- Before &lt;/body&gt; --&gt;
&lt;script src="{&lcub; asset('vendor/vitalytics/tracker.js') &rcub;}"&gt;&lt;/script&gt;</code></pre>
        </div>
        <p class="text-purple-700 text-sm mt-2"><i class="fas fa-info-circle mr-1"></i> Laravel Breeze/Jetstream layouts already include the CSRF meta tag.</p>
    </div>

    <div class="bg-green-100 border-2 border-green-500 rounded-xl p-4 mb-6">
        <h4 class="text-green-800 font-bold mb-2"><i class="fas fa-check-circle mr-2"></i>That's It! You're Done!</h4>
        <p class="text-green-700">No other code changes needed. The tracker automatically:</p>
        <ul class="text-green-700 text-sm mt-2 space-y-1">
            <li><i class="fas fa-check mr-2"></i>Detects all buttons, links, and clickable elements</li>
            <li><i class="fas fa-check mr-2"></i>Captures element text/id as the identifier</li>
            <li><i class="fas fa-check mr-2"></i>Detects screen from URL path automatically</li>
            <li><i class="fas fa-check mr-2"></i>Tracks Livewire/Turbo navigation</li>
        </ul>
        <p class="text-green-700 text-sm mt-3 font-semibold">Check your Vitalytics dashboard - events should start appearing immediately!</p>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-shield-alt mr-2 text-red-500"></i>PHI-Safe Mode (Healthcare / HIPAA)</h3>

    <div class="bg-red-50 border-2 border-red-300 rounded-xl p-6 mb-6">
        <h4 class="text-red-800 font-bold mb-3"><i class="fas fa-hospital mr-2"></i>For Healthcare Applications</h4>
        <p class="text-red-700 mb-3">PHI-Safe mode ensures no Protected Health Information (patient names, etc.) is captured in analytics. Enable it with one line:</p>
        <div class="bg-gray-900 rounded-lg p-3 mb-3">
            <pre class="text-gray-100 text-sm"><code># Add to .env
VITALYTICS_ANALYTICS_PHI_SAFE=true</code></pre>
        </div>
        <p class="text-red-700 text-sm mb-3">Then add the meta tag to your layout (in <code class="bg-red-100 px-1 rounded">&lt;head&gt;</code>):</p>
        <div class="bg-gray-900 rounded-lg p-3 mb-3">
            <pre class="text-gray-100 text-sm"><code>&commat;vitalyticsMeta</code></pre>
        </div>
        <p class="text-red-700 text-sm">Or manually: <code class="bg-red-100 px-1 rounded">&lt;meta name="vitalytics-phi-safe" content="true"&gt;</code></p>
    </div>

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">What</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Standard Mode</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">PHI-Safe Mode</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2"><code>&lt;button&gt;Save&lt;/button&gt;</code></td>
                    <td class="px-4 py-2">element: "save", label: "Save"</td>
                    <td class="px-4 py-2">element: "button", label: null</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>&lt;button id="save-btn"&gt;Save John&lt;/button&gt;</code></td>
                    <td class="px-4 py-2">element: "save-btn", label: "Save John"</td>
                    <td class="px-4 py-2 text-green-600 font-semibold">element: "save-btn", label: null</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">Page title: "Edit - John Smith"</td>
                    <td class="px-4 py-2">screenLabel: "Edit - John Smith"</td>
                    <td class="px-4 py-2 text-green-600 font-semibold">screenLabel: null</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">URL query params</td>
                    <td class="px-4 py-2">Included in tracking</td>
                    <td class="px-4 py-2 text-green-600 font-semibold">Stripped (path only)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <h4 class="text-blue-800 font-semibold mb-2"><i class="fas fa-lightbulb mr-2"></i>Best Practice</h4>
        <p class="text-blue-700 text-sm">Use meaningful IDs on your buttons and forms: <code class="bg-blue-100 px-1 rounded">id="save-patient-btn"</code>. In PHI-safe mode, this gives you clear analytics without any risk of capturing patient data. Use the <strong>Label Mappings</strong> feature in the dashboard to add friendly display names.</p>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-shield-alt mr-2 text-yellow-500"></i>User Consent (GDPR/CCPA)</h3>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
        <p class="text-green-800"><strong>Health Data Always Sent:</strong> Health monitoring (errors, crashes, heartbeats) is always active regardless of analytics consent. This is typically exempt from consent requirements as it's necessary for application stability and security.</p>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
        <p class="text-yellow-800"><strong>Analytics Requires Consent:</strong> User behavior tracking (page views, clicks, features) can be disabled by setting <code>VITALYTICS_ANALYTICS_ENABLED=false</code> until consent is granted.</p>
    </div>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code># .env - Health monitoring works even when analytics is disabled
VITALYTICS_ANALYTICS_ENABLED=false   # Disable analytics tracking
VITALYTICS_HEALTH_ENABLED=true       # Health events still sent

# In your code, health reporting always works:
VitalyticsHealth::error('Payment failed', ['order_id' => $id]);

# When user consents, update their preference:
// Session-based consent (doesn't require .env change)
session(['vitalytics_consent' => true]);</code></pre>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-code mr-2 text-indigo-500"></i>Blade Directives</h3>
    <p class="text-gray-600 mb-4">The SDK provides convenient Blade directives:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>&lt;head&gt;
    &lt;meta name="csrf-token" content="{&lcub; csrf_token() &rcub;}"&gt;
    &commat;vitalyticsMeta  {{-- Outputs PHI-safe meta tag if enabled --}}
&lt;/head&gt;
&lt;body&gt;
    ...
    &commat;vitalyticsScripts  {{-- Outputs tracker script if analytics enabled --}}
&lt;/body&gt;</code></pre>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-cog mr-2 text-gray-500"></i>How Auto-Detection Works</h3>

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">What</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Auto-Detected From</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Example</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2"><strong>Element ID</strong></td>
                    <td class="px-4 py-2">id &rarr; name &rarr; text &rarr; aria-label &rarr; class</td>
                    <td class="px-4 py-2"><code>&lt;button id="save-btn"&gt;</code> &rarr; "save-btn"</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><strong>Label</strong></td>
                    <td class="px-4 py-2">Button/link text content</td>
                    <td class="px-4 py-2"><code>&lt;button&gt;Save Patient&lt;/button&gt;</code> &rarr; "Save Patient"</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><strong>Screen</strong></td>
                    <td class="px-4 py-2">URL path (with IDs stripped)</td>
                    <td class="px-4 py-2"><code>/patients/123/edit</code> &rarr; "patients-edit"</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><strong>Screen Label</strong></td>
                    <td class="px-4 py-2">Page title</td>
                    <td class="px-4 py-2"><code>&lt;title&gt;Edit Patient&lt;/title&gt;</code> &rarr; "Edit Patient"</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><strong>Interactive Elements</strong></td>
                    <td class="px-4 py-2">buttons, links, wire:click, @click, role="button"</td>
                    <td class="px-4 py-2">Automatically detected</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-sliders-h mr-2 text-blue-500"></i>Optional: Customize Tracking</h3>
    <p class="text-gray-600 mb-4">While everything works automatically, you can optionally customize specific elements:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>&lt;!-- Override auto-detected element name --&gt;
&lt;button data-vitalytics-click="custom-save-button"&gt;Save&lt;/button&gt;

&lt;!-- Add a friendly label for dashboard display --&gt;
&lt;button data-vitalytics-label="Save Patient Record"&gt;Save&lt;/button&gt;

&lt;!-- Override auto-detected screen --&gt;
&lt;button data-vitalytics-screen="PatientEditor"&gt;Save&lt;/button&gt;

&lt;!-- Mark as feature usage (tracked separately from clicks) --&gt;
&lt;button data-vitalytics-feature="export-pdf"&gt;Export PDF&lt;/button&gt;

&lt;!-- Exclude from tracking --&gt;
&lt;button data-vitalytics-ignore&gt;Don't track this&lt;/button&gt;

&lt;!-- Track modal screen views --&gt;
&lt;div x-show="showModal" data-vitalytics-screen-view="EditPatientModal"&gt;
    &lt;!-- Modal content --&gt;
&lt;/div&gt;

&lt;!-- Add custom properties --&gt;
&lt;button data-vitalytics-props='{"patient_id": 123}'&gt;View&lt;/button&gt;</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-3">Available Data Attributes</h4>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Attribute</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Purpose</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Required?</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2"><code>data-vitalytics-click</code></td>
                    <td class="px-4 py-2">Override auto-detected element ID</td>
                    <td class="px-4 py-2 text-green-600">No - auto-detected</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>data-vitalytics-label</code></td>
                    <td class="px-4 py-2">Override auto-detected label</td>
                    <td class="px-4 py-2 text-green-600">No - auto-detected</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>data-vitalytics-screen</code></td>
                    <td class="px-4 py-2">Override auto-detected screen</td>
                    <td class="px-4 py-2 text-green-600">No - auto-detected</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>data-vitalytics-feature</code></td>
                    <td class="px-4 py-2">Track as feature usage instead of click</td>
                    <td class="px-4 py-2 text-gray-500">Optional</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>data-vitalytics-screen-view</code></td>
                    <td class="px-4 py-2">Track modal/dialog as screen view</td>
                    <td class="px-4 py-2 text-gray-500">Optional (for modals)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>data-vitalytics-ignore</code></td>
                    <td class="px-4 py-2">Exclude element from tracking</td>
                    <td class="px-4 py-2 text-gray-500">Optional</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>data-vitalytics-props</code></td>
                    <td class="px-4 py-2">Add custom properties (JSON)</td>
                    <td class="px-4 py-2 text-gray-500">Optional</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-server mr-2 text-orange-500"></i>Server-Side PHP Tracking (Optional)</h3>
    <p class="text-gray-600 mb-4">For server-side events (API calls, background jobs, etc.), use the PHP SDK:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>use Vitalytics\Facades\VitalyticsAnalytics;

// Track API calls (in middleware)
VitalyticsAnalytics::trackApiCall(
    $request->path(),
    $request->method(),
    $response->status(),
    $durationMs
);

// Track feature usage from PHP
VitalyticsAnalytics::trackFeature('report_generated', [
    'type' => 'monthly',
    'format' => 'pdf',
]);

// Track background jobs
VitalyticsAnalytics::trackJob('ProcessInvoice', 'completed', [
    'invoice_id' => $invoice->id,
]);

// Track custom events
VitalyticsAnalytics::trackEvent('custom_event', 'category', [
    'key' => 'value',
]);</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">API Tracking Middleware</h3>
    <p class="text-gray-600 mb-4">Create middleware to automatically track all API requests:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">app/Http/Middleware/TrackApiCalls.php</div>
        <pre class="text-gray-100 text-sm"><code>&lt;?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Vitalytics\Facades\VitalyticsAnalytics;

class TrackApiCalls
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $durationMs = (int) ((microtime(true) - $start) * 1000);

        VitalyticsAnalytics::trackApiCall(
            $request->path(),
            $request->method(),
            $response->status(),
            $durationMs
        );

        return $response;
    }
}</code></pre>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-comments mr-2 text-amber-500"></i>User Feedback (v1.2.0+)</h3>
    <p class="text-gray-600 mb-4">Collect user feedback directly from your application - bug reports, feature requests, and general feedback:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>use Vitalytics\Facades\VitalyticsAnalytics;

// Submit general feedback
$result = VitalyticsAnalytics::submitFeedback('Love the new dark mode!', [
    'category' => 'praise',         // 'general', 'bug', 'feature-request', 'praise'
    'rating' => 5,                  // 1-5 stars (optional)
    'email' => 'user@example.com',  // For follow-up (optional)
]);

if ($result['success']) {
    session()->flash('success', 'Thank you for your feedback!');
}

// Convenience methods for common categories
VitalyticsAnalytics::submitBugReport('Login button not working', [
    'email' => $user->email,
    'metadata' => ['browser' => $request->userAgent()],
]);

VitalyticsAnalytics::submitFeatureRequest('Add keyboard shortcuts for common actions');

VitalyticsAnalytics::submitPraise('Great application, very intuitive!');</code></pre>
    </div>

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Category</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Value</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Use Case</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-mono text-sm bg-gray-100">general</td>
                    <td class="px-4 py-2"><code>'general'</code></td>
                    <td class="px-4 py-2">Default category for general feedback</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-sm text-red-600">bug</td>
                    <td class="px-4 py-2"><code>'bug'</code></td>
                    <td class="px-4 py-2">Bug reports and issues</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-sm text-purple-600">feature-request</td>
                    <td class="px-4 py-2"><code>'feature-request'</code></td>
                    <td class="px-4 py-2">Feature suggestions and requests</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-sm text-green-600">praise</td>
                    <td class="px-4 py-2"><code>'praise'</code></td>
                    <td class="px-4 py-2">Positive feedback and compliments</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-3">Feedback Form Example</h4>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">app/Http/Controllers/FeedbackController.php</div>
        <pre class="text-gray-100 text-sm"><code>&lt;?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vitalytics\Facades\VitalyticsAnalytics;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'category' => 'required|in:general,bug,feature-request,praise',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $result = VitalyticsAnalytics::submitFeedback($validated['message'], [
            'category' => $validated['category'],
            'rating' => $validated['rating'],
            'email' => auth()->user()?->email,
            'userId' => auth()->id(),
            'screen' => $request->header('referer'),
        ]);

        if ($result['success']) {
            return back()->with('success', 'Thank you for your feedback!');
        }

        return back()->with('error', 'Failed to submit feedback. Please try again.');
    }
}</code></pre>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-bug mr-2 text-red-500"></i>Debugging</h3>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">Open browser console and run:</div>
        <pre class="text-gray-100 text-sm"><code>// Enable debug mode to see all tracked events
VitalyticsTracker.enableDebug();

// Now click around - you'll see logs like:
// [Vitalytics] Page view: patients-edit
// [Vitalytics] Click: save-btn Save Patient
// [Vitalytics] Form submit: patient-form

// Check if tracker is loaded
console.log(VitalyticsTracker);

// Manually track something
VitalyticsTracker.trackClick('test-button', 'TestScreen');</code></pre>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <h4 class="text-blue-800 font-semibold mb-2"><i class="fas fa-info-circle mr-2"></i>Troubleshooting Checklist</h4>
        <ul class="text-blue-700 text-sm space-y-1">
            <li><i class="fas fa-check-square mr-2"></i><code class="bg-blue-100 px-1 rounded">VITALYTICS_ANALYTICS_ENABLED=true</code> in .env</li>
            <li><i class="fas fa-check-square mr-2"></i>CSRF meta tag in <code class="bg-blue-100 px-1 rounded">&lt;head&gt;</code></li>
            <li><i class="fas fa-check-square mr-2"></i>Tracker script before <code class="bg-blue-100 px-1 rounded">&lt;/body&gt;</code></li>
            <li><i class="fas fa-check-square mr-2"></i>Run <code class="bg-blue-100 px-1 rounded">php artisan vendor:publish --tag=vitalytics-assets --force</code> to update tracker</li>
            <li><i class="fas fa-check-square mr-2"></i>Check Network tab for POST requests to <code class="bg-blue-100 px-1 rounded">/vitalytics/track</code></li>
        </ul>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-chart-pie mr-2 text-amber-500"></i>Metrics Tracking (v1.3.0+)</h3>
    <p class="text-gray-600 mb-4">Track quantifiable metrics like AI token usage, API response times, and custom business metrics. View aggregated data in the Metrics Dashboard.</p>

    <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-6 mb-6">
        <h4 class="text-amber-800 font-bold mb-3"><i class="fas fa-robot mr-2"></i>AI Token Usage Tracking</h4>
        <p class="text-amber-700 mb-3">Track token usage and costs from AI providers (OpenAI, Anthropic, etc.):</p>
        <div class="bg-gray-900 rounded-lg p-3">
            <pre class="text-gray-100 text-sm"><code>use Vitalytics\Facades\VitalyticsAnalytics;

// Track AI token usage with cost
VitalyticsAnalytics::trackAiTokens(
    provider: 'anthropic',
    inputTokens: 1500,
    outputTokens: 500,
    options: [
        'model' => 'claude-3-sonnet',
        'cost_cents' => 2.5,   // Cost in cents
        'user_id' => auth()->id(),
    ]
);

// Track OpenAI usage
VitalyticsAnalytics::trackAiTokens(
    provider: 'openai',
    inputTokens: 2000,
    outputTokens: 800,
    options: [
        'model' => 'gpt-4-turbo',
        'cost_cents' => 4.2,
    ]
);</code></pre>
        </div>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-3">Generic Metrics</h4>
    <p class="text-gray-600 mb-4">Track any quantifiable data with aggregation support:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>use Vitalytics\Facades\VitalyticsAnalytics;

// Track custom metrics
VitalyticsAnalytics::trackMetric('api_response_time', [
    'endpoint' => '/api/users',
    'duration_ms' => 245,
], [
    'aggregate' => 'avg',  // sum, avg, min, max, count
    'tags' => ['method' => 'GET', 'status' => '200'],
]);

// Track business metrics
VitalyticsAnalytics::trackMetric('order_value', [
    'amount_cents' => 9999,
    'currency' => 'USD',
], [
    'aggregate' => 'sum',
    'user_id' => $customer->id,
]);

// Track with specific timestamp
VitalyticsAnalytics::trackMetric('batch_processed', [
    'records' => 500,
    'duration_seconds' => 12,
], [
    'timestamp' => now()->subMinutes(5)->toIso8601String(),
]);</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-3">API Call Tracking</h4>
    <p class="text-gray-600 mb-4">Two ways to track API calls:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">Option 1: Track as analytics event (user journey)</div>
        <pre class="text-gray-100 text-sm"><code>use Vitalytics\Facades\VitalyticsAnalytics;

// Track API call as part of user session/journey
VitalyticsAnalytics::trackApiCall(
    endpoint: '/api/users',
    method: 'GET',
    statusCode: 200,
    durationMs: 125
);</code></pre>
    </div>

    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">Option 2: Track as metric (for aggregation/averages)</div>
        <pre class="text-gray-100 text-sm"><code>use Vitalytics\Facades\VitalyticsAnalytics;

// Track API response time as metric (aggregated in Metrics Dashboard)
VitalyticsAnalytics::trackApiCallMetric(
    endpoint: 'stripe/charges',
    durationMs: 342,
    options: [
        'method' => 'POST',
        'status_code' => 200,
    ]
);

// Example: Track outgoing HTTP requests
$start = microtime(true);
$response = Http::post('https://api.stripe.com/v1/charges', $data);
$durationMs = (int) ((microtime(true) - $start) * 1000);

VitalyticsAnalytics::trackApiCallMetric(
    'stripe/charges',
    $durationMs,
    ['method' => 'POST', 'status_code' => $response->status()]
);</code></pre>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <h4 class="text-blue-800 font-semibold mb-2"><i class="fas fa-chart-bar mr-2"></i>Metrics Dashboard</h4>
        <p class="text-blue-700 text-sm">View aggregated metrics in the Vitalytics dashboard under <strong>Analytics &rarr; Metrics</strong>. See daily trends, cost breakdowns, and per-app usage statistics.</p>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>Queue Failure Monitoring</h3>
    <p class="text-gray-600 mb-4">Automatically track failed queue jobs using Laravel's built-in event system:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <div class="text-sm text-gray-400 mb-2">app/Providers/AppServiceProvider.php</div>
        <pre class="text-gray-100 text-sm"><code>&lt;?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use Vitalytics\Facades\VitalyticsHealth;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Track all failed queue jobs
        Queue::failing(function (JobFailed $event) {
            VitalyticsHealth::error(
                message: "Queue job failed: " . $event->job->resolveName(),
                context: [
                    'job' => $event->job->resolveName(),
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                    'exception' => $event->exception->getMessage(),
                    'trace' => $event->exception->getTraceAsString(),
                ]
            );
        });
    }
}</code></pre>
    </div>

    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <h4 class="text-green-800 font-semibold mb-2"><i class="fas fa-check-circle mr-2"></i>What Gets Tracked</h4>
        <ul class="text-green-700 text-sm space-y-1">
            <li><i class="fas fa-check mr-2"></i>Job class name (e.g., <code class="bg-green-100 px-1 rounded">App\Jobs\ProcessOrder</code>)</li>
            <li><i class="fas fa-check mr-2"></i>Queue connection (redis, database, etc.)</li>
            <li><i class="fas fa-check mr-2"></i>Queue name (default, high, etc.)</li>
            <li><i class="fas fa-check mr-2"></i>Exception message and stack trace</li>
        </ul>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-3">Advanced: Track Job Completion Times</h4>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Vitalytics\Facades\VitalyticsAnalytics;

// In AppServiceProvider::boot()

// Track when jobs start
Queue::before(function (JobProcessing $event) {
    cache()->put(
        "job_start_{$event->job->getJobId()}",
        microtime(true),
        60
    );
});

// Track when jobs complete (with duration)
Queue::after(function (JobProcessed $event) {
    $startTime = cache()->pull("job_start_{$event->job->getJobId()}");
    if ($startTime) {
        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        VitalyticsAnalytics::trackMetric('queue_job_duration', [
            'job' => $event->job->resolveName(),
            'duration_ms' => $durationMs,
            'queue' => $event->job->getQueue(),
        ], [
            'aggregate' => 'avg',
        ]);
    }
});</code></pre>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-clock mr-2 text-gray-500"></i>Session Tracking</h3>
    <p class="text-gray-600 mb-4">Sessions are tracked automatically to enable user journey analysis.</p>

    <div class="bg-gray-50 border-l-4 border-gray-400 p-4 mb-6">
        <h4 class="text-gray-800 font-semibold mb-2">Session Behavior</h4>
        <ul class="text-gray-700 text-sm space-y-1">
            <li><strong>Default timeout:</strong> 30 minutes of inactivity</li>
            <li><strong>Continuous work:</strong> A user working for 2 hours has ONE session</li>
            <li><strong>Configure:</strong> <code class="bg-gray-200 px-1 rounded">VITALYTICS_SESSION_TIMEOUT_MINUTES=15</code></li>
        </ul>
    </div>

    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-6">
        <h4 class="text-purple-800 font-semibold mb-2"><i class="fas fa-route mr-2"></i>Session Explorer</h4>
        <p class="text-purple-700 text-sm">View complete user journeys in the Vitalytics dashboard under <strong>Analytics &rarr; Sessions</strong>. See every page visited, button clicked, and form submitted in sequence.</p>
    </div>

    <hr class="my-8 border-gray-200">

    <h3 class="text-xl font-semibold text-gray-900 mb-3"><i class="fas fa-cog mr-2 text-gray-500"></i>Configuration Reference</h3>
    <p class="text-gray-600 mb-4">All available environment variables:</p>

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Variable</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Default</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_API_KEY</td>
                    <td class="px-4 py-2">-</td>
                    <td class="px-4 py-2">Your API key from the dashboard</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_APP_IDENTIFIER</td>
                    <td class="px-4 py-2">-</td>
                    <td class="px-4 py-2">Your app identifier (e.g., myapp-web)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_ANALYTICS_ENABLED</td>
                    <td class="px-4 py-2">true</td>
                    <td class="px-4 py-2">Enable/disable analytics tracking</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_ANALYTICS_IS_TEST</td>
                    <td class="px-4 py-2">false</td>
                    <td class="px-4 py-2">Mark all events as test data</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_ANALYTICS_PHI_SAFE</td>
                    <td class="px-4 py-2">false</td>
                    <td class="px-4 py-2">Enable PHI-safe mode (HIPAA)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_METRICS_ENABLED</td>
                    <td class="px-4 py-2">true</td>
                    <td class="px-4 py-2">Enable/disable metrics tracking</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_METRICS_IS_TEST</td>
                    <td class="px-4 py-2">false</td>
                    <td class="px-4 py-2">Mark all metrics as test data</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_HEALTH_ENABLED</td>
                    <td class="px-4 py-2">true</td>
                    <td class="px-4 py-2">Enable/disable health monitoring</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">VITALYTICS_SESSION_TIMEOUT_MINUTES</td>
                    <td class="px-4 py-2">30</td>
                    <td class="px-4 py-2">Session inactivity timeout</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
