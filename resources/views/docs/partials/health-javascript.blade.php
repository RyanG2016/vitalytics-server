<div class="prose max-w-none">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fab fa-js mr-2 text-yellow-500"></i>JavaScript Health Monitoring
    </h2>
    <p class="text-gray-600 mb-6">Track crashes, errors, and health events in your web application or Chrome extension.</p>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Configuration</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>const vitalytics = new Vitalytics({
    baseUrl: 'https://vitalytics.yourserver.com',
    apiKey: 'your-api-key',
    appIdentifier: 'myapp-chrome'  // or 'myapp-web'
});</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Sending Health Events</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Report an error
vitalytics.error('Failed to load user data', {
    userId: '123',
    endpoint: '/api/users'
});

// Report a warning
vitalytics.warning('Slow API response', {
    duration: '3.5s'
});

// Report general info
vitalytics.info('User logged in successfully');

// Report a crash (typically in error handler)
vitalytics.crash('Uncaught TypeError', {
    stack: error.stack,
    screen: 'Dashboard'
});</code></pre>
    </div>

    <h3 class="text-xl font-semibold text-gray-900 mb-3">Automatic Error Capture</h3>
    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto">
        <pre class="text-gray-100 text-sm"><code>// Global error handler
window.onerror = function(message, source, lineno, colno, error) {
    vitalytics.crash(message, {
        source: source,
        line: lineno,
        column: colno,
        stack: error?.stack
    });
};

// Promise rejection handler
window.onunhandledrejection = function(event) {
    vitalytics.error('Unhandled Promise Rejection', {
        reason: event.reason?.message || String(event.reason)
    });
};

// Chrome Extension specific
chrome.runtime.onError?.addListener((error) => {
    vitalytics.error('Extension Error', {
        message: error.message
    });
});</code></pre>
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
                    <td class="px-4 py-2"><code>crash(message, context)</code></td>
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
            </tbody>
        </table>
    </div>
</div>
