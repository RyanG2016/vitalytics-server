<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-android mr-2 text-green-500"></i>Kotlin/Android Analytics
    </h2>
    <p class="text-gray-600 mb-6">Track user journeys, screen views, and feature usage in your Android application.</p>

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
    <p class="text-gray-600 mb-3">Copy this class into your project (e.g., <code class="bg-gray-200 px-1 rounded">app/src/main/java/com/yourapp/VitalyticsAnalytics.kt</code>):</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 600px;">
        <pre class="text-gray-100 text-sm"><code>import android.content.Context
import android.content.res.Resources
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
    private var apiKey: String? = null
    private var appSecret: String? = null
    private var appIdentifier: String = ""
    private var deviceId: String = ""
    private var isTest: Boolean = false
    private var enabled: Boolean = false

    private var sessionId: String = UUID.randomUUID().toString()
    private var anonymousUserId: String = ""
    private var currentScreen: String? = null
    private var screenStartTime: Long? = null

    private val eventQueue = mutableListOf&lt;JSONObject&gt;()
    private val scope = CoroutineScope(Dispatchers.IO + SupervisorJob())
    private var flushJob: Job? = null

    private const val PREFS_NAME = "vitalytics"
    private const val KEY_DEVICE_ID = "device_id"
    private const val KEY_ANONYMOUS_USER_ID = "anonymous_user_id"
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
        this.anonymousUserId = getOrCreateAnonymousUserId()
        this.isTest = isTest

        // Try to use cached API key first
        val cachedKey = getCachedApiKey()
        if (cachedKey != null) {
            this.apiKey = cachedKey
            onReady?.invoke(true)
            scope.launch { refreshApiKeyIfNeeded() }
        } else {
            scope.launch {
                val success = fetchApiKey()
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
        this.anonymousUserId = getOrCreateAnonymousUserId()
        this.isTest = isTest
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

        if (expiry != null) {
            try {
                val expiryDate = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
                    .parse(expiry.replace("Z", "").substringBefore("+"))
                if (expiryDate != null && expiryDate.before(Date())) {
                    prefs.edit().remove(KEY_CACHED_API_KEY).remove(KEY_API_KEY_EXPIRY).apply()
                    return null
                }
            } catch (e: Exception) { /* ignore */ }
        }

        return prefs.getString(KEY_CACHED_API_KEY, null)
    }

    private suspend fun refreshApiKeyIfNeeded() {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        val expiry = prefs.getString(KEY_API_KEY_EXPIRY, null) ?: return

        try {
            val expiryDate = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
                .parse(expiry.replace("Z", "").substringBefore("+")) ?: return

            val sixHoursFromNow = Date(System.currentTimeMillis() + 6 * 60 * 60 * 1000)
            if (expiryDate.before(sixHoursFromNow)) {
                fetchApiKey()
            }
        } catch (e: Exception) { /* ignore */ }
    }

    /**
     * Enable/disable analytics (call after user consent)
     */
    fun setEnabled(enabled: Boolean) {
        this.enabled = enabled
        if (enabled) {
            startSession()
        } else {
            stopSession()
            synchronized(eventQueue) { eventQueue.clear() }
        }
    }

    fun trackScreen(screenName: String, properties: Map&lt;String, Any&gt; = emptyMap()) {
        if (!enabled || apiKey == null) return

        currentScreen?.let { prevScreen ->
            screenStartTime?.let { startTime ->
                val durationMs = System.currentTimeMillis() - startTime
                queueEvent(
                    eventType = "screen_duration",
                    category = "navigation",
                    screen = prevScreen,
                    duration = durationMs.toInt(),
                    properties = mapOf("duration_ms" to durationMs)
                )
            }
        }

        currentScreen = screenName
        screenStartTime = System.currentTimeMillis()

        queueEvent(
            eventType = "screen_viewed",
            category = "navigation",
            screen = screenName,
            properties = properties
        )
    }

    fun trackClick(elementId: String, properties: Map&lt;String, Any&gt; = emptyMap()) {
        queueEvent(
            eventType = "button_clicked",
            category = "interaction",
            screen = currentScreen,
            element = elementId,
            properties = properties
        )
    }

    fun trackFeature(featureName: String, properties: Map&lt;String, Any&gt; = emptyMap()) {
        queueEvent(
            eventType = "feature_used",
            category = "feature",
            screen = currentScreen,
            properties = properties + mapOf("feature" to featureName)
        )
    }

    fun trackSearch(query: String, resultCount: Int, properties: Map&lt;String, Any&gt; = emptyMap()) {
        queueEvent(
            eventType = "search_performed",
            category = "search",
            screen = currentScreen,
            properties = properties + mapOf("query" to query, "result_count" to resultCount)
        )
    }

    fun trackEvent(
        eventType: String,
        category: String = "custom",
        properties: Map&lt;String, Any&gt; = emptyMap()
    ) {
        queueEvent(
            eventType = eventType,
            category = category,
            screen = currentScreen,
            properties = properties
        )
    }

    private fun queueEvent(
        eventType: String,
        category: String,
        screen: String? = null,
        element: String? = null,
        duration: Int? = null,
        properties: Map&lt;String, Any&gt; = emptyMap()
    ) {
        if (!enabled || apiKey == null) return

        val event = JSONObject().apply {
            put("id", UUID.randomUUID().toString())
            put("timestamp", getUTCTimestamp())
            put("eventType", eventType)
            put("sessionId", sessionId)
            put("category", category)
            screen?.let { put("screen", it) }
            element?.let { put("element", it) }
            duration?.let { put("duration", it) }
            if (properties.isNotEmpty()) {
                put("properties", JSONObject(properties))
            }
        }

        synchronized(eventQueue) {
            eventQueue.add(event)
            if (eventQueue.size >= 20) {
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
            put("appIdentifier", appIdentifier)
            put("deviceInfo", JSONObject().apply {
                put("deviceId", deviceId)
                put("deviceModel", "${Build.MANUFACTURER} ${Build.MODEL}")
                put("platform", "Android")
                put("osVersion", Build.VERSION.RELEASE)
                put("appVersion", getAppVersion())
                put("screenResolution", getScreenResolution())
                put("language", Locale.getDefault().language)
            })
            put("anonymousUserId", anonymousUserId)
            put("isTest", isTest)
            put("environment", if (isTest) "development" else "production")
            put("sentAt", getUTCTimestamp())
            put("events", JSONArray(events))
        }

        try {
            withContext(Dispatchers.IO) {
                val url = URL("$baseUrl/api/v1/analytics/events")
                val connection = url.openConnection() as HttpURLConnection
                connection.apply {
                    requestMethod = "POST"
                    setRequestProperty("Content-Type", "application/json")
                    setRequestProperty("Accept", "application/json")
                    setRequestProperty("X-API-Key", currentApiKey)
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

    private fun startSession() {
        sessionId = UUID.randomUUID().toString()
        queueEvent("session_started", "session")
        flushJob = scope.launch {
            while (isActive) {
                delay(30_000)
                flush()
            }
        }
    }

    private fun stopSession() {
        queueEvent("session_ended", "session")
        flush()
        flushJob?.cancel()
        flushJob = null
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

    private fun getOrCreateAnonymousUserId(): String {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        var id = prefs.getString(KEY_ANONYMOUS_USER_ID, null)
        if (id == null) {
            id = UUID.randomUUID().toString()
            prefs.edit().putString(KEY_ANONYMOUS_USER_ID, id).apply()
        }
        return id
    }

    private fun getAppVersion(): String {
        return try {
            context.packageManager.getPackageInfo(context.packageName, 0).versionName ?: "1.0.0"
        } catch (e: Exception) { "1.0.0" }
    }

    private fun getScreenResolution(): String {
        return try {
            val dm = Resources.getSystem().displayMetrics
            "${dm.widthPixels}x${dm.heightPixels}"
        } catch (e: Exception) { "unknown" }
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

        // Initialize Analytics with App Secret (recommended)
        VitalyticsAnalytics.initializeWithSecret(
            context = this,
            baseUrl = "https://your-vitalytics-server.com",
            appSecret = "your-app-secret-here",  // From Vitalytics dashboard
            appIdentifier = "yourapp-android",
            isTest = BuildConfig.DEBUG,
            onReady = { success ->
                if (success) {
                    Log.d("VitalyticsAnalytics", "SDK ready")
                    // Analytics is ready but NOT enabled yet
                    // Enable after user consent (GDPR)
                } else {
                    Log.e("VitalyticsAnalytics", "Failed to initialize - check app secret")
                }
            }
        )
    }
}

// After user consent (e.g., in settings or consent dialog)
VitalyticsAnalytics.setEnabled(true)</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-key mr-2"></i>Option 2: Initialize with API Key Directly
    </h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>VitalyticsAnalytics.initialize(
    context = this,
    baseUrl = "https://your-vitalytics-server.com",
    apiKey = "your-api-key-here",  // Direct API key
    appIdentifier = "yourapp-android",
    isTest = BuildConfig.DEBUG
)

// Enable after user consent
VitalyticsAnalytics.setEnabled(true)</code></pre>
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
                    <td class="px-4 py-2">Settings &rarr; Secrets &rarr; Your App</td>
                    <td class="px-4 py-2">Embedded in app, used to fetch API key</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono">apiKey</td>
                    <td class="px-4 py-2">Settings &rarr; API Keys &rarr; Your App</td>
                    <td class="px-4 py-2">Used to send events (fetched via secret or hardcoded)</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono">appIdentifier</td>
                    <td class="px-4 py-2">Settings &rarr; Apps &rarr; Identifier column</td>
                    <td class="px-4 py-2">Unique identifier for your app (e.g., "myapp-android")</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Usage Examples</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Track screen views (in Activity)
override fun onResume() {
    super.onResume()
    VitalyticsAnalytics.trackScreen("MainActivity")
}

// Track with properties
VitalyticsAnalytics.trackScreen("PatientDetails", mapOf(
    "patientId" to "12345",
    "source" to "search"
))

// Track button clicks
VitalyticsAnalytics.trackClick("submit_button", mapOf(
    "form" to "login"
))

// Track feature usage
VitalyticsAnalytics.trackFeature("dark_mode", mapOf(
    "enabled" to true
))

// Track search
VitalyticsAnalytics.trackSearch(
    query = "John Smith",
    resultCount = 15
)

// Track custom events
VitalyticsAnalytics.trackEvent(
    eventType = "purchase_completed",
    category = "commerce",
    properties = mapOf(
        "orderId" to "ORD-123",
        "total" to 99.99,
        "items" to 3
    )
)

// Manually flush (e.g., before app goes to background)
VitalyticsAnalytics.flush()</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-tag mr-2 text-purple-500"></i>Friendly Labels (Dashboard Display Names)
    </h3>
    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-4">
        <p class="text-purple-800"><strong>New Feature:</strong> Add human-readable labels to your events. Labels appear in the Vitalytics dashboard instead of technical IDs, making analytics easier to understand.</p>
    </div>

    <p class="text-gray-600 mb-4">There are two types of labels:</p>
    <div class="overflow-x-auto mb-4">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Label Type</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Property Key</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Use For</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Example</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 font-semibold">Element Label</td>
                    <td class="px-4 py-2 font-mono text-sm">label</td>
                    <td class="px-4 py-2">Buttons, features, forms</td>
                    <td class="px-4 py-2">"Save Changes" instead of "save_btn"</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-semibold">Screen Label</td>
                    <td class="px-4 py-2 font-mono text-sm">screen_label</td>
                    <td class="px-4 py-2">Screens, modals, dialogs</td>
                    <td class="px-4 py-2">"Patient Details" instead of "PatientDetailActivity"</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Screen Labels</h4>
    <p class="text-gray-600 mb-2">Add a friendly name for screens that appears in the dashboard:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Track screen with a friendly label
VitalyticsAnalytics.trackScreen("PatientDetailActivity", mapOf(
    "screen_label" to "Patient Details",  // Shows as "Patient Details" in dashboard
    "patientId" to "12345"
))

// Modals/Dialogs - use screen_label to identify them clearly
VitalyticsAnalytics.trackScreen("ConfirmDeleteDialog", mapOf(
    "screen_label" to "Delete Confirmation"
))

// Complex screen names become readable
VitalyticsAnalytics.trackScreen("com.app.ui.encounters.EncounterListFragment", mapOf(
    "screen_label" to "Visit History"
))</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Element Labels (Clicks, Features, Forms)</h4>
    <p class="text-gray-600 mb-2">Add friendly names to buttons, features, and other interactive elements:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Button click with label
VitalyticsAnalytics.trackClick("btn_save_patient", mapOf(
    "label" to "Save Patient",           // Friendly name for dashboard
    "screen_label" to "Patient Editor"   // Also set screen context label
))

// Feature usage with label
VitalyticsAnalytics.trackFeature("export_pdf", mapOf(
    "label" to "Export to PDF",
    "screen_label" to "Report View"
))

// Form submission with label
VitalyticsAnalytics.trackEvent(
    eventType = "form_submitted",
    category = "form",
    properties = mapOf(
        "form" to "patient_registration_form",
        "label" to "Patient Registration",
        "screen_label" to "New Patient"
    )
)

// Custom event with labels
VitalyticsAnalytics.trackEvent(
    eventType = "feature_used",
    category = "feature",
    properties = mapOf(
        "feature" to "voice_dictation",
        "label" to "Voice Dictation",
        "screen_label" to "SOAP Notes Editor"
    )
)</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Complete Example with Labels</h4>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>class PatientDetailActivity : AppCompatActivity() {

    override fun onResume() {
        super.onResume()
        // Track screen view with friendly label
        VitalyticsAnalytics.trackScreen("PatientDetailActivity", mapOf(
            "screen_label" to "Patient Details",
            "patientId" to patientId
        ))
    }

    private fun setupButtons() {
        binding.editButton.setOnClickListener {
            VitalyticsAnalytics.trackClick("edit_patient_btn", mapOf(
                "label" to "Edit Patient",
                "screen_label" to "Patient Details"
            ))
            // ... edit logic
        }

        binding.deleteButton.setOnClickListener {
            VitalyticsAnalytics.trackClick("delete_patient_btn", mapOf(
                "label" to "Delete Patient",
                "screen_label" to "Patient Details"
            ))
            showDeleteConfirmation()
        }
    }

    private fun showDeleteConfirmation() {
        // Track modal/dialog screen view
        VitalyticsAnalytics.trackScreen("DeleteConfirmDialog", mapOf(
            "screen_label" to "Delete Confirmation"
        ))
        // ... show dialog
    }
}</code></pre>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-800"><strong>Dashboard Display:</strong> When labels are set, the Vitalytics dashboard shows the friendly label with the technical ID available on hover. This makes it easy to understand user flows while maintaining traceability.</p>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Jetpack Compose Integration</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>@Composable
fun PatientScreen(patientId: String) {
    LaunchedEffect(Unit) {
        VitalyticsAnalytics.trackScreen("PatientView", mapOf(
            "patientId" to patientId
        ))
    }

    Column {
        Button(onClick = {
            VitalyticsAnalytics.trackClick("edit_patient_button")
        }) {
            Text("Edit Patient")
        }
    }
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Gradle Dependencies</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>dependencies {
    implementation "org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3"
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Event Types Reference</h3>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Method</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Event Type</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Category</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2"><code>trackScreen()</code></td>
                    <td class="px-4 py-2">screen_viewed</td>
                    <td class="px-4 py-2">navigation</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>trackClick()</code></td>
                    <td class="px-4 py-2">button_clicked</td>
                    <td class="px-4 py-2">interaction</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>trackFeature()</code></td>
                    <td class="px-4 py-2">feature_used</td>
                    <td class="px-4 py-2">feature</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>trackSearch()</code></td>
                    <td class="px-4 py-2">search_performed</td>
                    <td class="px-4 py-2">search</td>
                </tr>
                <tr>
                    <td class="px-4 py-2"><code>trackEvent()</code></td>
                    <td class="px-4 py-2">(custom)</td>
                    <td class="px-4 py-2">(custom)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Privacy Considerations</h3>
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <p class="text-yellow-800"><strong>GDPR/Privacy:</strong> Only enable analytics after obtaining user consent. Use <code>setEnabled(false)</code> by default and <code>setEnabled(true)</code> after consent.</p>
    </div>
</div>
