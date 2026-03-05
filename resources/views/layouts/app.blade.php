<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                {{ $slot }}
            </main>
        </div>

        <!-- Feedback Button & Modal -->
        @auth
        <div x-data="{
            open: false,
            loading: false,
            success: false,
            feedbackId: null,
            error: null,
            type: 'feedback',
            message: '',
            async submit() {
                this.loading = true;
                this.error = null;
                try {
                    const response = await fetch('{{ route('feedback.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({
                            type: this.type,
                            message: this.message
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.success = true;
                        this.feedbackId = data.feedback_id;
                        this.message = '';
                        this.type = 'feedback';
                    } else {
                        this.error = data.message || 'Failed to submit feedback';
                    }
                } catch (e) {
                    this.error = 'Failed to submit feedback. Please try again.';
                }
                this.loading = false;
            },
            reset() {
                this.success = false;
                this.feedbackId = null;
                this.error = null;
            }
        }">
            <!-- Floating Button -->
            <button @click="open = true; reset()"
                    class="fixed bottom-6 right-6 w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 z-50"
                    title="Send Feedback">
                <i class="fas fa-comment-dots text-xl"></i>
            </button>

            <!-- Modal -->
            <div x-show="open" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="open = false"></div>

                <!-- Modal Content -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.away="open = false">

                        <!-- Header -->
                        <div class="flex items-center justify-between p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-comment-dots text-blue-600 mr-2"></i>
                                Send Feedback
                            </h3>
                            <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Success State -->
                        <div x-show="success" class="p-6 text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-check text-3xl text-green-600"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Thank You!</h4>
                            <p class="text-gray-600 mb-4">Your feedback has been submitted successfully.</p>
                            <div class="bg-gray-100 rounded-lg px-4 py-3 mb-4">
                                <p class="text-sm text-gray-500">Feedback ID</p>
                                <p class="font-mono font-semibold text-gray-900" x-text="feedbackId"></p>
                            </div>
                            <button @click="open = false" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                                Close
                            </button>
                        </div>

                        <!-- Form State -->
                        <div x-show="!success" class="p-4">
                            <!-- Error Message -->
                            <div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <span x-text="error"></span>
                            </div>

                            <!-- Type Dropdown -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select x-model="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="feedback">General Feedback</option>
                                    <option value="feature">Feature Request</option>
                                    <option value="enhancement">Enhancement</option>
                                    <option value="bug">Bug Report</option>
                                </select>
                            </div>

                            <!-- Message Textarea -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                <textarea x-model="message"
                                          rows="5"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                          placeholder="Tell us what you think..."></textarea>
                                <p class="text-xs text-gray-400 mt-1">Minimum 10 characters</p>
                            </div>

                            <!-- Submit Button -->
                            <button @click="submit()"
                                    :disabled="loading || message.length < 10"
                                    class="w-full py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg font-medium transition">
                                <span x-show="!loading">Submit Feedback</span>
                                <span x-show="loading" x-cloak>
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Submitting...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile App Device Token Registration (user info only when authenticated) -->
        <script>
            // Get user info for native app (only available when logged in)
            window.getVitalyticsUser = function() {
                return {
                    id: {{ auth()->id() }},
                    name: "{{ auth()->user()->name }}",
                    email: "{{ auth()->user()->email }}",
                    isAdmin: {{ auth()->user()->isAdmin() ? 'true' : 'false' }}
                };
            };
            window.isVitalyticsAuthenticated = true;

            // Auto-register any pending device token from before login
            (function() {
                const pending = localStorage.getItem('vitalytics_pending_device_token');
                if (pending) {
                    try {
                        const data = JSON.parse(pending);
                        console.log('[Vitalytics] Found pending device token, registering now...');
                        window.registerDeviceToken(data.token, data.platform, data.deviceName);
                        localStorage.removeItem('vitalytics_pending_device_token');
                    } catch (e) {
                        console.error('[Vitalytics] Failed to parse pending token:', e);
                        localStorage.removeItem('vitalytics_pending_device_token');
                    }
                }
            })();
        </script>
        @else
        <script>
            window.isVitalyticsAuthenticated = false;
        </script>
        @endauth

        <!-- Mobile App Bridge Functions (always available) -->
        <script>
            // Register device token for push notifications (called from native iOS/Android code)
            // Works on all pages - queues token if not logged in, registers immediately if logged in
            window.registerDeviceToken = async function(token, platform, deviceName) {
                // If not authenticated, store for later
                if (!window.isVitalyticsAuthenticated) {
                    console.log('[Vitalytics] User not authenticated, queuing device token for after login');
                    localStorage.setItem('vitalytics_pending_device_token', JSON.stringify({
                        token: token,
                        platform: platform,
                        deviceName: deviceName
                    }));

                    // Notify native app that token is queued
                    const result = { success: true, queued: true, message: 'Token queued for registration after login' };
                    if (window.webkit?.messageHandlers?.deviceTokenCallback) {
                        window.webkit.messageHandlers.deviceTokenCallback.postMessage(result);
                    }
                    return result;
                }

                // User is authenticated, register immediately
                try {
                    const response = await fetch('/mobile/device-tokens', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        credentials: 'include',
                        body: JSON.stringify({
                            device_token: token,
                            platform: platform,
                            device_name: deviceName || null
                        })
                    });
                    const data = await response.json();
                    console.log('[Vitalytics] Device token registered:', data);

                    // Notify native app of success/failure
                    if (window.webkit?.messageHandlers?.deviceTokenCallback) {
                        window.webkit.messageHandlers.deviceTokenCallback.postMessage(data);
                    }

                    return data;
                } catch (error) {
                    console.error('[Vitalytics] Failed to register device token:', error);
                    const result = { success: false, error: error.message };
                    if (window.webkit?.messageHandlers?.deviceTokenCallback) {
                        window.webkit.messageHandlers.deviceTokenCallback.postMessage(result);
                    }
                    return result;
                }
            };

            // Remove device token (called when user logs out or disables notifications)
            window.removeDeviceToken = async function(token) {
                // Also clear any pending token
                localStorage.removeItem('vitalytics_pending_device_token');

                if (!window.isVitalyticsAuthenticated) {
                    return { success: true, message: 'Pending token cleared' };
                }

                try {
                    const response = await fetch('/mobile/device-tokens/' + encodeURIComponent(token), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        credentials: 'include'
                    });
                    const data = await response.json();
                    console.log('[Vitalytics] Device token removed:', data);
                    return data;
                } catch (error) {
                    console.error('[Vitalytics] Failed to remove device token:', error);
                    return { success: false, error: error.message };
                }
            };

            // Check if user is authenticated (for native app)
            window.isVitalyticsLoggedIn = function() {
                return window.isVitalyticsAuthenticated === true;
            };
        </script>
    </body>
</html>
