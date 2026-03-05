<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-swift mr-2 text-orange-500"></i>Swift Health Monitoring
    </h2>
    <p class="text-gray-600 mb-6">Track crashes, errors, and health events in your iOS/macOS application.</p>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Installation</h3>
    <p class="text-gray-600 mb-4">Add the Vitalytics class to your project and configure it in your AppDelegate or App struct.</p>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Configuration</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// In your App struct or AppDelegate
import Foundation

@main
struct MyApp: App {
    init() {
        Vitalytics.shared.configure(
            baseUrl: "https://vitalytics.yourserver.com",
            apiKey: "your-api-key",
            appIdentifier: "myapp-ios"
        )
    }
}</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Sending Health Events</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Report an error
Vitalytics.shared.error(
    message: "Failed to load user data",
    context: ["userId": "123", "endpoint": "/api/users"]
)

// Report a warning
Vitalytics.shared.warning(
    message: "Slow API response",
    context: ["duration": "3.5s"]
)

// Report general info
Vitalytics.shared.info(
    message: "User logged in successfully"
)

// Report a crash (typically in exception handler)
Vitalytics.shared.crash(
    message: error.localizedDescription,
    stackTrace: Thread.callStackSymbols.joined(separator: "\n"),
    context: ["screen": "PaymentView"]
)</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Automatic Crash Reporting</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Set up global exception handler
NSSetUncaughtExceptionHandler { exception in
    Vitalytics.shared.crash(
        message: exception.name.rawValue,
        stackTrace: exception.callStackSymbols.joined(separator: "\n"),
        context: ["reason": exception.reason ?? "Unknown"]
    )
}

// For signal handling (SIGSEGV, SIGBUS, etc.)
signal(SIGSEGV) { _ in
    Vitalytics.shared.crash(message: "SIGSEGV - Segmentation Fault")
}
signal(SIGBUS) { _ in
    Vitalytics.shared.crash(message: "SIGBUS - Bus Error")
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
                    <td class="px-4 py-2"><code>crash(message:stackTrace:context:)</code></td>
                    <td class="px-4 py-2">Unhandled exceptions, fatal errors</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-orange-600">error</td>
                    <td class="px-4 py-2"><code>error(message:context:)</code></td>
                    <td class="px-4 py-2">API failures, validation errors</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-yellow-600">warning</td>
                    <td class="px-4 py-2"><code>warning(message:context:)</code></td>
                    <td class="px-4 py-2">Deprecations, slow operations</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-blue-600">info</td>
                    <td class="px-4 py-2"><code>info(message:context:)</code></td>
                    <td class="px-4 py-2">Successful operations, milestones</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
