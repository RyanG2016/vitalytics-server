<x-app-layout>

<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-chart-line mr-2"></i> Analytics Integration Guide
        </h1>
        <p class="text-gray-600 mt-2">Learn how to integrate Vitalytics Analytics tracking into your applications</p>
    </div>

    {{-- Tabs --}}
    <div x-data="{ activeTab: '{{ $tab }}' }" class="bg-white rounded-lg shadow">
        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto">
                <button @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fas fa-info-circle mr-2"></i>Overview
                </button>
                <button @click="activeTab = 'api'"
                    :class="activeTab === 'api' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fas fa-code mr-2"></i>API Reference
                </button>
                <button @click="activeTab = 'javascript'"
                    :class="activeTab === 'javascript' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fab fa-js mr-2"></i>JavaScript
                </button>
                <button @click="activeTab = 'swift'"
                    :class="activeTab === 'swift' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fab fa-swift mr-2"></i>Swift (iOS)
                </button>
                <button @click="activeTab = 'kotlin'"
                    :class="activeTab === 'kotlin' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fab fa-android mr-2"></i>Kotlin (Android)
                </button>
                <button @click="activeTab = 'laravel'"
                    :class="activeTab === 'laravel' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fab fa-laravel mr-2"></i>Laravel
                </button>
                <button @click="activeTab = 'dotnet'"
                    :class="activeTab === 'dotnet' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fab fa-microsoft mr-2"></i>.NET
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            {{-- Overview Tab --}}
            <div x-show="activeTab === 'overview'" x-cloak>
                <div class="prose max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">What is Vitalytics Analytics?</h2>
                    <p class="text-gray-700 mb-6">
                        Vitalytics Analytics helps you understand how users interact with your applications.
                        Unlike health monitoring (which tracks errors and crashes), analytics focuses on
                        <strong>user behavior analysis</strong> - what screens users visit, which features they use,
                        and how they navigate through your app.
                    </p>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-shield-alt text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Privacy First:</strong> Analytics is <strong>disabled by default</strong>.
                                    You must explicitly enable it in your SDK after obtaining user consent.
                                    This ensures compliance with privacy expectations.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-blue-800 mb-3">
                                <i class="fas fa-desktop mr-2"></i>Screen/Page Views
                            </h3>
                            <p class="text-blue-700">
                                Track which screens users visit, how long they stay, and their navigation patterns
                                through your application.
                            </p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-green-800 mb-3">
                                <i class="fas fa-mouse-pointer mr-2"></i>User Interactions
                            </h3>
                            <p class="text-green-700">
                                Monitor button clicks, form submissions, menu selections, and other user
                                interactions to understand engagement.
                            </p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-purple-800 mb-3">
                                <i class="fas fa-star mr-2"></i>Feature Usage
                            </h3>
                            <p class="text-purple-700">
                                Identify which features are most used, which are underutilized, and how users
                                discover and adopt new functionality.
                            </p>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-orange-800 mb-3">
                                <i class="fas fa-clock mr-2"></i>Session Analytics
                            </h3>
                            <p class="text-orange-700">
                                Analyze session duration, screens per session, and return visit patterns
                                to measure overall user engagement.
                            </p>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Analytics vs Health Monitoring</h2>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Feature</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Health Monitoring</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Analytics</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2 font-medium">Purpose</td>
                                    <td class="px-4 py-2">Error tracking, crashes, uptime</td>
                                    <td class="px-4 py-2">User behavior analysis</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-medium">Event Types</td>
                                    <td class="px-4 py-2">error, warning, crash, heartbeat</td>
                                    <td class="px-4 py-2">screen_viewed, button_clicked, etc.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-medium">Data Focus</td>
                                    <td class="px-4 py-2">What went wrong</td>
                                    <td class="px-4 py-2">What users are doing</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-medium">Default State</td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Enabled</span></td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Disabled (opt-in)</span></td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-medium">API Endpoint</td>
                                    <td class="px-4 py-2 font-mono text-sm">/api/v1/health/events</td>
                                    <td class="px-4 py-2 font-mono text-sm">/api/v1/analytics/events</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">How It Works</h2>
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <ol class="list-decimal list-inside space-y-4 text-gray-700">
                            <li><strong>Initialize the SDK</strong> - Configure with your API key and app identifier (analytics starts disabled).</li>
                            <li><strong>Get User Consent</strong> - Ask users if they want to share usage data.</li>
                            <li><strong>Enable Analytics</strong> - Call <code class="bg-gray-200 px-1 rounded">setEnabled(true)</code> after consent.</li>
                            <li><strong>Track Events</strong> - SDK automatically batches events and sends them periodically.</li>
                            <li><strong>View in Dashboard</strong> - Analyze user behavior patterns in the Vitalytics dashboard.</li>
                        </ol>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Event Categories</h2>
                    <p class="text-gray-700 mb-4">Use consistent categories across your apps for better dashboard analytics:</p>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Category</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Example Events</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">navigation</td>
                                    <td class="px-4 py-2">Screen/page transitions</td>
                                    <td class="px-4 py-2">screen_viewed, tab_switched</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">interaction</td>
                                    <td class="px-4 py-2">User interactions</td>
                                    <td class="px-4 py-2">button_clicked, menu_opened</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">form</td>
                                    <td class="px-4 py-2">Form-related actions</td>
                                    <td class="px-4 py-2">form_submitted, field_focused</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">feature</td>
                                    <td class="px-4 py-2">Feature usage</td>
                                    <td class="px-4 py-2">feature_used, setting_changed</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">content</td>
                                    <td class="px-4 py-2">Content engagement</td>
                                    <td class="px-4 py-2">content_viewed, content_shared</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">session</td>
                                    <td class="px-4 py-2">Session lifecycle</td>
                                    <td class="px-4 py-2">session_started, session_ended</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Test Mode:</strong> Like health monitoring, analytics supports an
                                    <code class="bg-blue-100 px-1 rounded">isTest</code> flag. Enable this during
                                    development to keep test data separate from production metrics.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- API Reference Tab --}}
            <div x-show="activeTab === 'api'" x-cloak>
                <div class="prose max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-code mr-2 text-blue-500"></i>API Reference
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Analytics uses the same <strong>X-API-Key</strong> authentication as health monitoring.
                        No separate token is required.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Submit Events</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-400 mb-2">POST /api/v1/analytics/events</div>
                        <div class="text-sm text-gray-400 mt-4 mb-2">Headers</div>
                        <pre class="text-gray-100 text-sm overflow-x-auto">X-API-Key: your-api-key
Content-Type: application/json</pre>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <div class="text-sm text-gray-400 mb-2">Request Body</div>
                        <pre class="text-gray-100 text-sm"><code>{
    "batchId": "uuid-v4",
    "appIdentifier": "myapp-chrome",
    "deviceInfo": {
        "deviceId": "persistent-uuid",
        "deviceModel": "Chrome 120",
        "platform": "chrome",
        "osVersion": "Windows 11",
        "appVersion": "1.2.0",
        "screenResolution": "1920x1080",
        "language": "en-US"
    },
    "sessionId": "session-uuid",
    "anonymousUserId": "anon-user-uuid",
    "isTest": false,
    "sentAt": "2026-01-09T12:00:00Z",
    "events": [
        {
            "id": "event-uuid",
            "timestamp": "2026-01-09T11:59:45Z",
            "name": "screen_viewed",
            "category": "navigation",
            "screen": "Dashboard",
            "properties": {
                "source": "sidebar_link"
            },
            "duration": 5000,
            "referrer": "Settings"
        }
    ]
}</code></pre>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-400 mb-2">Success Response (200 OK)</div>
                        <pre class="text-gray-100 text-sm overflow-x-auto">{
    "success": true,
    "batchId": "uuid-v4",
    "eventsReceived": 5
}</pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Event Fields</h3>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Field</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Type</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Required</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">id</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">Yes</td>
                                    <td class="px-4 py-2">Unique event ID (UUID)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">timestamp</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">Yes</td>
                                    <td class="px-4 py-2">ISO 8601 timestamp (UTC)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">name</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">Yes</td>
                                    <td class="px-4 py-2">Event name (e.g., screen_viewed)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">category</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">Yes</td>
                                    <td class="px-4 py-2">Event category (navigation, interaction, etc.)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">screen</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">No</td>
                                    <td class="px-4 py-2">Current screen/page name</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">element</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">No</td>
                                    <td class="px-4 py-2">Element ID (for interactions)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">properties</td>
                                    <td class="px-4 py-2">object</td>
                                    <td class="px-4 py-2">No</td>
                                    <td class="px-4 py-2">Additional properties (max 10 keys)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">duration</td>
                                    <td class="px-4 py-2">integer</td>
                                    <td class="px-4 py-2">No</td>
                                    <td class="px-4 py-2">Duration in milliseconds</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">referrer</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">No</td>
                                    <td class="px-4 py-2">Previous screen/page</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Rate Limits</h3>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    <strong>1000 events per minute</strong> per app identifier.<br>
                                    <strong>100 events maximum</strong> per batch.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- JavaScript Tab --}}
            <div x-show="activeTab === 'javascript'" x-cloak>
                <div class="prose max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fab fa-js mr-2 text-yellow-500"></i>JavaScript Integration
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Add Vitalytics Analytics to your web application or Chrome extension.
                    </p>

                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-link text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong>Shared Configuration:</strong> Analytics uses the same API key and app identifier
                                    as health monitoring. No separate credentials needed.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// Initialize (disabled by default)
const analytics = new VitalyticsAnalytics({
    apiKey: 'your-api-key',
    appIdentifier: 'myapp-chrome',
    enabled: false,  // Disabled until user consent
    isTest: false    // Set true during development
});

// Enable after user consents
analytics.setEnabled(true);

// Track screens
analytics.trackScreen('Dashboard');
analytics.trackScreen('Settings', { source: 'menu' });

// Track interactions
analytics.trackClick('save-button', { formId: 'profile-form' });
analytics.trackEvent('menu_opened', 'interaction', { menuName: 'settings' });

// Track features
analytics.trackFeature('dark-mode', { enabled: true });
analytics.trackFeature('export', { format: 'csv' });

// Flush on page unload
window.addEventListener('beforeunload', () =&gt; {
    analytics.trackSessionEnd();
});</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Core SDK Methods</h3>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Method</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">setEnabled(bool)</td>
                                    <td class="px-4 py-2">Enable/disable analytics tracking</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">trackScreen(name, props)</td>
                                    <td class="px-4 py-2">Track screen/page view</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">trackEvent(name, category, props)</td>
                                    <td class="px-4 py-2">Track custom event</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">trackClick(elementId, props)</td>
                                    <td class="px-4 py-2">Track button/element click</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">trackFeature(name, props)</td>
                                    <td class="px-4 py-2">Track feature usage</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">flush()</td>
                                    <td class="px-4 py-2">Immediately send queued events</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">
                        <i class="fas fa-code mr-2"></i>Complete SDK Class
                    </h3>
                    <p class="text-gray-600 mb-3">Copy this class into your project:</p>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 500px;">
                        <pre class="text-gray-100 text-sm"><code>class VitalyticsAnalytics {
    constructor(config) {
        this.apiKey = config.apiKey;
        this.appIdentifier = config.appIdentifier;
        this.enabled = config.enabled ?? false; // Disabled by default
        this.isTest = config.isTest ?? false;
        this.flushInterval = config.flushInterval ?? 30000;
        this.maxQueueSize = config.maxQueueSize ?? 20;

        this.eventQueue = [];
        this.sessionId = this.generateUUID();
        this.sessionStartTime = Date.now();
        this.deviceInfo = config.deviceInfo || this.detectDeviceInfo();
        this.anonymousUserId = config.anonymousUserId || this.getOrCreateAnonymousId();

        this.currentScreen = null;
        this.screenStartTime = null;

        if (this.enabled) {
            this.startFlushTimer();
            this.trackSessionStart();
        }
    }

    // Enable/disable analytics (call after user consent)
    setEnabled(enabled) {
        this.enabled = enabled;
        if (enabled) {
            this.startFlushTimer();
            this.trackSessionStart();
        } else {
            this.stopFlushTimer();
            this.eventQueue = [];
        }
    }

    // Track screen/page view
    trackScreen(screenName, properties = {}) {
        if (!this.enabled) return;

        // Track time on previous screen
        if (this.currentScreen &amp;&amp; this.screenStartTime) {
            const duration = Date.now() - this.screenStartTime;
            this.queueEvent({
                name: 'screen_duration',
                category: 'navigation',
                screen: this.currentScreen,
                duration: duration
            });
        }

        this.currentScreen = screenName;
        this.screenStartTime = Date.now();

        this.queueEvent({
            name: 'screen_viewed',
            category: 'navigation',
            screen: screenName,
            properties: properties,
            referrer: this.currentScreen
        });
    }

    // Track user interaction
    trackEvent(eventName, category, properties = {}) {
        if (!this.enabled) return;

        this.queueEvent({
            name: eventName,
            category: category,
            screen: this.currentScreen,
            properties: properties
        });
    }

    // Track button click
    trackClick(elementId, properties = {}) {
        this.trackEvent('button_clicked', 'interaction', {
            element: elementId,
            ...properties
        });
    }

    // Track feature usage
    trackFeature(featureName, properties = {}) {
        this.trackEvent('feature_used', 'feature', {
            feature: featureName,
            ...properties
        });
    }

    // Queue event for batching
    // IMPORTANT: timestamp must be UTC - toISOString() returns UTC with Z suffix
    queueEvent(event) {
        this.eventQueue.push({
            id: this.generateUUID(),
            timestamp: new Date().toISOString(), // Always UTC
            ...event
        });

        if (this.eventQueue.length &gt;= this.maxQueueSize) {
            this.flush();
        }
    }

    // Send queued events
    async flush() {
        if (!this.enabled || this.eventQueue.length === 0) return;

        const events = [...this.eventQueue];
        this.eventQueue = [];

        const batch = {
            batchId: this.generateUUID(),
            appIdentifier: this.appIdentifier,
            deviceInfo: this.deviceInfo,
            sessionId: this.sessionId,
            anonymousUserId: this.anonymousUserId,
            isTest: this.isTest,
            sentAt: new Date().toISOString(),
            events: events
        };

        try {
            const response = await fetch('/api/v1/analytics/events', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.apiKey
                },
                body: JSON.stringify(batch)
            });

            if (!response.ok) {
                // Re-queue on failure
                this.eventQueue = [...events, ...this.eventQueue];
            }
        } catch (error) {
            // Re-queue on network error
            this.eventQueue = [...events, ...this.eventQueue];
            console.error('Analytics flush failed:', error);
        }
    }

    // Session tracking
    trackSessionStart() {
        this.queueEvent({
            name: 'session_started',
            category: 'session',
            properties: { sessionId: this.sessionId }
        });
    }

    trackSessionEnd() {
        const duration = Date.now() - this.sessionStartTime;
        this.queueEvent({
            name: 'session_ended',
            category: 'session',
            duration: duration,
            properties: { sessionId: this.sessionId }
        });
        this.flush(); // Immediate flush on session end
    }

    // Utility methods
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c =&gt; {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r &amp; 0x3 | 0x8)).toString(16);
        });
    }

    detectDeviceInfo() {
        return {
            deviceId: this.getOrCreateDeviceId(),
            deviceModel: navigator.userAgent,
            platform: this.detectPlatform(),
            osVersion: navigator.platform,
            appVersion: '1.0.0', // Set from your app config
            screenResolution: `${window.screen.width}x${window.screen.height}`,
            language: navigator.language
        };
    }

    detectPlatform() {
        if (typeof chrome !== 'undefined' &amp;&amp; chrome.runtime &amp;&amp; chrome.runtime.id) {
            return 'chrome';
        }
        return 'web';
    }

    getOrCreateDeviceId() {
        let deviceId = localStorage.getItem('vitalytics_device_id');
        if (!deviceId) {
            deviceId = 'dev-' + this.generateUUID();
            localStorage.setItem('vitalytics_device_id', deviceId);
        }
        return deviceId;
    }

    getOrCreateAnonymousId() {
        let anonId = localStorage.getItem('vitalytics_anon_id');
        if (!anonId) {
            anonId = 'anon-' + this.generateUUID();
            localStorage.setItem('vitalytics_anon_id', anonId);
        }
        return anonId;
    }

    startFlushTimer() {
        this.flushTimer = setInterval(() =&gt; this.flush(), this.flushInterval);
    }

    stopFlushTimer() {
        if (this.flushTimer) {
            clearInterval(this.flushTimer);
            this.flushTimer = null;
        }
    }
}</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Chrome Extension Storage</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// Use chrome.storage for device ID persistence
async function getOrCreateDeviceId() {
    const result = await chrome.storage.local.get('vitalytics_device_id');
    if (result.vitalytics_device_id) {
        return result.vitalytics_device_id;
    }
    const deviceId = 'dev-' + crypto.randomUUID();
    await chrome.storage.local.set({ vitalytics_device_id: deviceId });
    return deviceId;
}

// Track extension-specific events
analytics.trackEvent('extension_installed', 'session');
analytics.trackEvent('popup_opened', 'navigation');
analytics.trackEvent('context_menu_used', 'interaction', { action: 'translate' });</code></pre>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-download text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Full Documentation:</strong> Download the complete SDK documentation with all platforms:
                                    <a href="{{ route('docs.analytics-sdk') }}" target="_blank" class="underline font-medium">ANALYTICS_SDK_INTEGRATION.md</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Swift Tab --}}
            <div x-show="activeTab === 'swift'" x-cloak>
                <div class="prose max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fab fa-swift mr-2 text-orange-500"></i>Swift Integration (iOS/macOS)
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Add Vitalytics Analytics to your iOS or macOS application.
                    </p>

                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-link text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong>Shared Configuration:</strong> Analytics uses the same API key and app identifier
                                    as health monitoring. No separate credentials needed.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// Configure (still disabled)
VitalyticsAnalytics.shared.configure(
    apiKey: "your-api-key",
    appIdentifier: "myapp-ios",
    isTest: true // Set false for production
)

// Enable after user consent
VitalyticsAnalytics.shared.setEnabled(true)

// Track screens
VitalyticsAnalytics.shared.trackScreen("HomeViewController")
VitalyticsAnalytics.shared.trackScreen("SettingsViewController", properties: ["source": "menu"])

// Track interactions
VitalyticsAnalytics.shared.trackClick("save-button")
VitalyticsAnalytics.shared.trackFeature("dark-mode", properties: ["enabled": true])

// Track custom events
VitalyticsAnalytics.shared.trackEvent("purchase_completed", category: "conversion", properties: [
    "amount": 29.99,
    "currency": "USD"
])</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">
                        <i class="fas fa-code mr-2"></i>Complete SDK Class
                    </h3>
                    <p class="text-gray-600 mb-3">Copy this class into your project:</p>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 500px;">
                        <pre class="text-gray-100 text-sm"><code>import Foundation

class VitalyticsAnalytics {
    static let shared = VitalyticsAnalytics()

    private var apiKey: String = ""
    private var appIdentifier: String = ""
    private var enabled: Bool = false // Disabled by default
    private var isTest: Bool = false

    private var eventQueue: [[String: Any]] = []
    private var sessionId: String = UUID().uuidString
    private var sessionStartTime: Date = Date()
    private var currentScreen: String?
    private var screenStartTime: Date?

    private var flushTimer: Timer?

    func configure(apiKey: String, appIdentifier: String, isTest: Bool = false) {
        self.apiKey = apiKey
        self.appIdentifier = appIdentifier
        self.isTest = isTest
        // Note: enabled remains false until setEnabled(true) is called
    }

    func setEnabled(_ enabled: Bool) {
        self.enabled = enabled
        if enabled {
            startSession()
        } else {
            stopSession()
            eventQueue.removeAll()
        }
    }

    func trackScreen(_ screenName: String, properties: [String: Any] = [:]) {
        guard enabled else { return }

        // Track duration on previous screen
        if let current = currentScreen, let start = screenStartTime {
            let duration = Int(Date().timeIntervalSince(start) * 1000)
            queueEvent(name: "screen_duration", category: "navigation",
                      screen: current, duration: duration)
        }

        currentScreen = screenName
        screenStartTime = Date()

        queueEvent(name: "screen_viewed", category: "navigation",
                  screen: screenName, properties: properties)
    }

    func trackEvent(_ name: String, category: String, properties: [String: Any] = [:]) {
        guard enabled else { return }
        queueEvent(name: name, category: category,
                  screen: currentScreen, properties: properties)
    }

    func trackClick(_ elementId: String, properties: [String: Any] = [:]) {
        var props = properties
        props["element"] = elementId
        trackEvent("button_clicked", category: "interaction", properties: props)
    }

    func trackFeature(_ featureName: String, properties: [String: Any] = [:]) {
        var props = properties
        props["feature"] = featureName
        trackEvent("feature_used", category: "feature", properties: props)
    }

    // IMPORTANT: All timestamps must be UTC
    private func queueEvent(name: String, category: String, screen: String? = nil,
                           properties: [String: Any]? = nil, duration: Int? = nil) {
        let formatter = ISO8601DateFormatter()
        formatter.timeZone = TimeZone(identifier: "UTC") // Ensure UTC

        var event: [String: Any] = [
            "id": UUID().uuidString,
            "timestamp": formatter.string(from: Date()), // UTC timestamp
            "name": name,
            "category": category
        ]
        if let screen = screen { event["screen"] = screen }
        if let props = properties { event["properties"] = props }
        if let dur = duration { event["duration"] = dur }

        eventQueue.append(event)

        if eventQueue.count &gt;= 20 {
            flush()
        }
    }

    func flush() {
        guard enabled, !eventQueue.isEmpty else { return }

        let events = eventQueue
        eventQueue.removeAll()

        let batch: [String: Any] = [
            "batchId": UUID().uuidString,
            "appIdentifier": appIdentifier,
            "deviceInfo": getDeviceInfo(),
            "sessionId": sessionId,
            "anonymousUserId": getAnonymousUserId(),
            "isTest": isTest,
            "sentAt": ISO8601DateFormatter().string(from: Date()),
            "events": events
        ]

        sendBatch(batch)
    }

    private func sendBatch(_ batch: [String: Any]) {
        guard let url = URL(string: "https://your-vitalytics-server.com/api/v1/analytics/events"),
              let body = try? JSONSerialization.data(withJSONObject: batch) else { return }

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue(apiKey, forHTTPHeaderField: "X-API-Key")
        request.httpBody = body

        URLSession.shared.dataTask(with: request) { _, response, error in
            if error != nil {
                // Re-queue events on failure
                if let events = batch["events"] as? [[String: Any]] {
                    self.eventQueue.insert(contentsOf: events, at: 0)
                }
            }
        }.resume()
    }

    private func getDeviceInfo() -&gt; [String: Any] {
        return [
            "deviceId": getDeviceId(),
            "deviceModel": UIDevice.current.model,
            "platform": "ios",
            "osVersion": UIDevice.current.systemVersion,
            "appVersion": Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "1.0",
            "screenResolution": "\(Int(UIScreen.main.bounds.width))x\(Int(UIScreen.main.bounds.height))",
            "language": Locale.current.language.languageCode?.identifier ?? "en"
        ]
    }

    private func getDeviceId() -&gt; String {
        if let id = UserDefaults.standard.string(forKey: "vitalytics_device_id") {
            return id
        }
        let id = "dev-\(UUID().uuidString)"
        UserDefaults.standard.set(id, forKey: "vitalytics_device_id")
        return id
    }

    private func getAnonymousUserId() -&gt; String {
        if let id = UserDefaults.standard.string(forKey: "vitalytics_anon_id") {
            return id
        }
        let id = "anon-\(UUID().uuidString)"
        UserDefaults.standard.set(id, forKey: "vitalytics_anon_id")
        return id
    }

    private func startSession() {
        sessionId = UUID().uuidString
        sessionStartTime = Date()
        queueEvent(name: "session_started", category: "session")

        flushTimer = Timer.scheduledTimer(withTimeInterval: 30, repeats: true) { _ in
            self.flush()
        }
    }

    private func stopSession() {
        flushTimer?.invalidate()
        flushTimer = nil
    }
}</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">SwiftUI Screen Tracking</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>struct PatientView: View {
    var body: some View {
        VStack {
            // Content
        }
        .onAppear {
            VitalyticsAnalytics.shared.trackScreen("PatientView")
        }
    }
}

struct SettingsView: View {
    var body: some View {
        NavigationStack {
            List {
                // Settings
            }
        }
        .onAppear {
            VitalyticsAnalytics.shared.trackScreen("Settings")
        }
    }
}</code></pre>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-download text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Full Documentation:</strong> Download the complete SDK documentation with all platforms:
                                    <a href="{{ route('docs.analytics-sdk') }}" target="_blank" class="underline font-medium">ANALYTICS_SDK_INTEGRATION.md</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kotlin Tab --}}
            <div x-show="activeTab === 'kotlin'" x-cloak>
                <div class="prose max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fab fa-android mr-2 text-green-500"></i>Kotlin Integration (Android)
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Add Vitalytics Analytics to your Android application.
                    </p>

                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-link text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong>Shared Configuration:</strong> Analytics uses the same base URL, API key, and app identifier
                                    as health monitoring. No separate credentials needed.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// In Application class
class MyApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        // Use the same config as health monitoring
        VitalyticsAnalytics.initialize(
            context = this,
            baseUrl = "https://vitalytics.yourserver.com",  // Same as health
            apiKey = "your-api-key",                        // Same as health
            appIdentifier = "myapp-android",                // Same as health
            isTest = BuildConfig.DEBUG
        )
    }
}

// Enable after user consent
VitalyticsAnalytics.setEnabled(true)

// Track screens (in Activity)
override fun onResume() {
    super.onResume()
    VitalyticsAnalytics.trackScreen("MainActivity")
}

// Track interactions
VitalyticsAnalytics.trackClick("save-button")
VitalyticsAnalytics.trackFeature("dark-mode", mapOf("enabled" to true))

// Track custom events
VitalyticsAnalytics.trackEvent("purchase_completed", "conversion", mapOf(
    "amount" to 29.99,
    "currency" to "USD"
))</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">
                        <i class="fas fa-code mr-2"></i>Complete SDK Class
                    </h3>
                    <p class="text-gray-600 mb-3">Copy this class into your project:</p>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 500px;">
                        <pre class="text-gray-100 text-sm"><code>import android.content.Context
import android.content.SharedPreferences
import android.os.Build
import kotlinx.coroutines.*
import org.json.JSONArray
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.text.SimpleDateFormat
import java.util.*

object VitalyticsAnalytics {
    private lateinit var context: Context
    private var baseUrl: String = ""
    private var apiKey: String = ""
    private var appIdentifier: String = ""
    private var enabled: Boolean = false // Disabled by default
    private var isTest: Boolean = false

    private val eventQueue = mutableListOf&lt;JSONObject&gt;()
    private var sessionId: String = UUID.randomUUID().toString()
    private var sessionStartTime: Long = System.currentTimeMillis()
    private var currentScreen: String? = null
    private var screenStartTime: Long? = null

    private var flushJob: Job? = null
    private val scope = CoroutineScope(Dispatchers.IO + SupervisorJob())

    private val prefs: SharedPreferences by lazy {
        context.getSharedPreferences("vitalytics_analytics", Context.MODE_PRIVATE)
    }

    fun initialize(
        context: Context,
        baseUrl: String,  // e.g., "https://vitalytics.yourserver.com"
        apiKey: String,
        appIdentifier: String,
        isTest: Boolean = false
    ) {
        this.context = context.applicationContext
        this.baseUrl = baseUrl.trimEnd('/')
        this.apiKey = apiKey
        this.appIdentifier = appIdentifier
        this.isTest = isTest
    }

    fun setEnabled(enabled: Boolean) {
        this.enabled = enabled
        if (enabled) {
            startSession()
        } else {
            stopSession()
            eventQueue.clear()
        }
    }

    fun trackScreen(screenName: String, properties: Map&lt;String, Any&gt; = emptyMap()) {
        if (!enabled) return

        // Track duration on previous screen
        currentScreen?.let { current -&gt;
            screenStartTime?.let { start -&gt;
                val duration = System.currentTimeMillis() - start
                queueEvent("screen_duration", "navigation", current, duration = duration.toInt())
            }
        }

        currentScreen = screenName
        screenStartTime = System.currentTimeMillis()

        queueEvent("screen_viewed", "navigation", screenName, properties)
    }

    fun trackEvent(name: String, category: String, properties: Map&lt;String, Any&gt; = emptyMap()) {
        if (!enabled) return
        queueEvent(name, category, currentScreen, properties)
    }

    fun trackClick(elementId: String, properties: Map&lt;String, Any&gt; = emptyMap()) {
        trackEvent("button_clicked", "interaction", properties + ("element" to elementId))
    }

    fun trackFeature(featureName: String, properties: Map&lt;String, Any&gt; = emptyMap()) {
        trackEvent("feature_used", "feature", properties + ("feature" to featureName))
    }

    private fun queueEvent(
        name: String,
        category: String,
        screen: String? = null,
        properties: Map&lt;String, Any&gt;? = null,
        duration: Int? = null
    ) {
        val event = JSONObject().apply {
            put("id", UUID.randomUUID().toString())
            put("timestamp", getISOTimestamp())
            put("name", name)
            put("category", category)
            screen?.let { put("screen", it) }
            properties?.let { put("properties", JSONObject(it)) }
            duration?.let { put("duration", it) }
        }

        synchronized(eventQueue) {
            eventQueue.add(event)
            if (eventQueue.size &gt;= 20) {
                flush()
            }
        }
    }

    fun flush() {
        if (!enabled || eventQueue.isEmpty()) return

        val events: List&lt;JSONObject&gt;
        synchronized(eventQueue) {
            events = eventQueue.toList()
            eventQueue.clear()
        }

        scope.launch {
            sendBatch(events)
        }
    }

    private suspend fun sendBatch(events: List&lt;JSONObject&gt;) {
        val batch = JSONObject().apply {
            put("batchId", UUID.randomUUID().toString())
            put("appIdentifier", appIdentifier)
            put("deviceInfo", getDeviceInfo())
            put("sessionId", sessionId)
            put("anonymousUserId", getAnonymousUserId())
            put("isTest", isTest)
            put("sentAt", getISOTimestamp())
            put("events", JSONArray(events))
        }

        try {
            val url = URL("$baseUrl/api/v1/analytics/events")
            val connection = url.openConnection() as HttpURLConnection
            connection.requestMethod = "POST"
            connection.setRequestProperty("Content-Type", "application/json")
            connection.setRequestProperty("X-API-Key", apiKey)
            connection.doOutput = true

            connection.outputStream.write(batch.toString().toByteArray())

            if (connection.responseCode !in 200..299) {
                // Re-queue on failure
                synchronized(eventQueue) {
                    eventQueue.addAll(0, events)
                }
            }
            connection.disconnect()
        } catch (e: Exception) {
            // Re-queue on error
            synchronized(eventQueue) {
                eventQueue.addAll(0, events)
            }
        }
    }

    private fun getDeviceInfo(): JSONObject {
        return JSONObject().apply {
            put("deviceId", getDeviceId())
            put("deviceModel", Build.MODEL)
            put("platform", "android")
            put("osVersion", Build.VERSION.RELEASE)
            put("appVersion", context.packageManager
                .getPackageInfo(context.packageName, 0).versionName)
            put("language", Locale.getDefault().language)
        }
    }

    private fun getDeviceId(): String {
        return prefs.getString("device_id", null) ?: run {
            val id = "dev-${UUID.randomUUID()}"
            prefs.edit().putString("device_id", id).apply()
            id
        }
    }

    private fun getAnonymousUserId(): String {
        return prefs.getString("anon_id", null) ?: run {
            val id = "anon-${UUID.randomUUID()}"
            prefs.edit().putString("anon_id", id).apply()
            id
        }
    }

    // IMPORTANT: All timestamps must be UTC (GMT+0)
    private fun getISOTimestamp(): String {
        return SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US).apply {
            timeZone = TimeZone.getTimeZone("UTC") // Must be UTC!
        }.format(Date())
    }

    private fun startSession() {
        sessionId = UUID.randomUUID().toString()
        sessionStartTime = System.currentTimeMillis()
        queueEvent("session_started", "session")

        flushJob = scope.launch {
            while (isActive) {
                delay(30_000)
                flush()
            }
        }
    }

    private fun stopSession() {
        flushJob?.cancel()
        flushJob = null
    }
}</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Jetpack Compose Screen Tracking</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>@Composable
fun PatientScreen(patientId: String) {
    LaunchedEffect(Unit) {
        VitalyticsAnalytics.trackScreen("PatientView", mapOf("patientId" to patientId))
    }

    Column {
        // Screen content
    }
}

@Composable
fun SettingsScreen() {
    LaunchedEffect(Unit) {
        VitalyticsAnalytics.trackScreen("Settings")
    }

    Column {
        // Settings content
    }
}</code></pre>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-download text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Full Documentation:</strong> Download the complete SDK documentation with all platforms:
                                    <a href="{{ route('docs.analytics-sdk') }}" target="_blank" class="underline font-medium">ANALYTICS_SDK_INTEGRATION.md</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Laravel Tab --}}
            <div x-show="activeTab === 'laravel'" x-cloak>
                <div class="prose max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fab fa-laravel mr-2 text-red-500"></i>Laravel Integration
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Add Vitalytics Analytics to your Laravel application for server-side tracking.
                    </p>

                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-link text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong>Shared Configuration:</strong> Analytics uses the same base URL, API key, and app identifier
                                    as health monitoring. No separate credentials needed.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Configuration</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <div class="text-sm text-gray-400 mb-2">config/vitalytics.php</div>
                        <pre class="text-gray-100 text-sm"><code>return [
    // These are the same values you use for health monitoring
    'base_url' =&gt; env('VITALYTICS_BASE_URL', 'https://vitalytics.yourserver.com'),
    'api_key' =&gt; env('VITALYTICS_API_KEY'),
    'app_identifier' =&gt; env('VITALYTICS_APP_IDENTIFIER', 'myapp-laravel'),
    'analytics_enabled' =&gt; env('VITALYTICS_ANALYTICS_ENABLED', false),
];</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Service Provider Setup</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// In AppServiceProvider boot() method
VitalyticsAnalytics::instance()
    -&gt;configure(
        config('vitalytics.base_url'),        // Same as health monitoring
        config('vitalytics.api_key'),         // Same as health monitoring
        config('vitalytics.app_identifier'),  // Same as health monitoring
        config('app.env') !== 'production'    // isTest
    )
    -&gt;setEnabled(config('vitalytics.analytics_enabled', false));</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">
                        <i class="fas fa-code mr-2"></i>Complete SDK Class
                    </h3>
                    <p class="text-gray-600 mb-3">Create this file at <code class="bg-gray-200 px-1 rounded">app/Services/VitalyticsAnalytics.php</code>:</p>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 500px;">
                        <pre class="text-gray-100 text-sm"><code>&lt;?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class VitalyticsAnalytics
{
    private static ?self $instance = null;

    private string $baseUrl;
    private string $apiKey;
    private string $appIdentifier;
    private bool $enabled = false; // Disabled by default
    private bool $isTest = false;

    private array $eventQueue = [];
    private string $sessionId;
    private ?string $deviceId = null;

    private function __construct()
    {
        $this-&gt;sessionId = (string) Str::uuid();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function configure(string $baseUrl, string $apiKey, string $appIdentifier, bool $isTest = false): self
    {
        $this-&gt;baseUrl = rtrim($baseUrl, '/');
        $this-&gt;apiKey = $apiKey;
        $this-&gt;appIdentifier = $appIdentifier;
        $this-&gt;isTest = $isTest;
        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this-&gt;enabled = $enabled;
        return $this;
    }

    public function setDeviceId(string $deviceId): self
    {
        $this-&gt;deviceId = $deviceId;
        return $this;
    }

    private ?string $currentScreen = null;

    public function trackScreen(string $screenName, array $properties = []): self
    {
        $this-&gt;currentScreen = $screenName;
        return $this-&gt;trackEvent('screen_viewed', 'navigation', array_merge(
            ['screen' =&gt; $screenName],
            $properties
        ));
    }

    public function trackEvent(string $name, string $category, array $properties = []): self
    {
        if (!$this-&gt;enabled) {
            return $this;
        }

        // IMPORTANT: All timestamps must be UTC
        $event = [
            'id' =&gt; (string) Str::uuid(),
            'timestamp' =&gt; now()-&gt;utc()-&gt;toIso8601String(), // Must be UTC!
            'name' =&gt; $name,
            'category' =&gt; $category,
            'properties' =&gt; $properties ?: null,
        ];

        // Include current screen context if available
        if ($this-&gt;currentScreen) {
            $event['screen'] = $this-&gt;currentScreen;
        }

        $this-&gt;eventQueue[] = $event;

        if (count($this-&gt;eventQueue) &gt;= 20) {
            $this-&gt;flush();
        }

        return $this;
    }

    public function trackApiCall(string $endpoint, string $method, int $statusCode, int $durationMs): self
    {
        return $this-&gt;trackEvent('api_called', 'api', [
            'endpoint' =&gt; $endpoint,
            'method' =&gt; $method,
            'status_code' =&gt; $statusCode,
            'duration_ms' =&gt; $durationMs,
        ]);
    }

    public function trackFeature(string $featureName, array $properties = []): self
    {
        return $this-&gt;trackEvent('feature_used', 'feature', array_merge(
            ['feature' =&gt; $featureName],
            $properties
        ));
    }

    public function trackJob(string $jobName, string $status, array $properties = []): self
    {
        return $this-&gt;trackEvent('job_' . $status, 'background', array_merge(
            ['job' =&gt; $jobName],
            $properties
        ));
    }

    public function flush(): bool
    {
        if (!$this-&gt;enabled || empty($this-&gt;eventQueue)) {
            return true;
        }

        $events = $this-&gt;eventQueue;
        $this-&gt;eventQueue = [];

        $batch = [
            'batchId' =&gt; (string) Str::uuid(),
            'appIdentifier' =&gt; $this-&gt;appIdentifier,
            'deviceInfo' =&gt; [
                'deviceId' =&gt; $this-&gt;deviceId ?? $this-&gt;getServerDeviceId(),
                'deviceModel' =&gt; gethostname(),
                'platform' =&gt; 'laravel',
                'osVersion' =&gt; PHP_OS,
                'appVersion' =&gt; config('app.version', '1.0.0'),
            ],
            'sessionId' =&gt; $this-&gt;sessionId,
            'isTest' =&gt; $this-&gt;isTest,
            'sentAt' =&gt; now()-&gt;utc()-&gt;toIso8601String(), // Must be UTC!
            'events' =&gt; $events,
        ];

        try {
            $response = Http::withHeaders([
                'X-API-Key' =&gt; $this-&gt;apiKey,
            ])-&gt;post($this-&gt;baseUrl . '/api/v1/analytics/events', $batch);

            if (!$response-&gt;successful()) {
                // Re-queue on failure
                $this-&gt;eventQueue = array_merge($events, $this-&gt;eventQueue);
                Log::warning('Analytics flush failed', ['status' =&gt; $response-&gt;status()]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            // Re-queue on error
            $this-&gt;eventQueue = array_merge($events, $this-&gt;eventQueue);
            Log::warning('Analytics flush error', ['error' =&gt; $e-&gt;getMessage()]);
            return false;
        }
    }

    private function getServerDeviceId(): string
    {
        $cacheFile = storage_path('framework/.vitalytics_device_id');

        if (file_exists($cacheFile)) {
            return trim(file_get_contents($cacheFile));
        }

        $deviceId = 'srv-' . Str::uuid();
        file_put_contents($cacheFile, $deviceId);
        return $deviceId;
    }

    public function __destruct()
    {
        $this-&gt;flush();
    }
}</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Usage Examples</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// Track API calls (in middleware)
VitalyticsAnalytics::instance()-&gt;trackApiCall(
    $request-&gt;path(),
    $request-&gt;method(),
    $response-&gt;status(),
    $durationMs
);

// Track page views
VitalyticsAnalytics::instance()-&gt;trackScreen('Dashboard');

// Track features
VitalyticsAnalytics::instance()-&gt;trackFeature('report_generated', [
    'type' =&gt; 'monthly',
    'format' =&gt; 'pdf'
]);

// Track background jobs
VitalyticsAnalytics::instance()-&gt;trackJob('ProcessInvoice', 'completed', [
    'invoice_id' =&gt; $invoice-&gt;id
]);

// Track admin actions
VitalyticsAnalytics::instance()-&gt;trackEvent('user_created', 'admin', [
    'role' =&gt; 'editor'
]);</code></pre>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-download text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Full Documentation:</strong> Download the complete SDK documentation with all platforms:
                                    <a href="{{ route('docs.analytics-sdk') }}" target="_blank" class="underline font-medium">ANALYTICS_SDK_INTEGRATION.md</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- .NET Tab --}}
            <div x-show="activeTab === 'dotnet'" x-cloak>
                <div class="prose max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fab fa-microsoft mr-2 text-blue-600"></i>.NET Integration (Windows)
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Add Vitalytics Analytics to your .NET Windows application.
                    </p>

                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-link text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong>Shared Configuration:</strong> Analytics uses the same base URL, API key, and app identifier
                                    as health monitoring. No separate credentials needed.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// Initialize (use the same config as health monitoring)
VitalyticsAnalytics.Instance.Initialize(
    baseUrl: "https://vitalytics.yourserver.com",  // Same as health
    apiKey: "your-api-key",                        // Same as health
    appIdentifier: "myapp-windows",                // Same as health
    isTest: false  // Set true during development
);

// Enable after user consent
VitalyticsAnalytics.Instance.SetEnabled(true);

// Track screens
VitalyticsAnalytics.Instance.TrackScreen("MainWindow");
VitalyticsAnalytics.Instance.TrackScreen("SettingsWindow", new Dictionary&lt;string, object&gt; {
    ["source"] = "menu"
});

// Track interactions
VitalyticsAnalytics.Instance.TrackClick("save-button");
VitalyticsAnalytics.Instance.TrackFeature("dark-mode", new Dictionary&lt;string, object&gt; {
    ["enabled"] = true
});

// Track custom events
VitalyticsAnalytics.Instance.TrackEvent("file_exported", "feature", new Dictionary&lt;string, object&gt; {
    ["format"] = "pdf",
    ["pageCount"] = 25
});</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">
                        <i class="fas fa-code mr-2"></i>Complete SDK Class with DTOs
                    </h3>
                    <p class="text-gray-600 mb-3">Copy this complete implementation into your project:</p>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 500px;">
                        <pre class="text-gray-100 text-sm"><code>using System.Net.Http.Json;
using System.Text.Json;
using System.Text.Json.Serialization;

namespace YourApp.Services
{
    public class VitalyticsAnalytics
    {
        private static VitalyticsAnalytics? _instance;
        private static readonly object _lock = new();

        public static VitalyticsAnalytics Instance
        {
            get
            {
                if (_instance == null)
                {
                    lock (_lock) { _instance ??= new VitalyticsAnalytics(); }
                }
                return _instance;
            }
        }

        private readonly HttpClient _httpClient;
        private readonly List&lt;AnalyticsEvent&gt; _eventQueue = new();
        private readonly object _queueLock = new();

        private string _baseUrl = "";
        private string _apiKey = "";
        private string _appIdentifier = "";
        private bool _enabled = false; // Disabled by default
        private bool _isTest = false;

        private string _sessionId = Guid.NewGuid().ToString();
        private string? _currentScreen;
        private DateTime? _screenStartTime;
        private Timer? _flushTimer;

        private VitalyticsAnalytics()
        {
            _httpClient = new HttpClient { Timeout = TimeSpan.FromSeconds(30) };
        }

        public void Initialize(string baseUrl, string apiKey, string appIdentifier, bool isTest = false)
        {
            _baseUrl = baseUrl.TrimEnd('/');
            _apiKey = apiKey;
            _appIdentifier = appIdentifier;
            _isTest = isTest;

            _httpClient.DefaultRequestHeaders.Clear();
            _httpClient.DefaultRequestHeaders.Add("X-API-Key", _apiKey);
        }

        public bool IsEnabled =&gt; _enabled;

        public void SetEnabled(bool enabled)
        {
            _enabled = enabled;
            if (enabled)
            {
                StartSession();
            }
            else
            {
                StopSession();
                lock (_queueLock) { _eventQueue.Clear(); }
            }
        }

        public void TrackScreen(string screenName, Dictionary&lt;string, object&gt;? properties = null)
        {
            if (!_enabled) return;

            // Track duration on previous screen
            if (_currentScreen != null &amp;&amp; _screenStartTime.HasValue)
            {
                var duration = (int)(DateTime.UtcNow - _screenStartTime.Value).TotalMilliseconds;
                QueueEvent("screen_duration", "navigation", _currentScreen, duration: duration);
            }

            _currentScreen = screenName;
            _screenStartTime = DateTime.UtcNow;

            QueueEvent("screen_viewed", "navigation", screenName, properties);
        }

        public void TrackEvent(string name, string category, Dictionary&lt;string, object&gt;? properties = null)
        {
            if (!_enabled) return;
            QueueEvent(name, category, _currentScreen, properties);
        }

        public void TrackClick(string elementId, Dictionary&lt;string, object&gt;? properties = null)
        {
            var props = properties ?? new Dictionary&lt;string, object&gt;();
            props["element"] = elementId;
            TrackEvent("button_clicked", "interaction", props);
        }

        public void TrackFeature(string featureName, Dictionary&lt;string, object&gt;? properties = null)
        {
            var props = properties ?? new Dictionary&lt;string, object&gt;();
            props["feature"] = featureName;
            TrackEvent("feature_used", "feature", props);
        }

        private void QueueEvent(string name, string category, string? screen = null,
            Dictionary&lt;string, object&gt;? properties = null, int? duration = null)
        {
            var evt = new AnalyticsEvent
            {
                Name = name,
                Category = category,
                Screen = screen,
                Properties = properties,
                Duration = duration
            };

            lock (_queueLock)
            {
                _eventQueue.Add(evt);
                if (_eventQueue.Count &gt;= 20)
                {
                    _ = FlushAsync();
                }
            }
        }

        public async Task FlushAsync()
        {
            if (!_enabled) return;

            List&lt;AnalyticsEvent&gt; eventsToSend;
            lock (_queueLock)
            {
                if (_eventQueue.Count == 0) return;
                eventsToSend = new List&lt;AnalyticsEvent&gt;(_eventQueue);
                _eventQueue.Clear();
            }

            var batch = new AnalyticsBatch
            {
                AppIdentifier = _appIdentifier,
                SessionId = _sessionId,
                IsTest = _isTest,
                DeviceInfo = GetDeviceInfo(),
                Events = eventsToSend
            };

            try
            {
                var response = await _httpClient.PostAsJsonAsync(
                    $"{_baseUrl}/api/v1/analytics/events", batch);
                response.EnsureSuccessStatusCode();
            }
            catch
            {
                // Re-queue on failure
                lock (_queueLock)
                {
                    _eventQueue.InsertRange(0, eventsToSend);
                }
            }
        }

        private AnalyticsDeviceInfo GetDeviceInfo()
        {
            return new AnalyticsDeviceInfo
            {
                DeviceId = GetDeviceId(),
                DeviceModel = Environment.MachineName,
                Platform = "windows",
                OsVersion = Environment.OSVersion.ToString(),
                AppVersion = "1.0.0"
            };
        }

        private string GetDeviceId()
        {
            var path = Path.Combine(AppContext.BaseDirectory, ".vitalytics_analytics_device_id");
            if (File.Exists(path))
                return File.ReadAllText(path).Trim();

            var deviceId = $"dev-{Guid.NewGuid()}";
            File.WriteAllText(path, deviceId);
            return deviceId;
        }

        private void StartSession()
        {
            _sessionId = Guid.NewGuid().ToString();
            QueueEvent("session_started", "session");

            _flushTimer = new Timer(_ =&gt; _ = FlushAsync(), null,
                TimeSpan.FromSeconds(30), TimeSpan.FromSeconds(30));
        }

        private void StopSession()
        {
            _flushTimer?.Dispose();
            _flushTimer = null;
        }
    }

    // DTOs - IMPORTANT: All timestamps must be UTC (GMT+0)
    public class AnalyticsEvent
    {
        [JsonPropertyName("id")]
        public string Id { get; set; } = Guid.NewGuid().ToString();

        [JsonPropertyName("timestamp")]
        public string Timestamp { get; set; } = DateTime.UtcNow.ToString("o"); // Must use UtcNow!

        [JsonPropertyName("name")]
        public string Name { get; set; } = "";

        [JsonPropertyName("category")]
        public string Category { get; set; } = "";

        [JsonPropertyName("screen")]
        public string? Screen { get; set; }

        [JsonPropertyName("properties")]
        public Dictionary&lt;string, object&gt;? Properties { get; set; }

        [JsonPropertyName("duration")]
        public int? Duration { get; set; }
    }

    public class AnalyticsDeviceInfo
    {
        [JsonPropertyName("deviceId")]
        public string DeviceId { get; set; } = "";

        [JsonPropertyName("deviceModel")]
        public string? DeviceModel { get; set; }

        [JsonPropertyName("platform")]
        public string Platform { get; set; } = "windows";

        [JsonPropertyName("osVersion")]
        public string? OsVersion { get; set; }

        [JsonPropertyName("appVersion")]
        public string? AppVersion { get; set; }
    }

    public class AnalyticsBatch
    {
        [JsonPropertyName("batchId")]
        public string BatchId { get; set; } = Guid.NewGuid().ToString();

        [JsonPropertyName("appIdentifier")]
        public string AppIdentifier { get; set; } = "";

        [JsonPropertyName("deviceInfo")]
        public AnalyticsDeviceInfo DeviceInfo { get; set; } = new();

        [JsonPropertyName("sessionId")]
        public string SessionId { get; set; } = "";

        [JsonPropertyName("isTest")]
        public bool IsTest { get; set; }

        [JsonPropertyName("sentAt")]
        public string SentAt { get; set; } = DateTime.UtcNow.ToString("o");

        [JsonPropertyName("events")]
        public List&lt;AnalyticsEvent&gt; Events { get; set; } = new();
    }
}</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">WPF Integration</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// In App.xaml.cs
protected override void OnStartup(StartupEventArgs e)
{
    base.OnStartup(e);

    VitalyticsAnalytics.Instance.Initialize(
        baseUrl: ConfigurationManager.AppSettings["VitalyticsBaseUrl"],
        apiKey: ConfigurationManager.AppSettings["VitalyticsApiKey"],
        appIdentifier: "myapp-windows"
    );

    // Check stored user preference
    if (Settings.Default.AnalyticsConsent)
    {
        VitalyticsAnalytics.Instance.SetEnabled(true);
    }
}

protected override void OnExit(ExitEventArgs e)
{
    // Flush remaining events
    VitalyticsAnalytics.Instance.FlushAsync().Wait(TimeSpan.FromSeconds(5));
    base.OnExit(e);
}

// In each Window
private void Window_Loaded(object sender, RoutedEventArgs e)
{
    VitalyticsAnalytics.Instance.TrackScreen(this.GetType().Name);
}</code></pre>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-download text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Full Documentation:</strong> Download the complete SDK documentation with all platforms:
                                    <a href="{{ route('docs.analytics-sdk') }}" target="_blank" class="underline font-medium">ANALYTICS_SDK_INTEGRATION.md</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

</x-app-layout>
