<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-android mr-2 text-green-500"></i>Kotlin/Android Health Monitoring
    </h2>
    <p class="text-gray-600 mb-6">Track crashes, errors, warnings, and health events in your Android application.</p>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <h4 class="text-blue-800 font-semibold mb-2"><i class="fas fa-key mr-2"></i>Authentication Options</h4>
        <p class="text-blue-700 mb-2">You have two options for authentication:</p>
        <ol class="text-blue-700 list-decimal ml-4">
            <li><strong>App Secret (Recommended)</strong> - Embed the App Secret in your app. The SDK fetches the API Key dynamically. This allows API key rotation without app updates.</li>
            <li><strong>Direct API Key</strong> - Embed the API Key directly. Simpler but requires app update if key changes.</li>
        </ol>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-code mr-2"></i>Complete SDK Class
    </h3>
    <p class="text-gray-600 mb-3">Copy this class into your project (e.g., <code class="bg-gray-200 px-1 rounded">app/src/main/java/com/yourapp/Vitalytics.kt</code>):</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 600px;">
        <pre class="text-gray-100 text-sm"><code>import android.content.Context
import android.os.Build
import kotlinx.coroutines.*
import org.json.JSONArray
import org.json.JSONObject
import java.io.BufferedReader
import java.net.HttpURLConnection
import java.net.URL
import java.text.SimpleDateFormat
import java.util.*

object Vitalytics {
    private lateinit var context: Context
    private var baseUrl: String = ""
    private var apiKey: String? = null
    private var appSecret: String? = null
    private var appIdentifier: String = ""
    private var deviceId: String = ""
    private var isTest: Boolean = false
    private var enabled: Boolean = true

    private val eventQueue = mutableListOf&lt;JSONObject&gt;()
    private val scope = CoroutineScope(Dispatchers.IO + SupervisorJob())
    private var flushJob: Job? = null

    private const val PREFS_NAME = "vitalytics"
    private const val KEY_DEVICE_ID = "device_id"
    private const val KEY_CACHED_API_KEY = "cached_api_key"
    private const val KEY_API_KEY_EXPIRY = "api_key_expiry"

    /**
     * Initialize with App Secret (RECOMMENDED)
     * The SDK will automatically fetch and cache the API key.
     * This allows API key rotation without app updates.
     */
    fun initializeWithSecret(
        context: Context,
        baseUrl: String,
        appSecret: String,
        appIdentifier: String,
        deviceId: String? = null,
        isTest: Boolean = false,
        onReady: ((Boolean) -> Unit)? = null
    ) {
        this.context = context.applicationContext
        this.baseUrl = baseUrl.trimEnd('/')
        this.appSecret = appSecret
        this.appIdentifier = appIdentifier
        this.deviceId = deviceId ?: getOrCreateDeviceId()
        this.isTest = isTest

        // Try to use cached API key first
        val cachedKey = getCachedApiKey()
        if (cachedKey != null) {
            this.apiKey = cachedKey
            startPeriodicFlush()
            onReady?.invoke(true)
            // Refresh key in background if expiring soon
            scope.launch { refreshApiKeyIfNeeded() }
        } else {
            // Fetch API key before starting
            scope.launch {
                val success = fetchApiKey()
                if (success) {
                    startPeriodicFlush()
                }
                withContext(Dispatchers.Main) {
                    onReady?.invoke(success)
                }
            }
        }
    }

    /**
     * Initialize with API Key directly
     * Use this if you don't need API key rotation.
     */
    fun initialize(
        context: Context,
        baseUrl: String,
        apiKey: String,
        appIdentifier: String,
        deviceId: String? = null,
        isTest: Boolean = false
    ) {
        this.context = context.applicationContext
        this.baseUrl = baseUrl.trimEnd('/')
        this.apiKey = apiKey
        this.appIdentifier = appIdentifier
        this.deviceId = deviceId ?: getOrCreateDeviceId()
        this.isTest = isTest
        startPeriodicFlush()
    }

    private fun startPeriodicFlush() {
        flushJob?.cancel()
        flushJob = scope.launch {
            while (isActive) {
                delay(30_000)
                flush()
            }
        }
    }

    /**
     * Fetch API key using App Secret
     */
    private suspend fun fetchApiKey(): Boolean {
        val secret = appSecret ?: return false

        return withContext(Dispatchers.IO) {
            try {
                val url = URL("$baseUrl/api/v1/auth/key/$appIdentifier")
                val connection = url.openConnection() as HttpURLConnection
                connection.apply {
                    requestMethod = "GET"
                    setRequestProperty("Accept", "application/json")
                    setRequestProperty("X-App-Secret", secret)
                    connectTimeout = 10000
                    readTimeout = 10000
                }

                val responseCode = connection.responseCode
                if (responseCode == 200) {
                    val response = connection.inputStream.bufferedReader().use { it.readText() }
                    val json = JSONObject(response)

                    if (json.getBoolean("success")) {
                        val newApiKey = json.getString("apiKey")
                        val expiresAt = json.optString("expiresAt", "")

                        // Cache the API key
                        apiKey = newApiKey
                        cacheApiKey(newApiKey, expiresAt)
                        connection.disconnect()
                        return@withContext true
                    }
                }
                connection.disconnect()
                false
            } catch (e: Exception) {
                false
            }
        }
    }

    private fun cacheApiKey(key: String, expiresAt: String) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit()
            .putString(KEY_CACHED_API_KEY, key)
            .putString(KEY_API_KEY_EXPIRY, expiresAt)
            .apply()
    }

    private fun getCachedApiKey(): String? {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        val expiry = prefs.getString(KEY_API_KEY_EXPIRY, null)

        // Check if key is expired
        if (expiry != null) {
            try {
                val expiryDate = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
                    .parse(expiry.replace("Z", "").substringBefore("+"))
                if (expiryDate != null && expiryDate.before(Date())) {
                    // Key expired, clear cache
                    prefs.edit().remove(KEY_CACHED_API_KEY).remove(KEY_API_KEY_EXPIRY).apply()
                    return null
                }
            } catch (e: Exception) { /* ignore parse errors */ }
        }

        return prefs.getString(KEY_CACHED_API_KEY, null)
    }

    private suspend fun refreshApiKeyIfNeeded() {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        val expiry = prefs.getString(KEY_API_KEY_EXPIRY, null) ?: return

        try {
            val expiryDate = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
                .parse(expiry.replace("Z", "").substringBefore("+")) ?: return

            // Refresh if expiring within 6 hours
            val sixHoursFromNow = Date(System.currentTimeMillis() + 6 * 60 * 60 * 1000)
            if (expiryDate.before(sixHoursFromNow)) {
                fetchApiKey()
            }
        } catch (e: Exception) { /* ignore */ }
    }

    fun setEnabled(enabled: Boolean) {
        this.enabled = enabled
    }

    /**
     * Report a crash (unhandled exception, fatal error)
     */
    fun crash(message: String, stackTrace: String? = null, context: Map&lt;String, Any&gt; = emptyMap()) {
        queueEvent("crash", message, stackTrace, context)
        flush() // Flush immediately for crashes
    }

    /**
     * Report an error (handled exception, API failure)
     */
    fun error(message: String, context: Map&lt;String, Any&gt; = emptyMap()) {
        queueEvent("error", message, null, context)
    }

    /**
     * Report a warning (deprecation, slow operation)
     */
    fun warning(message: String, context: Map&lt;String, Any&gt; = emptyMap()) {
        queueEvent("warning", message, null, context)
    }

    /**
     * Report an info event (successful operation, milestone)
     */
    fun info(message: String, context: Map&lt;String, Any&gt; = emptyMap()) {
        queueEvent("info", message, null, context)
    }

    /**
     * Send a heartbeat to indicate the app is running
     */
    fun heartbeat(context: Map&lt;String, Any&gt; = emptyMap()) {
        queueEvent("heartbeat", "Application heartbeat", null, context)
    }

    private fun queueEvent(
        level: String,
        message: String,
        stackTrace: String?,
        metadata: Map&lt;String, Any&gt;
    ) {
        if (!enabled || apiKey == null) return

        val event = JSONObject().apply {
            put("id", UUID.randomUUID().toString())
            put("timestamp", getUTCTimestamp())
            put("level", level)
            put("message", message)
            if (metadata.isNotEmpty()) {
                put("metadata", JSONObject(metadata))
            }
            if (stackTrace != null) {
                put("stackTrace", JSONArray(stackTrace.split("\n").filter { it.isNotBlank() }))
            }
        }

        synchronized(eventQueue) {
            eventQueue.add(event)
            if (eventQueue.size >= 10) {
                scope.launch { flush() }
            }
        }
    }

    fun flush() {
        if (!enabled || eventQueue.isEmpty() || apiKey == null) return

        val events: List&lt;JSONObject&gt;
        synchronized(eventQueue) {
            events = eventQueue.toList()
            eventQueue.clear()
        }

        scope.launch { sendBatch(events) }
    }

    private suspend fun sendBatch(events: List&lt;JSONObject&gt;) {
        val currentApiKey = apiKey ?: return

        val batch = JSONObject().apply {
            put("batchId", UUID.randomUUID().toString())
            put("deviceInfo", JSONObject().apply {
                put("deviceId", deviceId)
                put("deviceModel", "${Build.MANUFACTURER} ${Build.MODEL}")
                put("osVersion", "Android ${Build.VERSION.RELEASE}")
                put("appVersion", getAppVersion())
                put("buildNumber", getAppVersionCode())
                put("platform", "Android")
            })
            put("appIdentifier", appIdentifier)
            put("environment", if (isTest) "development" else "production")
            put("userId", JSONObject.NULL)
            put("events", JSONArray(events))
            put("sentAt", getUTCTimestamp())
            put("isTest", isTest)
        }

        try {
            withContext(Dispatchers.IO) {
                val url = URL("$baseUrl/api/v1/health/events")
                val connection = url.openConnection() as HttpURLConnection
                connection.apply {
                    requestMethod = "POST"
                    setRequestProperty("Content-Type", "application/json")
                    setRequestProperty("Accept", "application/json")
                    setRequestProperty("X-API-Key", currentApiKey)
                    setRequestProperty("X-App-Identifier", appIdentifier)
                    connectTimeout = 10000
                    readTimeout = 10000
                    doOutput = true
                }
                connection.outputStream.use { os ->
                    os.write(batch.toString().toByteArray(Charsets.UTF_8))
                }

                val responseCode = connection.responseCode

                // If unauthorized, try refreshing API key
                if (responseCode == 401 && appSecret != null) {
                    connection.disconnect()
                    if (fetchApiKey()) {
                        // Retry with new key
                        sendBatch(events)
                        return@withContext
                    }
                }

                if (responseCode !in 200..299) {
                    synchronized(eventQueue) { eventQueue.addAll(0, events) }
                }
                connection.disconnect()
            }
        } catch (e: Exception) {
            synchronized(eventQueue) { eventQueue.addAll(0, events) }
        }
    }

    private fun getUTCTimestamp(): String {
        return SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US).apply {
            timeZone = TimeZone.getTimeZone("UTC")
        }.format(Date())
    }

    private fun getOrCreateDeviceId(): String {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        var id = prefs.getString(KEY_DEVICE_ID, null)
        if (id == null) {
            id = UUID.randomUUID().toString()
            prefs.edit().putString(KEY_DEVICE_ID, id).apply()
        }
        return id
    }

    private fun getAppVersion(): String {
        return try {
            context.packageManager.getPackageInfo(context.packageName, 0).versionName ?: "1.0.0"
        } catch (e: Exception) { "1.0.0" }
    }

    private fun getAppVersionCode(): String {
        return try {
            val pInfo = context.packageManager.getPackageInfo(context.packageName, 0)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                pInfo.longVersionCode.toString()
            } else {
                @Suppress("DEPRECATION")
                pInfo.versionCode.toString()
            }
        } catch (e: Exception) { "1" }
    }
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-key mr-2"></i>Option 1: Initialize with App Secret (Recommended)
    </h3>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
        <p class="text-green-800">The App Secret allows the SDK to fetch API keys dynamically. If you rotate your API key in the Vitalytics dashboard, the app will automatically get the new key without requiring an update.</p>
    </div>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>class MyApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        // Initialize with App Secret (recommended)
        Vitalytics.initializeWithSecret(
            context = this,
            baseUrl = "https://your-vitalytics-server.com",
            appSecret = "your-app-secret-here",  // From Vitalytics dashboard
            appIdentifier = "yourapp-android",
            isTest = BuildConfig.DEBUG,
            onReady = { success ->
                if (success) {
                    Log.d("Vitalytics", "SDK ready")
                    setupCrashHandler()
                } else {
                    Log.e("Vitalytics", "Failed to initialize - check app secret")
                }
            }
        )
    }

    private fun setupCrashHandler() {
        val defaultHandler = Thread.getDefaultUncaughtExceptionHandler()

        Thread.setDefaultUncaughtExceptionHandler { thread, throwable ->
            Vitalytics.crash(
                message = throwable.message ?: "Uncaught Exception",
                stackTrace = throwable.stackTraceToString(),
                context = mapOf(
                    "thread" to thread.name,
                    "exceptionType" to throwable.javaClass.simpleName
                )
            )
            Thread.sleep(1000)
            defaultHandler?.uncaughtException(thread, throwable)
        }
    }
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-key mr-2"></i>Option 2: Initialize with API Key Directly
    </h3>
    <p class="text-gray-600 mb-3">If you don't need API key rotation, you can use the API key directly:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>Vitalytics.initialize(
    context = this,
    baseUrl = "https://your-vitalytics-server.com",
    apiKey = "your-api-key-here",  // Direct API key
    appIdentifier = "yourapp-android",
    isTest = BuildConfig.DEBUG
)

// Can set up crash handler immediately
setupCrashHandler()</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-cog mr-2"></i>Where to Find Your Credentials
    </h3>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Credential</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Location in Dashboard</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Purpose</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-mono">appSecret</td>
                    <td class="px-4 py-2">Settings → Secrets → Your App</td>
                    <td class="px-4 py-2">Embedded in app, used to fetch API key</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono">apiKey</td>
                    <td class="px-4 py-2">Settings → API Keys → Your App</td>
                    <td class="px-4 py-2">Used to send events (fetched via secret or hardcoded)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono">appIdentifier</td>
                    <td class="px-4 py-2">Settings → Apps → Identifier column</td>
                    <td class="px-4 py-2">Unique identifier for your app (e.g., "myapp-android")</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Usage Examples</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Report an error (e.g., API failure)
Vitalytics.error(
    message = "Failed to load user data",
    context = mapOf(
        "userId" to "123",
        "endpoint" to "/api/users",
        "statusCode" to 500
    )
)

// Report a warning (e.g., slow operation)
Vitalytics.warning(
    message = "Slow API response",
    context = mapOf(
        "endpoint" to "/api/patients",
        "duration_ms" to 3500
    )
)

// Report info (e.g., successful operation)
Vitalytics.info(
    message = "User completed checkout",
    context = mapOf(
        "orderId" to "ORD-12345",
        "total" to 99.99
    )
)

// Report a crash with stack trace
try {
    // risky operation
} catch (e: Exception) {
    Vitalytics.crash(
        message = e.message ?: "Unknown error",
        stackTrace = e.stackTraceToString(),
        context = mapOf("screen" to "PaymentActivity")
    )
    throw e
}

// Send periodic heartbeat
Vitalytics.heartbeat(mapOf("activeUsers" to 5))</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Gradle Dependencies</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>dependencies {
    implementation "org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3"
}</code></pre>
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
                    <td class="px-4 py-2"><code>crash(message, stackTrace, context)</code></td>
                    <td class="px-4 py-2">Unhandled exceptions, fatal errors</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-orange-600">error</td>
                    <td class="px-4 py-2"><code>error(message, context)</code></td>
                    <td class="px-4 py-2">API failures, validation errors</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-yellow-600">warning</td>
                    <td class="px-4 py-2"><code>warning(message, context)</code></td>
                    <td class="px-4 py-2">Deprecations, slow operations</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-blue-600">info</td>
                    <td class="px-4 py-2"><code>info(message, context)</code></td>
                    <td class="px-4 py-2">Successful operations, milestones</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-green-600">heartbeat</td>
                    <td class="px-4 py-2"><code>heartbeat(context)</code></td>
                    <td class="px-4 py-2">Periodic app health check</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">ProGuard / R8 Rules</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>-keep class com.yourapp.Vitalytics { *; }</code></pre>
    </div>
</div>
