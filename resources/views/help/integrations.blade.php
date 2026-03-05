<x-app-layout>

<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-book mr-2"></i> Integration Guide
        </h1>
        <p class="text-gray-600 mt-2">Learn how to integrate Vitalytics into your applications</p>
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
                <button @click="activeTab = 'auth'"
                    :class="activeTab === 'auth' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fas fa-key mr-2"></i>Authentication
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
                <button @click="activeTab = 'javascript'"
                    :class="activeTab === 'javascript' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                    <i class="fab fa-js mr-2"></i>JavaScript
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
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">What is Vitalytics?</h2>
                    <p class="text-gray-700 mb-6">
                        Vitalytics is a real-time health monitoring service for your applications. It collects crash reports,
                        errors, warnings, and other health events to give you visibility into the health and stability of
                        your products across all platforms.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-blue-800 mb-3">
                                <i class="fas fa-bug mr-2"></i>Crash Reporting
                            </h3>
                            <p class="text-blue-700">
                                Automatically capture unhandled exceptions and crashes with full stack traces,
                                device information, and context.
                            </p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-green-800 mb-3">
                                <i class="fas fa-heartbeat mr-2"></i>Health Monitoring
                            </h3>
                            <p class="text-green-700">
                                Monitor application health with heartbeats, track errors, and get real-time
                                health scores for each product.
                            </p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-purple-800 mb-3">
                                <i class="fas fa-wifi mr-2"></i>Network Monitoring
                            </h3>
                            <p class="text-purple-700">
                                Track API failures, timeouts, and connectivity issues to identify
                                network-related problems quickly.
                            </p>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-orange-800 mb-3">
                                <i class="fas fa-mobile-alt mr-2"></i>Multi-Platform
                            </h3>
                            <p class="text-orange-700">
                                Support for iOS, Android, Web, Chrome Extensions, Windows, and macOS
                                applications from a single dashboard.
                            </p>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">How It Works</h2>
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <ol class="list-decimal list-inside space-y-4 text-gray-700">
                            <li><strong>Integrate the SDK</strong> - Add the Vitalytics SDK to your application using the code samples for your platform.</li>
                            <li><strong>Configure Authentication</strong> - Bundle an app secret with your app and retrieve API keys dynamically (see Authentication tab).</li>
                            <li><strong>Log Events</strong> - Call the SDK methods to log crashes, errors, warnings, info events, and heartbeats.</li>
                            <li><strong>Events are Batched</strong> - The SDK automatically batches events and sends them periodically (every 30 seconds or when critical events occur).</li>
                            <li><strong>View in Dashboard</strong> - Monitor all events in real-time from the Vitalytics dashboard.</li>
                        </ol>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">API Details</h2>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-400 mb-2">Base URL</div>
                        <code class="text-green-400 text-lg">https://api.vitalytics.app/api</code>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-400 mb-2">Required Headers for Event Submission</div>
                        <pre class="text-gray-100 text-sm overflow-x-auto">X-API-Key: your-api-key-here
X-App-Identifier: your-app-identifier
Content-Type: application/json</pre>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Dynamic API Keys:</strong> Instead of bundling API keys directly with your app,
                                    use the <button @click="activeTab = 'auth'" class="underline font-semibold">Authentication</button>
                                    system to retrieve API keys dynamically using app secrets.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Authentication Tab --}}
            <div x-show="activeTab === 'auth'" x-cloak>
                <div class="prose max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-key mr-2 text-yellow-500"></i>API Key Authentication
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Vitalytics uses a two-tier authentication system that allows you to rotate API keys without
                        requiring app updates. Instead of bundling API keys directly, you bundle an <strong>app secret</strong>
                        and use it to retrieve time-limited API keys dynamically.
                    </p>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-lightbulb text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Why use app secrets?</strong> If an API key is ever compromised, you can regenerate it
                                    server-side and all clients will automatically get the new key on their next request.
                                    No app update required!
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">How It Works</h3>
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <ol class="list-decimal list-inside space-y-4 text-gray-700">
                            <li><strong>Admin generates an app secret</strong> - For each app identifier (e.g., "myapp-ios", "myapp-chrome"), an admin generates a secret in the Secrets management page.</li>
                            <li><strong>Bundle the secret</strong> - Include the app secret in your application bundle (NOT the API key).</li>
                            <li><strong>Request API key at startup</strong> - Call the auth endpoint with your app secret to get a time-limited API key.</li>
                            <li><strong>Cache and use the API key</strong> - Store the API key and use it for all event submissions until it expires.</li>
                            <li><strong>Refresh when needed</strong> - Request a new API key when the current one expires (typically after 24 hours).</li>
                        </ol>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">API Endpoint</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-400 mb-2">GET /api/v1/auth/key/{appIdentifier}</div>
                        <div class="text-sm text-gray-400 mt-4 mb-2">Request Headers</div>
                        <pre class="text-gray-100 text-sm overflow-x-auto">X-App-Secret: your-app-secret-here</pre>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-400 mb-2">Success Response (200 OK)</div>
                        <pre class="text-gray-100 text-sm overflow-x-auto">{
    "api_key": "vit_abc123...",
    "expires_at": "2026-01-08T12:00:00Z",
    "expires_in": 86400
}</pre>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-400 mb-2">Error Responses</div>
                        <pre class="text-gray-100 text-sm overflow-x-auto">// 401 Unauthorized - Missing or invalid secret
{ "error": "Invalid or expired app secret" }

// 429 Too Many Requests - Rate limited (10 requests/hour per IP)
{ "error": "Too many requests. Please try again later." }
Headers: Retry-After: 3600</pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Secret Rotation</h3>
                    <p class="text-gray-700 mb-4">
                        When you need to rotate a secret (e.g., if it's been compromised), the system supports a
                        <strong>grace period</strong> where both the old and new secrets are valid simultaneously.
                        This ensures existing app installations continue to work while the new secret is deployed.
                    </p>
                    <div class="bg-blue-50 rounded-lg p-6 mb-6">
                        <h4 class="text-lg font-semibold text-blue-800 mb-3">Rotation Workflow</h4>
                        <ol class="list-decimal list-inside space-y-2 text-blue-700">
                            <li>Generate a new secret (admin sets expiry date for old secret)</li>
                            <li>Deploy new secret to app stores</li>
                            <li>Both secrets work during the grace period</li>
                            <li>Old secret automatically expires and stops working</li>
                        </ol>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">JavaScript Example</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>class VitalyticsAuth {
    constructor(appSecret, appIdentifier) {
        this.appSecret = appSecret;
        this.appIdentifier = appIdentifier;
        this.cachedApiKey = null;
        this.keyExpiresAt = null;
    }

    async getApiKey() {
        // Return cached key if still valid (with 5 min buffer)
        if (this.cachedApiKey &amp;&amp; this.keyExpiresAt) {
            const bufferTime = 5 * 60 * 1000; // 5 minutes
            if (Date.now() &lt; this.keyExpiresAt - bufferTime) {
                return this.cachedApiKey;
            }
        }

        // Fetch new API key
        const response = await fetch(
            'https://api.vitalytics.app/api/v1/auth/key/' + this.appIdentifier,
            {
                method: 'GET',
                headers: { 'X-App-Secret': this.appSecret }
            }
        );

        if (!response.ok) {
            throw new Error('Auth failed: ' + response.status);
        }

        const data = await response.json();
        this.cachedApiKey = data.api_key;
        this.keyExpiresAt = new Date(data.expires_at).getTime();

        return this.cachedApiKey;
    }
}

// Usage
const auth = new VitalyticsAuth('your-app-secret', 'your-app-identifier');

async function initializeVitalytics() {
    const apiKey = await auth.getApiKey();

    const vitalytics = new VitalyticsSDK({
        apiKey: apiKey,
        appIdentifier: 'your-app-identifier',
        // ... other config
    });

    return vitalytics;
}</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Rate Limiting</h3>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    The auth endpoint is rate-limited to <strong>10 requests per hour per IP address</strong>.
                                    Always cache the API key and only request a new one when it's about to expire.
                                    API keys are valid for 24 hours by default.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Best Practices</h3>
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <ul class="list-disc list-inside space-y-2 text-gray-700">
                            <li><strong>Cache the API key</strong> - Store it in memory or secure storage (Keychain, SharedPreferences).</li>
                            <li><strong>Refresh proactively</strong> - Request a new key when the current one has less than 5 minutes until expiry.</li>
                            <li><strong>Handle 429 errors gracefully</strong> - If rate limited, wait for the Retry-After period.</li>
                            <li><strong>Don't expose secrets in logs</strong> - Never log the app secret or API key.</li>
                            <li><strong>Use different secrets per platform</strong> - Each app identifier should have its own secret for easier rotation.</li>
                        </ul>
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
                        Add Vitalytics health monitoring to your iOS or macOS application.
                    </p>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    See the <button @click="activeTab = 'auth'" class="underline font-semibold">Authentication</button>
                                    tab for how to retrieve API keys dynamically using app secrets.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// Initialize with dynamic API key
Task {
    let auth = VitalyticsAuth(appSecret: "your-secret", appIdentifier: "your-app-ios")
    let apiKey = try await auth.getApiKey()

    VitalyticsSDK.shared.configure(
        apiKey: apiKey,
        appIdentifier: "your-app-ios",
        environment: "production"
    )
}

// Log events
VitalyticsSDK.shared.logError(
    message: "Failed to load profile",
    context: ["userId": "123"]
)

VitalyticsSDK.shared.logHeartbeat()</code></pre>
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
                        Add Vitalytics health monitoring to your Android application.
                    </p>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    See the <button @click="activeTab = 'auth'" class="underline font-semibold">Authentication</button>
                                    tab for how to retrieve API keys dynamically using app secrets.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Dependencies</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>dependencies {
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
    implementation("com.google.code.gson:gson:2.10.1")
}</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// In Application class
class MyApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        lifecycleScope.launch {
            val auth = VitalyticsAuth("your-secret", "your-app-android")
            val apiKey = auth.getApiKey()

            VitalyticsSDK.initialize(
                context = this@MyApplication,
                apiKey = apiKey,
                appIdentifier = "your-app-android"
            )
        }
    }
}

// Log events anywhere
VitalyticsSDK.logError(
    message = "Failed to load profile",
    context = mapOf("userId" to "123")
)

VitalyticsSDK.logHeartbeat()</code></pre>
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
                        Add Vitalytics health monitoring to your web application or Chrome extension.
                    </p>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    See the <button @click="activeTab = 'auth'" class="underline font-semibold">Authentication</button>
                                    tab for how to retrieve API keys dynamically using app secrets.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// Initialize with dynamic API key
const auth = new VitalyticsAuth('your-secret', 'your-app-chrome');
const apiKey = await auth.getApiKey();

const vitalytics = new VitalyticsSDK({
    apiKey: apiKey,
    appIdentifier: 'your-app-chrome',
    deviceInfo: {
        deviceId: getOrCreateDeviceId(),
        deviceModel: navigator.userAgent,
        osVersion: navigator.platform,
        appVersion: '1.0.0',
        platform: 'chrome'
    }
});

// Log events
vitalytics.logError('Failed to load profile', { userId: '123' });
vitalytics.logNetworkError('API timeout', { endpoint: '/api/data' });
vitalytics.logHeartbeat({ page: 'dashboard' });</code></pre>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Chrome Extension Storage</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <pre class="text-gray-100 text-sm"><code>// Use chrome.storage for device ID
async function getOrCreateDeviceId() {
    const result = await chrome.storage.local.get('vitalytics_device_id');
    if (result.vitalytics_device_id) {
        return result.vitalytics_device_id;
    }
    const deviceId = 'dev-' + crypto.randomUUID();
    await chrome.storage.local.set({ vitalytics_device_id: deviceId });
    return deviceId;
}</code></pre>
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
                        Add Vitalytics health monitoring to your .NET Windows application or service.
                        This guide covers configuration options, service architecture patterns, and recommended event types.
                    </p>

                    {{-- Table of Contents --}}
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Contents</h4>
                        <ul class="text-sm text-blue-600 space-y-1">
                            <li><a href="#dotnet-config" class="hover:underline">Configuration Options</a></li>
                            <li><a href="#dotnet-models" class="hover:underline">Data Models</a></li>
                            <li><a href="#dotnet-service" class="hover:underline">Service Implementation</a></li>
                            <li><a href="#dotnet-events" class="hover:underline">Event Types</a></li>
                            <li><a href="#dotnet-integration" class="hover:underline">Integration Points</a></li>
                        </ul>
                    </div>

                    {{-- Configuration Section --}}
                    <h3 id="dotnet-config" class="text-xl font-semibold text-gray-900 mb-3">Configuration Options</h3>
                    <p class="text-gray-700 mb-4">
                        You can store Vitalytics configuration using your existing project patterns. Here are two common approaches:
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-file-code mr-2"></i>Option A: appsettings.json</h4>
                            <p class="text-sm text-blue-700 mb-2">Standard .NET configuration pattern</p>
                            <div class="bg-gray-900 rounded p-3 mt-2">
                                <pre class="text-gray-100 text-xs overflow-x-auto"><code>{
  "Vitalytics": {
    "Enabled": true,
    "ApiKey": "vtx_your_key_here",
    "ApiBaseUrl": "https://api.vitalytics.app/api/v1",
    "AppIdentifier": "myapp-windows"
  }
}</code></pre>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <h4 class="font-semibold text-green-800 mb-2"><i class="fas fa-cog mr-2"></i>Option B: INI Files</h4>
                            <p class="text-sm text-green-700 mb-2">Match existing project configuration patterns</p>
                            <div class="bg-gray-900 rounded p-3 mt-2">
                                <pre class="text-gray-100 text-xs overflow-x-auto"><code># localconfig.ini
Vitalytics_Enabled=true

# centralconfig.ini
Vitalytics_ApiKey=&lt;encrypted_base64&gt;
Vitalytics_ApiBaseUrl=https://api.vitalytics.app/api/v1</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-shield-alt text-yellow-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Security Recommendation:</strong> If your project already encrypts sensitive config values
                                    (like API tokens), apply the same encryption to the Vitalytics API key for consistency.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Required Settings Table --}}
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Required Settings</h4>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Setting</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Type</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">Enabled</td>
                                    <td class="px-4 py-2">bool</td>
                                    <td class="px-4 py-2">Enable/disable Vitalytics (useful for local dev)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">ApiKey</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">Your Vitalytics API key (vtx_...)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">ApiBaseUrl</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">API endpoint URL</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-blue-600">AppIdentifier</td>
                                    <td class="px-4 py-2">string</td>
                                    <td class="px-4 py-2">Your app identifier (e.g., myapp-windows)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Data Models Section --}}
                    <h3 id="dotnet-models" class="text-xl font-semibold text-gray-900 mb-3">Data Models</h3>
                    <p class="text-gray-700 mb-4">Create a models file for the Vitalytics API request/response DTOs:</p>

                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <div class="text-sm text-gray-400 mb-2">Models/VitalyticsModels.cs</div>
                        <pre class="text-gray-100 text-sm"><code>using System.Text.Json.Serialization;

namespace YourApp.Models
{
    public class VitalyticsEvent
    {
        [JsonPropertyName("id")]
        public string Id { get; set; } = Guid.NewGuid().ToString();

        [JsonPropertyName("timestamp")]
        public string Timestamp { get; set; } = DateTime.UtcNow.ToString("o");

        [JsonPropertyName("level")]
        public string Level { get; set; } = "info";

        [JsonPropertyName("message")]
        public string Message { get; set; } = "";

        [JsonPropertyName("metadata")]
        public Dictionary&lt;string, object&gt;? Metadata { get; set; }

        [JsonPropertyName("stackTrace")]
        public List&lt;string&gt;? StackTrace { get; set; }
    }

    public class VitalyticsDeviceInfo
    {
        [JsonPropertyName("deviceId")]
        public string DeviceId { get; set; } = "";

        [JsonPropertyName("deviceModel")]
        public string? DeviceModel { get; set; }

        [JsonPropertyName("osVersion")]
        public string? OsVersion { get; set; }

        [JsonPropertyName("appVersion")]
        public string? AppVersion { get; set; }

        [JsonPropertyName("buildNumber")]
        public string? BuildNumber { get; set; }

        [JsonPropertyName("platform")]
        public string Platform { get; set; } = "windows";
    }

    public class VitalyticsBatch
    {
        [JsonPropertyName("batchId")]
        public string BatchId { get; set; } = Guid.NewGuid().ToString();

        [JsonPropertyName("deviceInfo")]
        public VitalyticsDeviceInfo DeviceInfo { get; set; } = new();

        [JsonPropertyName("appIdentifier")]
        public string AppIdentifier { get; set; } = "";

        [JsonPropertyName("environment")]
        public string Environment { get; set; } = "production";

        [JsonPropertyName("userId")]
        public string? UserId { get; set; }

        [JsonPropertyName("events")]
        public List&lt;VitalyticsEvent&gt; Events { get; set; } = new();

        [JsonPropertyName("sentAt")]
        public string SentAt { get; set; } = DateTime.UtcNow.ToString("o");

        [JsonPropertyName("isTest")]
        public bool IsTest { get; set; } = false;
    }
}</code></pre>
                    </div>

                    {{-- Service Implementation Section --}}
                    <h3 id="dotnet-service" class="text-xl font-semibold text-gray-900 mb-3">Service Implementation</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-syringe mr-2"></i>Option A: Dependency Injection</h4>
                            <p class="text-sm text-blue-700">Standard .NET DI pattern with IVitalyticsService interface</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <h4 class="font-semibold text-green-800 mb-2"><i class="fas fa-cube mr-2"></i>Option B: Singleton</h4>
                            <p class="text-sm text-green-700">Match existing static service patterns (no Program.cs changes needed)</p>
                        </div>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <div class="text-sm text-gray-400 mb-2">Services/VitalyticsService.cs (Singleton Pattern)</div>
                        <pre class="text-gray-100 text-sm"><code>using System.Net.Http.Json;
using System.Text.Json;
using YourApp.Models;

namespace YourApp.Services
{
    public class VitalyticsService
    {
        private static VitalyticsService? _instance;
        private static readonly object _lock = new();

        public static VitalyticsService Instance
        {
            get
            {
                if (_instance == null)
                {
                    lock (_lock)
                    {
                        _instance ??= new VitalyticsService();
                    }
                }
                return _instance;
            }
        }

        private readonly HttpClient _httpClient;
        private readonly List&lt;VitalyticsEvent&gt; _eventQueue = new();
        private readonly object _queueLock = new();

        private string _apiKey = "";
        private string _apiBaseUrl = "";
        private string _appIdentifier = "";
        private string _deviceId = "";
        private string _deviceModel = "";
        private string _appVersion = "";
        private bool _enabled = false;
        private bool _isTest = false;

        private VitalyticsService()
        {
            _httpClient = new HttpClient();
            _httpClient.Timeout = TimeSpan.FromSeconds(30);
        }

        public void Initialize(
            string apiKey,
            string apiBaseUrl,
            string appIdentifier,
            string deviceId,
            string? deviceModel = null,
            string? appVersion = null,
            bool isTest = false)
        {
            _apiKey = apiKey;
            _apiBaseUrl = apiBaseUrl.TrimEnd('/');
            _appIdentifier = appIdentifier;
            _deviceId = deviceId;
            _deviceModel = deviceModel ?? Environment.MachineName;
            _appVersion = appVersion ?? "1.0.0";
            _isTest = isTest;
            _enabled = !string.IsNullOrEmpty(apiKey);

            _httpClient.DefaultRequestHeaders.Clear();
            _httpClient.DefaultRequestHeaders.Add("X-API-Key", _apiKey);
            _httpClient.DefaultRequestHeaders.Add("X-App-Identifier", _appIdentifier);
        }

        public bool IsEnabled =&gt; _enabled;

        public void LogInfo(string message, Dictionary&lt;string, object&gt;? metadata = null)
            =&gt; QueueEvent("info", message, metadata);

        public void LogWarning(string message, Dictionary&lt;string, object&gt;? metadata = null)
            =&gt; QueueEvent("warning", message, metadata);

        public void LogError(string message, Dictionary&lt;string, object&gt;? metadata = null, Exception? ex = null)
            =&gt; QueueEvent("error", message, metadata, ex);

        public void LogCrash(string message, Exception ex, Dictionary&lt;string, object&gt;? metadata = null)
            =&gt; QueueEvent("crash", message, metadata, ex);

        public void LogNetworkError(string message, Dictionary&lt;string, object&gt;? metadata = null)
            =&gt; QueueEvent("networkError", message, metadata);

        public void LogHeartbeat(Dictionary&lt;string, object&gt;? metadata = null)
            =&gt; QueueEvent("heartbeat", "heartbeat", metadata);

        private void QueueEvent(string level, string message,
            Dictionary&lt;string, object&gt;? metadata = null, Exception? ex = null)
        {
            if (!_enabled) return;

            var evt = new VitalyticsEvent
            {
                Level = level,
                Message = message,
                Metadata = metadata,
                StackTrace = ex?.StackTrace?.Split('\n').ToList()
            };

            lock (_queueLock)
            {
                _eventQueue.Add(evt);
            }

            // Immediately flush critical events
            if (level == "crash" || level == "error")
            {
                _ = FlushAsync();
            }
        }

        public async Task FlushAsync()
        {
            if (!_enabled) return;

            List&lt;VitalyticsEvent&gt; eventsToSend;
            lock (_queueLock)
            {
                if (_eventQueue.Count == 0) return;
                eventsToSend = new List&lt;VitalyticsEvent&gt;(_eventQueue);
                _eventQueue.Clear();
            }

            var batch = new VitalyticsBatch
            {
                AppIdentifier = _appIdentifier,
                IsTest = _isTest,
                Events = eventsToSend,
                DeviceInfo = new VitalyticsDeviceInfo
                {
                    DeviceId = _deviceId,
                    DeviceModel = _deviceModel,
                    OsVersion = Environment.OSVersion.ToString(),
                    AppVersion = _appVersion,
                    Platform = "windows"
                }
            };

            try
            {
                var response = await _httpClient.PostAsJsonAsync(
                    $"{_apiBaseUrl}/health/events", batch);
                response.EnsureSuccessStatusCode();
            }
            catch (Exception ex)
            {
                // Re-queue events on failure
                lock (_queueLock)
                {
                    _eventQueue.InsertRange(0, eventsToSend);
                }
                System.Diagnostics.Debug.WriteLine($"Vitalytics flush failed: {ex.Message}");
            }
        }
    }
}</code></pre>
                    </div>

                    {{-- Device Model Tip --}}
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-lightbulb text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong>Device Model Tip:</strong> Instead of using <code>Environment.MachineName</code> (which returns generic names like "DESKTOP-ABC123"),
                                    consider using a configured location identifier (e.g., "CLINIC-A", "OFFICE-RECEPTION") so you can easily identify devices in the dashboard.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Event Types Section --}}
                    <h3 id="dotnet-events" class="text-xl font-semibold text-gray-900 mb-3">Recommended Event Types</h3>
                    <p class="text-gray-700 mb-4">Here are common events to track in a Windows service or desktop application:</p>

                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Event Type</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Level</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">When to Use</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2 font-mono">service_startup</td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">info</span></td>
                                    <td class="px-4 py-2">Service/app starts (flush immediately)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">service_shutdown</td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">info</span></td>
                                    <td class="px-4 py-2">Service/app stops gracefully</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">heartbeat</td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">heartbeat</span></td>
                                    <td class="px-4 py-2">Periodic health check (every 5-10 minutes)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">upload_success</td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">info</span></td>
                                    <td class="px-4 py-2">File/data uploaded successfully</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">upload_failure</td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">error</span></td>
                                    <td class="px-4 py-2">API returns non-success status</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">config_error</td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">warning</span></td>
                                    <td class="px-4 py-2">Configuration issue detected</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">critical_exception</td>
                                    <td class="px-4 py-2"><span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">crash</span></td>
                                    <td class="px-4 py-2">Unhandled exception caught</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Timing Configuration --}}
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Timing Configuration</h4>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Setting</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Recommended</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2">Heartbeat interval</td>
                                    <td class="px-4 py-2">5-10 minutes</td>
                                    <td class="px-4 py-2">Balance between visibility and API usage</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2">Batch flush interval</td>
                                    <td class="px-4 py-2">30-60 seconds</td>
                                    <td class="px-4 py-2">Or flush with heartbeat timer</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2">Startup event</td>
                                    <td class="px-4 py-2">Immediate flush</td>
                                    <td class="px-4 py-2">Confirms service came online</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2">Error/crash events</td>
                                    <td class="px-4 py-2">Immediate flush</td>
                                    <td class="px-4 py-2">Critical events shouldn't wait</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Integration Points Section --}}
                    <h3 id="dotnet-integration" class="text-xl font-semibold text-gray-900 mb-3">Integration Points</h3>

                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
                        <div class="text-sm text-gray-400 mb-2">Worker Service Example</div>
                        <pre class="text-gray-100 text-sm"><code>public class Worker : BackgroundService
{
    private Timer? _heartbeatTimer;

    public override Task StartAsync(CancellationToken cancellationToken)
    {
        // Initialize Vitalytics
        if (Config.Vitalytics_Enabled)
        {
            VitalyticsService.Instance.Initialize(
                apiKey: Config.Vitalytics_ApiKey,
                apiBaseUrl: Config.Vitalytics_ApiBaseUrl,
                appIdentifier: "myapp-windows",
                deviceId: GetOrCreateDeviceId(),
                deviceModel: Config.LocationIdentifier ?? Environment.MachineName,
                appVersion: Assembly.GetExecutingAssembly().GetName().Version?.ToString(),
                isTest: false  // Set true during initial deployment
            );

            // Log startup and flush immediately
            VitalyticsService.Instance.LogInfo("service_startup");
            _ = VitalyticsService.Instance.FlushAsync();

            // Start heartbeat timer (every 10 minutes)
            _heartbeatTimer = new Timer(HeartbeatCallback, null,
                TimeSpan.FromMinutes(10), TimeSpan.FromMinutes(10));
        }

        return base.StartAsync(cancellationToken);
    }

    private void HeartbeatCallback(object? state)
    {
        VitalyticsService.Instance.LogHeartbeat();
        _ = VitalyticsService.Instance.FlushAsync();
    }

    public override async Task StopAsync(CancellationToken cancellationToken)
    {
        _heartbeatTimer?.Dispose();

        if (VitalyticsService.Instance.IsEnabled)
        {
            VitalyticsService.Instance.LogInfo("service_shutdown");
            await VitalyticsService.Instance.FlushAsync();
        }

        await base.StopAsync(cancellationToken);
    }

    // Catch unhandled exceptions
    private void SetupGlobalExceptionHandling()
    {
        AppDomain.CurrentDomain.UnhandledException += (s, e) =&gt;
        {
            if (e.ExceptionObject is Exception ex)
            {
                VitalyticsService.Instance.LogCrash("critical_exception", ex);
                VitalyticsService.Instance.FlushAsync().Wait(TimeSpan.FromSeconds(5));
            }
        };
    }

    private string GetOrCreateDeviceId()
    {
        var path = Path.Combine(AppContext.BaseDirectory, ".vitalytics_device_id");
        if (File.Exists(path))
            return File.ReadAllText(path).Trim();

        var deviceId = $"dev-{Guid.NewGuid()}";
        File.WriteAllText(path, deviceId);
        return deviceId;
    }
}</code></pre>
                    </div>

                    {{-- Testing Tip --}}
                    <div class="bg-purple-50 border-l-4 border-purple-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-flask text-purple-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-purple-700">
                                    <strong>Testing Tip:</strong> Set <code>isTest: true</code> during initial deployment to verify events
                                    are flowing correctly. Test events can be filtered in the dashboard. Switch to <code>false</code>
                                    once you've confirmed everything works.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- API Headers Reference --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">API Headers Reference</h3>
                    <div class="bg-gray-900 rounded-lg p-4 mb-6">
                        <pre class="text-gray-100 text-sm overflow-x-auto">X-API-Key: vtx_your_api_key_here
X-App-Identifier: myapp-windows
Content-Type: application/json</pre>
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
