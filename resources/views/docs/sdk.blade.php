<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vitalytics SDK Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        pre code { white-space: pre; }
        .prose pre { margin: 0; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                <i class="fas fa-heartbeat text-red-500 mr-2"></i>
                Vitalytics SDK
            </h1>
            <p class="text-lg text-gray-600">Complete integration guide for Health Monitoring & Analytics</p>
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-rocket mr-2 text-cyan-500"></i>Quick Start
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
                    <h3 class="font-semibold text-red-800 mb-2">
                        <i class="fas fa-heartbeat mr-1"></i> Health Monitoring
                    </h3>
                    <p class="text-sm text-red-700 mb-3">Track crashes, errors, warnings, and application health events in real-time.</p>
                    <a href="#health" class="text-sm font-medium text-red-600 hover:text-red-800">
                        View Health SDK <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 rounded-lg p-4 border border-cyan-200">
                    <h3 class="font-semibold text-cyan-800 mb-2">
                        <i class="fas fa-chart-line mr-1"></i> Analytics
                    </h3>
                    <p class="text-sm text-cyan-700 mb-3">Track user journeys, screen views, feature usage, and custom events.</p>
                    <a href="#analytics" class="text-sm font-medium text-cyan-600 hover:text-cyan-800">
                        View Analytics SDK <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Shared Configuration -->
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-8">
            <h2 class="text-lg font-semibold text-green-800 mb-3">
                <i class="fas fa-link mr-2"></i>Shared Configuration
            </h2>
            <p class="text-green-700 mb-4">
                Health Monitoring and Analytics use the <strong>same credentials</strong>. You only need one set of configuration values for both features:
            </p>
            <div class="bg-white rounded-lg p-4 border border-green-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="font-medium text-gray-700">Base URL</div>
                        <div class="text-gray-500">Your Vitalytics server URL</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-700">API Key</div>
                        <div class="text-gray-500">From the Secrets page</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-700">App Identifier</div>
                        <div class="text-gray-500">e.g., myapp-ios, myapp-android</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Platform Selection -->
        <div x-data="{ platform: '{{ $tab }}', section: 'health' }" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Platform Tabs -->
            <div class="border-b border-gray-200 bg-gray-50">
                <nav class="flex overflow-x-auto">
                    <button @click="platform = 'swift'"
                            :class="platform === 'swift' ? 'border-blue-500 text-blue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                        <i class="fab fa-swift mr-2 text-orange-500"></i>Swift (iOS)
                    </button>
                    <button @click="platform = 'kotlin'"
                            :class="platform === 'kotlin' ? 'border-blue-500 text-blue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                        <i class="fab fa-android mr-2 text-green-500"></i>Kotlin (Android)
                    </button>
                    <button @click="platform = 'javascript'"
                            :class="platform === 'javascript' ? 'border-blue-500 text-blue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                        <i class="fab fa-js mr-2 text-yellow-500"></i>JavaScript
                    </button>
                    <button @click="platform = 'laravel'"
                            :class="platform === 'laravel' ? 'border-blue-500 text-blue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                        <i class="fab fa-laravel mr-2 text-red-500"></i>Laravel
                    </button>
                    <button @click="platform = 'dotnet'"
                            :class="platform === 'dotnet' ? 'border-blue-500 text-blue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-4 px-6 border-b-2 font-medium text-sm whitespace-nowrap">
                        <i class="fab fa-microsoft mr-2 text-blue-600"></i>.NET (Windows)
                    </button>
                </nav>
            </div>

            <!-- Section Toggle -->
            <div class="border-b border-gray-200 p-4 bg-gray-50">
                <div class="flex justify-center gap-2">
                    <button @click="section = 'health'"
                            :class="section === 'health' ? 'bg-red-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                            class="px-4 py-2 rounded-lg font-medium text-sm transition border border-gray-200">
                        <i class="fas fa-heartbeat mr-1"></i> Health Monitoring
                    </button>
                    <button @click="section = 'analytics'"
                            :class="section === 'analytics' ? 'bg-cyan-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                            class="px-4 py-2 rounded-lg font-medium text-sm transition border border-gray-200">
                        <i class="fas fa-chart-line mr-1"></i> Analytics
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-6">
                {{-- Swift --}}
                <div x-show="platform === 'swift'" x-cloak>
                    <div x-show="section === 'health'">
                        @include('docs.partials.health-swift')
                    </div>
                    <div x-show="section === 'analytics'">
                        @include('docs.partials.analytics-swift')
                    </div>
                </div>

                {{-- Kotlin --}}
                <div x-show="platform === 'kotlin'" x-cloak>
                    <div x-show="section === 'health'">
                        @include('docs.partials.health-kotlin')
                    </div>
                    <div x-show="section === 'analytics'">
                        @include('docs.partials.analytics-kotlin')
                    </div>
                </div>

                {{-- JavaScript --}}
                <div x-show="platform === 'javascript'" x-cloak>
                    <div x-show="section === 'health'">
                        @include('docs.partials.health-javascript')
                    </div>
                    <div x-show="section === 'analytics'">
                        @include('docs.partials.analytics-javascript')
                    </div>
                </div>

                {{-- Laravel --}}
                <div x-show="platform === 'laravel'" x-cloak>
                    <div x-show="section === 'health'">
                        @include('docs.partials.health-laravel')
                    </div>
                    <div x-show="section === 'analytics'">
                        @include('docs.partials.analytics-laravel')
                    </div>
                </div>

                {{-- .NET --}}
                <div x-show="platform === 'dotnet'" x-cloak>
                    <div x-show="section === 'health'">
                        @include('docs.partials.health-dotnet')
                    </div>
                    <div x-show="section === 'analytics'">
                        @include('docs.partials.analytics-dotnet')
                    </div>
                </div>
            </div>
        </div>

        <!-- Download Full Docs -->
        <div class="mt-8 text-center">
            <p class="text-gray-500 mb-4">Need the raw documentation files?</p>
            <div class="flex justify-center gap-4">
                <a href="{{ route('docs.analytics-sdk') }}"
                   class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">
                    <i class="fas fa-download mr-2"></i>Analytics SDK (Markdown)
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-12 text-center text-gray-400 text-sm">
            <p>Vitalytics SDK Documentation</p>
        </div>
    </div>
</body>
</html>
