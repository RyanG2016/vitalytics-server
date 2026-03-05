<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-swift mr-2 text-orange-500"></i>Swift Analytics
    </h2>
    <p class="text-gray-600 mb-6">Track user journeys, screen views, and feature usage in your iOS/macOS application.</p>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Quick Start</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Configure (uses same credentials as health monitoring)
VitalyticsAnalytics.shared.configure(
    apiKey: "your-api-key",
    appIdentifier: "myapp-ios",
    isTest: false // Set true during development
)

// Enable after user consent
VitalyticsAnalytics.shared.setEnabled(true)

// Track screens
VitalyticsAnalytics.shared.trackScreen("HomeViewController")
VitalyticsAnalytics.shared.trackScreen("SettingsViewController", properties: ["source": "menu"])

// Track interactions
VitalyticsAnalytics.shared.trackClick("save-button")
VitalyticsAnalytics.shared.trackFeature("dark-mode", properties: ["enabled": true])</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">
        <i class="fas fa-code mr-2"></i>Complete SDK Class
    </h3>
    <p class="text-gray-600 mb-3">Copy this class into your project:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto" style="max-height: 400px;">
        <pre class="text-gray-100 text-sm"><code>import Foundation

class VitalyticsAnalytics {
    static let shared = VitalyticsAnalytics()

    private var apiKey: String = ""
    private var appIdentifier: String = ""
    private var enabled: Bool = false
    private var isTest: Bool = false

    private var eventQueue: [[String: Any]] = []
    private var sessionId: String = UUID().uuidString
    private var currentScreen: String?
    private var screenStartTime: Date?
    private var flushTimer: Timer?

    func configure(apiKey: String, appIdentifier: String, isTest: Bool = false) {
        self.apiKey = apiKey
        self.appIdentifier = appIdentifier
        self.isTest = isTest
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

        if let current = currentScreen, let start = screenStartTime {
            let duration = Int(Date().timeIntervalSince(start) * 1000)
            queueEvent(eventType: "screen_duration", category: "navigation",
                      screen: current, duration: duration)
        }

        currentScreen = screenName
        screenStartTime = Date()
        queueEvent(eventType: "screen_viewed", category: "navigation",
                  screen: screenName, properties: properties)
    }

    func trackEvent(_ eventType: String, category: String, properties: [String: Any] = [:]) {
        guard enabled else { return }
        queueEvent(eventType: eventType, category: category,
                  screen: currentScreen, properties: properties)
    }

    func trackClick(_ elementId: String, properties: [String: Any] = [:]) {
        queueEvent(eventType: "button_clicked", category: "interaction",
                  screen: currentScreen, element: elementId, properties: properties)
    }

    func trackFeature(_ featureName: String, properties: [String: Any] = [:]) {
        var props = properties
        props["feature"] = featureName
        queueEvent(eventType: "feature_used", category: "feature",
                  screen: currentScreen, properties: props)
    }

    private func queueEvent(eventType: String, category: String, screen: String? = nil,
                           element: String? = nil, properties: [String: Any]? = nil,
                           duration: Int? = nil) {
        let formatter = ISO8601DateFormatter()
        formatter.timeZone = TimeZone(identifier: "UTC")

        var event: [String: Any] = [
            "id": UUID().uuidString,
            "timestamp": formatter.string(from: Date()),
            "eventType": eventType,
            "sessionId": sessionId,
            "category": category
        ]
        if let screen = screen { event["screen"] = screen }
        if let element = element { event["element"] = element }
        if let props = properties, !props.isEmpty { event["properties"] = props }
        if let dur = duration { event["duration"] = dur }

        eventQueue.append(event)
        if eventQueue.count >= 20 { flush() }
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

        URLSession.shared.dataTask(with: request).resume()
    }

    private func getDeviceInfo() -> [String: Any] {
        return [
            "deviceId": getDeviceId(),
            "deviceModel": UIDevice.current.model,
            "platform": "ios",
            "osVersion": UIDevice.current.systemVersion
        ]
    }

    private func getDeviceId() -> String {
        if let id = UserDefaults.standard.string(forKey: "vitalytics_device_id") {
            return id
        }
        let id = "dev-\(UUID().uuidString)"
        UserDefaults.standard.set(id, forKey: "vitalytics_device_id")
        return id
    }

    private func startSession() {
        sessionId = UUID().uuidString
        queueEvent(eventType: "session_started", category: "session")
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

    <h3 class="text-xl font-semibold text-gray-900 mb-3">SwiftUI Integration</h3>
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
}</code></pre>
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
                    <td class="px-4 py-2">Screens, modals, sheets</td>
                    <td class="px-4 py-2">"Patient Details" instead of "PatientDetailViewController"</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Screen Labels</h4>
    <p class="text-gray-600 mb-2">Add a friendly name for screens that appears in the dashboard:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Track screen with a friendly label
VitalyticsAnalytics.shared.trackScreen("PatientDetailViewController", properties: [
    "screen_label": "Patient Details",  // Shows as "Patient Details" in dashboard
    "patientId": "12345"
])

// Modals/Sheets - use screen_label to identify them clearly
VitalyticsAnalytics.shared.trackScreen("ConfirmDeleteSheet", properties: [
    "screen_label": "Delete Confirmation"
])

// Complex view controller names become readable
VitalyticsAnalytics.shared.trackScreen("EncounterHistoryTableViewController", properties: [
    "screen_label": "Visit History"
])</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">Element Labels (Clicks, Features, Forms)</h4>
    <p class="text-gray-600 mb-2">Add friendly names to buttons, features, and other interactive elements:</p>
    <div class="bg-gray-900 rounded-lg p-4 mb-4 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Button click with label
VitalyticsAnalytics.shared.trackClick("btn_save_patient", properties: [
    "label": "Save Patient",           // Friendly name for dashboard
    "screen_label": "Patient Editor"   // Also set screen context label
])

// Feature usage with label
VitalyticsAnalytics.shared.trackFeature("export_pdf", properties: [
    "label": "Export to PDF",
    "screen_label": "Report View"
])

// Form submission with label
VitalyticsAnalytics.shared.trackEvent("form_submitted", category: "form", properties: [
    "form": "patient_registration_form",
    "label": "Patient Registration",
    "screen_label": "New Patient"
])

// Custom event with labels
VitalyticsAnalytics.shared.trackEvent("feature_used", category: "feature", properties: [
    "feature": "voice_dictation",
    "label": "Voice Dictation",
    "screen_label": "SOAP Notes Editor"
])</code></pre>
    </div>

    <h4 class="text-lg font-semibold text-gray-900 mb-2">SwiftUI Example with Labels</h4>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>struct PatientDetailView: View {
    let patient: Patient

    var body: some View {
        VStack {
            // Patient info

            Button("Edit Patient") {
                VitalyticsAnalytics.shared.trackClick("edit_patient_btn", properties: [
                    "label": "Edit Patient",
                    "screen_label": "Patient Details"
                ])
                // ... edit logic
            }

            Button("Delete Patient") {
                VitalyticsAnalytics.shared.trackClick("delete_patient_btn", properties: [
                    "label": "Delete Patient",
                    "screen_label": "Patient Details"
                ])
                showDeleteConfirmation = true
            }
        }
        .onAppear {
            VitalyticsAnalytics.shared.trackScreen("PatientDetailView", properties: [
                "screen_label": "Patient Details",
                "patientId": patient.id
            ])
        }
        .sheet(isPresented: $showDeleteConfirmation) {
            DeleteConfirmationSheet()
                .onAppear {
                    VitalyticsAnalytics.shared.trackScreen("DeleteConfirmSheet", properties: [
                        "screen_label": "Delete Confirmation"
                    ])
                }
        }
    }
}</code></pre>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-800"><strong>Dashboard Display:</strong> When labels are set, the Vitalytics dashboard shows the friendly label with the technical ID available on hover. This makes it easy to understand user flows while maintaining traceability.</p>
    </div>
</div>
