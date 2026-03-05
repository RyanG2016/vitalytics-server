<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-js mr-2 text-yellow-500"></i>JavaScript / Chrome Extension SDK
    </h2>
    <p class="text-gray-600 mb-6">Universal SDK combining Health Monitoring + Analytics for websites and Chrome extensions.</p>

    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <p class="text-green-800"><strong>One SDK, Everything Included:</strong> Health monitoring (errors, crashes, heartbeats) AND analytics (page views, clicks, features) in a single script. Auto-detects Chrome extension vs website.</p>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>&lt;script src="https://your-vitalytics-server.com/sdk/vitalytics.js"&gt;&lt;/script&gt;
&lt;script&gt;
    Vitalytics.init({
        apiKey: 'your-api-key',
        appIdentifier: 'your-app-identifier'
    });
&lt;/script&gt;</code></pre>
    </div>

    <p class="text-gray-600 mb-6">That's it! The SDK automatically:</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2"><i class="fas fa-heartbeat mr-2"></i>Health Monitoring</h4>
            <ul class="text-blue-800 text-sm space-y-1">
                <li>Captures JavaScript errors automatically</li>
                <li>Captures unhandled promise rejections</li>
                <li>Optional heartbeat monitoring</li>
            </ul>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
            <h4 class="font-semibold text-purple-900 mb-2"><i class="fas fa-chart-line mr-2"></i>Analytics</h4>
            <ul class="text-purple-800 text-sm space-y-1">
                <li>All button and link clicks</li>
                <li>All form submissions</li>
                <li>Page views (including SPA navigation)</li>
            </ul>
        </div>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Configuration Options</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>Vitalytics.init({
    // Required
    apiKey: 'your-api-key',
    appIdentifier: 'your-app-identifier',

    // Optional - General
    enabled: true,              // Enable/disable all tracking (default: true)
    isTest: false,              // Mark events as test data (default: false)
    environment: 'production',  // Environment name (default: 'production')
    appVersion: '1.0.0',        // Your app version
    userId: null,               // Current user ID
    debug: false,               // Console logging (default: false)

    // Optional - Health (always active regardless of analytics consent)
    captureErrors: true,        // Auto-capture JS errors (default: true)
    heartbeatInterval: 0,       // Heartbeat interval in ms, 0=disabled (default: 0)

    // Optional - Analytics (can be disabled for consent)
    autoTrack: true,            // Auto-track clicks/forms/pages (default: true)
    phiSafe: false,             // PHI-safe mode for HIPAA (default: false)

    // Optional - Performance
    batchSize: 20,              // Events per batch (default: 20)
    flushInterval: 30000        // Flush interval in ms (default: 30000)
});</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-shield-alt mr-2 text-yellow-500"></i>User Consent (GDPR/CCPA)
    </h3>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
        <p class="text-green-800"><strong>Health Data Always Sent:</strong> Health monitoring (errors, crashes, heartbeats) is always active regardless of analytics consent. This is typically exempt from consent requirements as it's necessary for application stability and security.</p>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
        <p class="text-yellow-800"><strong>Analytics Requires Consent:</strong> User behavior tracking (page views, clicks, features) can be disabled until consent is granted.</p>
    </div>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Initialize with analytics disabled until consent
Vitalytics.init({
    apiKey: 'your-api-key',
    appIdentifier: 'your-app',
    autoTrack: false    // Disable analytics auto-tracking
});

// Health monitoring works immediately (errors, crashes)
// This is sent regardless of consent:
Vitalytics.error('API failed', { endpoint: '/users' });

// When user grants consent, enable analytics:
Vitalytics.setEnabled(true);
Vitalytics.trackScreen('Dashboard');  // Now analytics works</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fab fa-chrome mr-2 text-blue-500"></i>Chrome Extension Setup
    </h3>

    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
        <p class="text-red-800"><strong>Important - CSP Requirement:</strong> Chrome extensions block external scripts due to Content Security Policy. You must download the SDK and include it locally in your extension.</p>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Step 1: Download the SDK</h4>
    <p class="text-gray-600 mb-3">Download the SDK file and add it to your extension folder:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code># Download via curl
curl -o vitalytics.js https://your-vitalytics-server.com/sdk/vitalytics.js

# Or download directly in browser:
# https://your-vitalytics-server.com/sdk/vitalytics.js</code></pre>
    </div>

    <p class="text-gray-600 mb-3">Your extension folder structure should look like:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>my-extension/
├── manifest.json
├── popup.html
├── popup.js
├── background.js
└── lib/
    └── vitalytics.js    &lt;-- SDK file here</code></pre>
    </div>

    <div class="flex gap-2 mb-6">
        <a href="/sdk/vitalytics.js" download="vitalytics.js" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-download mr-2"></i> Download vitalytics.js
        </a>
        <a href="/sdk/vitalytics.js" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
            <i class="fas fa-external-link-alt mr-2"></i> View Source
        </a>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Step 2: Include in popup.html</h4>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;My Extension&lt;/title&gt;
    &lt;script src="lib/vitalytics.js"&gt;&lt;/script&gt;  &lt;!-- Local file --&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;button id="settings-btn"&gt;Settings&lt;/button&gt;
    &lt;button id="sync-btn"&gt;Sync Now&lt;/button&gt;

    &lt;script&gt;
        Vitalytics.init({
            apiKey: 'your-api-key',
            appIdentifier: 'myapp-chrome',
            appVersion: chrome.runtime.getManifest().version
        });

        // Track popup opened
        Vitalytics.trackPopupOpened();
    &lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Step 3: Include in background.js (Service Worker)</h4>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// background.js (Manifest V3 service worker)
importScripts('lib/vitalytics.js');  // Local file

Vitalytics.init({
    apiKey: 'your-api-key',
    appIdentifier: 'myapp-chrome',
    appVersion: chrome.runtime.getManifest().version,
    autoTrack: false,      // No DOM in service worker
    captureErrors: true    // Still capture errors
});

// Track extension lifecycle
chrome.runtime.onInstalled.addListener((details) => {
    if (details.reason === 'install') {
        Vitalytics.trackInstalled();
    } else if (details.reason === 'update') {
        Vitalytics.trackUpdated(details.previousVersion);
    }
});

// Track background events
chrome.alarms.onAlarm.addListener((alarm) => {
    Vitalytics.trackEvent('alarm_triggered', 'background', { alarm: alarm.name });
});

// Manual error reporting
try {
    // risky operation
} catch (e) {
    Vitalytics.error('Sync failed', {
        message: e.message,
        stack: e.stack
    });
}</code></pre>
    </div>

    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <p class="text-yellow-800"><strong>Updating the SDK:</strong> When a new SDK version is released, download the updated file and replace <code>lib/vitalytics.js</code> in your extension, then publish a new version of your extension.</p>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Test Data Mode</h4>
    <p class="text-gray-600 mb-3">Mark events as test data during development so they can be filtered out in the dashboard:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Option 1: Config file approach (recommended)
// config.js - change isTest when switching environments
const CONFIG = {
    apiKey: 'your-api-key',
    appIdentifier: 'myapp-chrome',
    isTest: true   // Set to false for production
};

// popup.js
Vitalytics.init(CONFIG);</code></pre>
    </div>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Option 2: Auto-detect unpacked extension (development mode)
const isDev = !('update_url' in chrome.runtime.getManifest());

Vitalytics.init({
    apiKey: 'your-api-key',
    appIdentifier: 'myapp-chrome',
    isTest: isDev   // Unpacked extensions don't have update_url
});</code></pre>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-800"><strong>Note:</strong> Test mode is set at initialization. To switch between test and production mode, update your config and reload the extension. This is the recommended approach for Chrome extensions.</p>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-heartbeat mr-2 text-red-500"></i>Health Monitoring API
    </h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Report a crash (fatal error) - flushes immediately
Vitalytics.crash('Application crashed', {
    component: 'DataSync',
    lastAction: 'fetch_users'
});

// Report an error
Vitalytics.error('API request failed', {
    endpoint: '/api/users',
    status: 500,
    stack: error.stack  // Stack traces are parsed automatically
});

// Report a warning
Vitalytics.warning('Slow API response', {
    endpoint: '/api/data',
    duration: 3500
});

// Report info
Vitalytics.info('User logged in', {
    method: 'oauth',
    provider: 'google'
});

// Send heartbeat (usually configured via heartbeatInterval)
Vitalytics.heartbeat({ status: 'healthy' });

// Enable heartbeats every 5 minutes
Vitalytics.init({
    apiKey: 'your-api-key',
    appIdentifier: 'myapp-chrome',
    heartbeatInterval: 300000  // 5 minutes
});

// Stop heartbeats
Vitalytics.stopHeartbeat();</code></pre>
    </div>

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Level</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Method</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Use Case</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-mono text-red-600">crash</td>
                    <td class="px-4 py-2"><code>crash(message, context)</code></td>
                    <td class="px-4 py-2">Unhandled exceptions, fatal errors (flushes immediately)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-orange-600">error</td>
                    <td class="px-4 py-2"><code>error(message, context)</code></td>
                    <td class="px-4 py-2">API failures, validation errors, caught exceptions</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-yellow-600">warning</td>
                    <td class="px-4 py-2"><code>warning(message, context)</code></td>
                    <td class="px-4 py-2">Deprecations, slow operations, retry attempts</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-blue-600">info</td>
                    <td class="px-4 py-2"><code>info(message, context)</code></td>
                    <td class="px-4 py-2">Successful operations, milestones, state changes</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-green-600">heartbeat</td>
                    <td class="px-4 py-2"><code>heartbeat(context)</code></td>
                    <td class="px-4 py-2">Liveness signal, uptime monitoring</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-chart-line mr-2 text-purple-500"></i>Analytics API
    </h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Track screen/page view
Vitalytics.trackScreen('Dashboard', { user_type: 'admin' });

// Track button click
Vitalytics.trackClick('export-btn', { format: 'pdf' });

// Track feature usage
Vitalytics.trackFeature('dark-mode', { enabled: true });

// Track form submission
Vitalytics.trackForm('contact-form', { source: 'footer' });

// Track custom event
Vitalytics.trackEvent('video_played', 'media', {
    video_id: '123',
    duration: 120
});

// Set screen context without tracking
Vitalytics.setScreen('SettingsPage', 'Settings');

// Set current user
Vitalytics.setUserId('user-123');</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-comments mr-2 text-amber-500"></i>User Feedback API
    </h3>
    <p class="text-gray-600 mb-3">Collect user feedback directly from your app - bug reports, feature requests, and general feedback.</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Submit general feedback
Vitalytics.submitFeedback('Love the new dark mode!', {
    category: 'praise',     // 'general', 'bug', 'feature-request', 'praise'
    rating: 5,              // 1-5 stars (optional)
    email: 'user@example.com'  // For follow-up (optional)
}).then(result => {
    if (result.success) {
        console.log('Feedback submitted!');
    }
});

// Convenience methods
Vitalytics.submitBugReport('Login button not working on mobile', {
    email: 'user@example.com',
    metadata: { browser: navigator.userAgent }
});

Vitalytics.submitFeatureRequest('Add keyboard shortcuts for common actions');

// Feedback with metadata and rating
Vitalytics.submitFeedback('Great app overall', {
    category: 'general',
    rating: 4,
    metadata: { feature: 'search', usage: 'daily' }
});</code></pre>
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

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-tag mr-2 text-indigo-500"></i>Data Attributes (Auto-Tracking)
    </h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>&lt;!-- Custom element ID and label --&gt;
&lt;button data-vitalytics-click="save-settings"
        data-vitalytics-label="Save Settings"&gt;
    Save
&lt;/button&gt;

&lt;!-- Feature tracking --&gt;
&lt;input type="checkbox"
       data-vitalytics-feature="dark-mode"
       data-vitalytics-label="Dark Mode Toggle" /&gt;

&lt;!-- Form tracking --&gt;
&lt;form data-vitalytics-form="contact-form"
      data-vitalytics-label="Contact Form"&gt;
    ...
&lt;/form&gt;

&lt;!-- Exclude from tracking --&gt;
&lt;button data-vitalytics-ignore&gt;Don't Track This&lt;/button&gt;

&lt;!-- Custom properties --&gt;
&lt;button data-vitalytics-click="upgrade"
        data-vitalytics-props='{"plan": "pro", "source": "banner"}'&gt;
    Upgrade
&lt;/button&gt;</code></pre>
    </div>

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Attribute</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Purpose</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-click</td><td class="px-4 py-2">Override click element ID</td></tr>
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-feature</td><td class="px-4 py-2">Mark as feature usage</td></tr>
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-form</td><td class="px-4 py-2">Override form ID</td></tr>
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-label</td><td class="px-4 py-2">Friendly element name</td></tr>
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-screen</td><td class="px-4 py-2">Set screen context</td></tr>
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-screen-label</td><td class="px-4 py-2">Friendly screen name</td></tr>
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-screen-view</td><td class="px-4 py-2">Track when visible (modals)</td></tr>
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-props</td><td class="px-4 py-2">Custom properties (JSON)</td></tr>
                <tr><td class="px-4 py-2 font-mono text-sm">data-vitalytics-ignore</td><td class="px-4 py-2">Exclude from tracking</td></tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-shield-alt mr-2 text-red-500"></i>PHI-Safe Mode (HIPAA)
    </h3>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
        <p class="text-red-800"><strong>Healthcare Apps:</strong> PHI-safe mode ensures no patient data is captured. Element text, page titles, and full URLs are never collected.</p>
    </div>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Enable via config
Vitalytics.init({
    apiKey: 'your-api-key',
    appIdentifier: 'healthcare-app',
    phiSafe: true
});

// Or via meta tag (auto-detected)
&lt;meta name="vitalytics-phi-safe" content="true"&gt;

// Or at runtime
Vitalytics.enablePHISafe();</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fab fa-chrome mr-2 text-green-500"></i>Chrome Extension Helpers
    </h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Check environment
if (Vitalytics.isExtension()) {
    console.log('Running in Chrome extension');
}
console.log(Vitalytics.getPlatform());  // 'chrome-extension' or 'web'

// Extension lifecycle
Vitalytics.trackInstalled();                    // Track install
Vitalytics.trackUpdated('1.0.0');               // Track update (previous version)
Vitalytics.trackPopupOpened();                  // Track popup open

// Any extension event
Vitalytics.trackExtensionEvent('badge_clicked', { badge_text: '5' });</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Utility Methods</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Enable/disable all tracking
Vitalytics.setEnabled(false);  // Pause
Vitalytics.setEnabled(true);   // Resume

// Set user ID (appears in health events)
Vitalytics.setUserId('user-123');

// Force flush all queued events
Vitalytics.flush();              // Both health + analytics
Vitalytics.flushHealth();        // Health only
Vitalytics.flushAnalytics();     // Analytics only

// Debug mode
Vitalytics.enableDebug();</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-lock mr-2 text-yellow-500"></i>Authentication
    </h3>

    <p class="text-gray-600 mb-4">Vitalytics supports two authentication methods:</p>

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Method</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Best For</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">How It Works</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr class="bg-green-50">
                    <td class="px-4 py-2 font-semibold text-green-800">Direct API Key</td>
                    <td class="px-4 py-2">Chrome extensions, web apps, mobile apps</td>
                    <td class="px-4 py-2">Hardcode API key in app &rarr; Send with requests</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold text-gray-700">Secret Exchange</td>
                    <td class="px-4 py-2">Server-to-server, high-security environments</td>
                    <td class="px-4 py-2">App secret &rarr; Request API key &rarr; Use for 24 hours</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">
        <i class="fas fa-key mr-2 text-green-500"></i>Method 1: Direct API Key (Recommended)
    </h4>
    <p class="text-gray-600 mb-3">The simplest approach &mdash; configure your API key directly in the SDK:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>Vitalytics.init({
    apiKey: 'vtx_your_api_key_here',   // Your API key
    appIdentifier: 'myapp-chrome'       // Your app identifier
});</code></pre>
    </div>

    <div class="overflow-x-auto mb-4">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Parameter</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Example</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-mono text-sm">apiKey</td>
                    <td class="px-4 py-2">Your unique API key (starts with <code>vtx_</code>)</td>
                    <td class="px-4 py-2 font-mono text-sm">vtx_abc123def456...</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-sm">appIdentifier</td>
                    <td class="px-4 py-2">Your app identifier (product slug + platform)</td>
                    <td class="px-4 py-2 font-mono text-sm">myapp-chrome</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="text-gray-600 mb-2"><strong>Getting Your API Key:</strong></p>
    <ol class="list-decimal list-inside text-gray-600 mb-4 space-y-1">
        <li>Go to <strong>Admin &rarr; Products</strong> in your Vitalytics dashboard</li>
        <li>Select your product (or create a new one)</li>
        <li>Click <strong>"Add App"</strong> and select your platform</li>
        <li>Copy the API key immediately &mdash; click the key to reveal it anytime in the dashboard</li>
    </ol>

    <p class="text-gray-600 mb-2"><strong>Regenerating Your API Key:</strong></p>
    <ol class="list-decimal list-inside text-gray-600 mb-4 space-y-1">
        <li>Go to <strong>Admin &rarr; Products &rarr; [Your Product]</strong></li>
        <li>Click the <i class="fas fa-sync-alt text-yellow-600"></i> regenerate icon next to your app</li>
        <li>Copy the new key &mdash; the old key stops working immediately</li>
        <li>Update your application and redeploy</li>
    </ol>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">
        <i class="fas fa-shield-alt mr-2 text-purple-500"></i>Method 2: Secret Exchange (Advanced)
    </h4>
    <p class="text-gray-600 mb-3">For environments where you don't want to hardcode API keys, use app secrets to request temporary API keys:</p>

    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Step 1: Request API key using your app secret
const response = await fetch('https://your-vitalytics-server.com/api/v1/auth/key/myapp-chrome', {
    method: 'GET',
    headers: {
        'X-App-Secret': 'vtx_your_app_secret_here'
    }
});

const { apiKey, expiresAt } = await response.json();
// apiKey is valid for 24 hours

// Step 2: Initialize SDK with the retrieved key
Vitalytics.init({
    apiKey: apiKey,
    appIdentifier: 'myapp-chrome'
});</code></pre>
    </div>

    <p class="text-gray-600 mb-2"><strong>Managing App Secrets:</strong></p>
    <ol class="list-decimal list-inside text-gray-600 mb-4 space-y-1">
        <li>Go to <strong>Admin &rarr; Products &rarr; [Your Product]</strong></li>
        <li>Click the <i class="fas fa-shield-alt text-purple-600"></i> secrets icon next to your app</li>
        <li>Click <strong>"Generate Secret"</strong> to create a new secret</li>
        <li>Copy the secret immediately &mdash; it's only shown once</li>
    </ol>

    <p class="text-gray-600 mb-2"><strong>Rotating Secrets:</strong></p>
    <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
        <li>Click <strong>"Rotate Secret"</strong> to generate a new secret</li>
        <li>Old secrets get a 30-day grace period before expiring</li>
        <li>This allows time to update your applications without downtime</li>
    </ul>

    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-4">
        <p class="text-purple-800"><strong>When to use Secret Exchange:</strong></p>
        <ul class="list-disc list-inside text-purple-800 mt-2 space-y-1">
            <li>Server-side applications where secrets can be kept secure</li>
            <li>Environments requiring key rotation without app updates</li>
            <li>Multi-tenant systems that need dynamic key management</li>
        </ul>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Security Notes</h4>
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <ul class="list-disc list-inside text-blue-800 space-y-1">
            <li><strong>Write-only:</strong> API keys can only send events, not read data</li>
            <li><strong>Rate limited:</strong> 100 requests/minute per device prevents abuse</li>
            <li><strong>Encrypted storage:</strong> Keys and secrets are encrypted at rest</li>
            <li><strong>Instant revocation:</strong> Regenerating a key immediately invalidates the old one</li>
            <li><strong>Grace period:</strong> Rotated secrets remain valid for 30 days</li>
        </ul>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Environment Detection</h3>
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-800"><strong>Automatic Adaptation:</strong></p>
        <ul class="list-disc list-inside text-blue-800 mt-2 space-y-1">
            <li><strong>Chrome Extension:</strong> Uses <code>chrome.storage.local</code> for device ID persistence</li>
            <li><strong>Website:</strong> Uses <code>localStorage</code> for device ID persistence</li>
            <li>Platform is automatically set to <code>chrome-extension</code> or <code>web</code></li>
        </ul>
    </div>

    <div class="bg-green-50 border-l-4 border-green-500 p-4">
        <p class="text-green-800"><strong>Pro Tip:</strong> Enable debug mode during development: <code>Vitalytics.enableDebug()</code> - see all events in the console.</p>
    </div>
</div>
