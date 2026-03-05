<x-app-layout>

<div x-data="{ autoRefresh: true }" x-init="
    setInterval(() => {
        if (autoRefresh) {
            window.location.reload();
        }
    }, 60000)
">
    {{-- Mode Toggle - Centered above hero --}}
    <div class="flex justify-center mb-4">
        <div class="bg-gray-200 rounded-lg p-1 flex shadow-sm">
            @if(Auth::user()->canAccessHealth())
            <a href="{{ route('dashboard', ['mode' => 'health', 'hours' => $hours, 'product' => $product, 'show_test' => $showTest]) }}"
               class="px-5 py-2 rounded-md text-sm font-medium transition {{ $mode === 'health' ? 'bg-white text-indigo-600 shadow' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <i class="fas fa-heartbeat mr-1"></i> Health
            </a>
            @endif
            @if(Auth::user()->canAccessAnalytics())
            <a href="{{ route('dashboard', ['mode' => 'analytics', 'hours' => $hours, 'product' => $product, 'show_test' => $showTest]) }}"
               class="px-5 py-2 rounded-md text-sm font-medium transition {{ $mode === 'analytics' ? 'bg-white text-cyan-600 shadow' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <i class="fas fa-chart-line mr-1"></i> Analytics
            </a>
            @endif
            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.system.index') }}"
               class="px-5 py-2 rounded-md text-sm font-medium transition text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                <i class="fas fa-server mr-1"></i> Vitalytics
            </a>
            @endif
        </div>
    </div>

    {{-- Hero Header with Gradient --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-500 p-6 sm:p-8 mb-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-4xl font-bold text-white flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                    Analytics
                </h1>
                <p class="text-white/80 mt-2 text-sm sm:text-base">User behavior and engagement tracking</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="bg-white/20 backdrop-blur rounded-lg px-4 py-2 text-white text-sm hidden sm:block">
                    <i class="fas fa-clock mr-2"></i>{{ $lastUpdated }}
                </div>
                <label class="bg-white/20 backdrop-blur rounded-lg px-4 py-2 text-white text-sm flex items-center gap-2 cursor-pointer hover:bg-white/30 transition">
                    <input type="checkbox" x-model="autoRefresh" class="rounded border-white/50 bg-white/20 text-cyan-500 focus:ring-white/50">
                    <span class="hidden sm:inline">Auto-refresh</span>
                </label>
                <button onclick="window.location.reload()" class="bg-white text-cyan-600 hover:bg-white/90 px-4 py-2 rounded-lg text-sm font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-4">
                <input type="hidden" name="mode" value="analytics">

                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-gray-400"></i>
                    <select name="product" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">All Products</option>
                        @foreach($products as $productId => $productInfo)
                            <option value="{{ $productId }}" {{ ($product ?? null) == $productId ? 'selected' : '' }}>{{ $productInfo['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <i class="fas fa-clock text-gray-400"></i>
                    <select name="hours" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="1" {{ $hours == 1 ? 'selected' : '' }}>Last 1 Hour</option>
                        <option value="6" {{ $hours == 6 ? 'selected' : '' }}>Last 6 Hours</option>
                        <option value="24" {{ $hours == 24 ? 'selected' : '' }}>Last 24 Hours</option>
                        <option value="48" {{ $hours == 48 ? 'selected' : '' }}>Last 48 Hours</option>
                        <option value="168" {{ $hours == 168 ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="720" {{ $hours == 720 ? 'selected' : '' }}>Last 30 Days</option>
                    </select>
                </div>

                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-orange-200 bg-orange-50 cursor-pointer hover:bg-orange-100 transition">
                    <input type="checkbox" name="show_test" value="1" onchange="this.form.submit()" {{ ($showTest ?? false) ? 'checked' : '' }} class="rounded border-orange-300 text-orange-500 focus:ring-orange-500">
                    <span class="text-orange-700 text-sm font-medium"><i class="fas fa-flask mr-1"></i> Test Data</span>
                </label>

                @if($product ?? null)
                    <a href="{{ route('dashboard', ['mode' => 'analytics', 'hours' => $hours]) }}" class="text-sm text-gray-500 hover:text-cyan-600 flex items-center gap-1">
                        <i class="fas fa-times"></i> Clear filter
                    </a>
                @endif
            </form>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.product-feedback.index', ['product' => $product, 'show_test' => $showTest]) }}"
                   class="inline-flex items-center gap-2 px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium shadow-sm hover:shadow-md transition-all relative">
                    <i class="fas fa-comments"></i>
                    <span>Feedback</span>
                    @if(($feedbackUnread ?? 0) > 0)
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                        {{ $feedbackUnread > 99 ? '99+' : $feedbackUnread }}
                    </span>
                    @endif
                </a>
                <a href="{{ route('admin.analytics.metrics', ['product' => $product, 'show_test' => $showTest]) }}"
                   class="inline-flex items-center gap-2 px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium shadow-sm hover:shadow-md transition-all">
                    <i class="fas fa-chart-pie"></i>
                    <span>Metrics</span>
                </a>
                <a href="{{ route('admin.analytics.geo-map', ['hours' => $hours, 'product' => $product, 'show_test' => $showTest]) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-medium shadow-sm hover:shadow-md transition-all">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Session Map</span>
                    <i class="fas fa-external-link-alt text-xs opacity-75"></i>
                </a>
                <a href="{{ route('admin.analytics.summaries', ['product' => $product]) }}"
                   class="inline-flex items-center gap-2 px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-medium shadow-sm hover:shadow-md transition-all">
                    <i class="fas fa-robot"></i>
                    <span>Usage Summaries</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Overview Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        {{-- Total Events --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-chart-bar text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['totalEvents']) }}</div>
                <div class="text-blue-100 text-sm mt-1">Total Events</div>
            </div>
        </div>

        {{-- Sessions --}}
        <a href="{{ route('admin.analytics.sessions', ['hours' => $hours, 'product' => $product, 'show_test' => $showTest]) }}"
           class="group relative overflow-hidden bg-gradient-to-br from-cyan-500 to-teal-500 rounded-xl p-4 shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 cursor-pointer">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-users text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['uniqueSessions']) }}</div>
                <div class="text-cyan-100 text-sm mt-1">Sessions <i class="fas fa-arrow-right ml-1 text-xs opacity-0 group-hover:opacity-100 transition"></i></div>
            </div>
        </a>

        {{-- Identified Devices --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-mobile-alt text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['identifiedDevices']) }}</div>
                <div class="text-purple-100 text-sm mt-1">Identified Devices</div>
            </div>
        </div>

        {{-- Anonymous Sessions --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-user-secret text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['anonymousSessions']) }}</div>
                <div class="text-slate-100 text-sm mt-1">Anonymous Sessions</div>
            </div>
        </div>

        {{-- Active Sessions --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-signal text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['activeSessions']) }}</div>
                <div class="text-green-100 text-sm mt-1">Active Now</div>
            </div>
        </div>

        {{-- Avg Duration --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-amber-500 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-clock text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">
                    @if($overallStats['avgSessionDuration'] >= 60)
                        {{ floor($overallStats['avgSessionDuration'] / 60) }}m
                    @else
                        {{ $overallStats['avgSessionDuration'] }}s
                    @endif
                </div>
                <div class="text-orange-100 text-sm mt-1">Avg Duration</div>
            </div>
        </div>

        {{-- Screens/Session --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-pink-500 to-rose-500 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-layer-group text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ $overallStats['screensPerSession'] }}</div>
                <div class="text-pink-100 text-sm mt-1">Screens/Session</div>
            </div>
        </div>
    </div>

    {{-- Collection Mode Breakdown --}}
    @if(($overallStats['identifiedDevices'] ?? 0) > 0 || ($overallStats['anonymousSessions'] ?? 0) > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-100 to-violet-100 flex items-center justify-center">
                <i class="fas fa-shield-alt text-purple-600"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Analytics Collection Modes</h3>
                <p class="text-xs text-gray-500">Breakdown of Standard Analytics vs Privacy Mode sessions</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            {{-- Progress Bar --}}
            <div class="flex-1">
                <div class="h-8 rounded-full overflow-hidden bg-gray-100 flex">
                    @if(($overallStats['standardPercentage'] ?? 0) > 0)
                    <div class="h-full bg-gradient-to-r from-purple-500 to-violet-500 flex items-center justify-center text-white text-xs font-bold transition-all duration-500"
                         style="width: {{ $overallStats['standardPercentage'] }}%;"
                         title="Standard Analytics: {{ $overallStats['identifiedDevices'] }} devices">
                        @if($overallStats['standardPercentage'] >= 15)
                        {{ $overallStats['standardPercentage'] }}%
                        @endif
                    </div>
                    @endif
                    @if(($overallStats['privacyPercentage'] ?? 0) > 0)
                    <div class="h-full bg-gradient-to-r from-slate-400 to-slate-500 flex items-center justify-center text-white text-xs font-bold transition-all duration-500"
                         style="width: {{ $overallStats['privacyPercentage'] }}%;"
                         title="Privacy Mode: {{ $overallStats['anonymousSessions'] }} sessions">
                        @if($overallStats['privacyPercentage'] >= 15)
                        {{ $overallStats['privacyPercentage'] }}%
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center justify-center gap-8 mt-4">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-gradient-to-r from-purple-500 to-violet-500"></div>
                <span class="text-sm text-gray-600">Standard Analytics</span>
                <span class="text-sm font-semibold text-purple-600">{{ $overallStats['standardPercentage'] ?? 0 }}%</span>
                <span class="text-xs text-gray-400">({{ number_format($overallStats['identifiedDevices'] ?? 0) }} devices)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-gradient-to-r from-slate-400 to-slate-500"></div>
                <span class="text-sm text-gray-600">Privacy Mode</span>
                <span class="text-sm font-semibold text-slate-600">{{ $overallStats['privacyPercentage'] ?? 0 }}%</span>
                <span class="text-xs text-gray-400">({{ number_format($overallStats['anonymousSessions'] ?? 0) }} sessions)</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Product Status Cards --}}
    <div class="space-y-4 mb-8">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-cubes text-cyan-500"></i> Products
        </h2>

        @forelse($productStats as $productId => $pStats)
        <div x-data="{ expanded: localStorage.getItem('analytics_expanded_{{ $productId }}') === 'true' }" x-init="$watch('expanded', val => localStorage.setItem('analytics_expanded_{{ $productId }}', val))" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
            {{-- Product Header --}}
            <div class="p-5">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    {{-- Product Info --}}
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-sm" style="background: linear-gradient(135deg, {{ $pStats['color'] }}20, {{ $pStats['color'] }}40);">
                            @if($pStats['has_custom_icon'] && $pStats['custom_icon'])
                                <img src="{{ $pStats['custom_icon'] }}" alt="{{ $pStats['name'] }}" class="w-10 h-10 object-contain">
                            @else
                                <i class="fas {{ $pStats['icon'] }} text-2xl" style="color: {{ $pStats['color'] }};"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">{{ $pStats['name'] }}</h3>
                            <p class="text-sm text-gray-500 flex items-center gap-1">
                                <i class="fas fa-layer-group text-xs"></i>
                                {{ count($pStats['subProducts']) }} platforms
                            </p>
                        </div>
                    </div>

                    {{-- Engagement Summary --}}
                    <div class="flex items-center gap-4">
                        @if($pStats['totalEvents'] > 0)
                        <div class="text-center px-4">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($pStats['totalEvents']) }}</div>
                            <div class="text-xs text-gray-500">Events</div>
                        </div>
                        <div class="text-center px-4 border-l border-gray-200">
                            <div class="text-2xl font-bold text-cyan-600">{{ number_format($pStats['uniqueSessions']) }}</div>
                            <div class="text-xs text-gray-500">Sessions</div>
                        </div>
                        <div class="text-center px-4 border-l border-gray-200">
                            <div class="text-2xl font-bold text-purple-600">{{ number_format($pStats['uniqueUsers']) }}</div>
                            <div class="text-xs text-gray-500">Users</div>
                        </div>
                        @else
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gray-200 text-gray-600">
                            <i class="fas fa-question-circle mr-2"></i> No Data
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Stats Grid --}}
                <div class="mt-5 grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div class="text-center p-3 rounded-lg bg-blue-50">
                        <div class="text-2xl font-bold text-blue-600">{{ number_format($pStats['totalEvents']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Events</div>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-cyan-50">
                        <div class="text-2xl font-bold text-cyan-600">{{ number_format($pStats['uniqueSessions']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Sessions</div>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-purple-50">
                        <div class="text-2xl font-bold text-purple-600">{{ number_format($pStats['uniqueUsers']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Users</div>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-orange-50">
                        <div class="text-2xl font-bold text-orange-600">
                            @if($pStats['avgSessionDuration'] >= 60)
                                {{ floor($pStats['avgSessionDuration'] / 60) }}m
                            @else
                                {{ $pStats['avgSessionDuration'] }}s
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Avg Duration</div>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-pink-50">
                        <div class="text-2xl font-bold text-pink-600">{{ $pStats['screensPerSession'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">Screens/Session</div>
                    </div>
                </div>

                {{-- Expand Button --}}
                <button @click="expanded = !expanded"
                        class="mt-4 w-full flex items-center justify-center gap-2 text-sm text-gray-500 hover:text-cyan-600 py-2 border-t border-gray-100 transition">
                    <span x-text="expanded ? 'Hide Platforms' : 'Show Platforms'"></span>
                    <i class="fas fa-chevron-down transition-transform duration-200" :class="expanded ? 'rotate-180' : ''"></i>
                </button>
            </div>

            {{-- Sub-Products (Collapsible) --}}
            <div x-show="expanded"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="bg-gradient-to-b from-gray-50 to-gray-100 p-4 border-t border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($pStats['subProducts'] as $appId => $stats)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                        <i class="fab {{ $stats['info']['icon'] ?? 'fa-cube' }} text-lg text-gray-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 text-sm">{{ $stats['info']['name'] }}</h4>
                                        <p class="text-xs text-gray-400 font-mono">{{ $appId }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <a href="{{ route('admin.analytics.tracking-events', ['app' => $appId, 'hours' => $hours, 'show_test' => $showTest]) }}"
                                   class="text-center p-2 rounded bg-blue-50 flex-1 hover:bg-blue-100 transition cursor-pointer">
                                    <div class="text-sm font-bold text-blue-600">{{ $stats['totalEvents'] }}</div>
                                    <div class="text-xs text-gray-400">Events</div>
                                </a>
                                <a href="{{ route('admin.analytics.sessions', ['product' => $productId, 'hours' => $hours, 'show_test' => $showTest]) }}"
                                   class="text-center p-2 rounded bg-cyan-50 flex-1 hover:bg-cyan-100 transition cursor-pointer"
                                   title="View all sessions for {{ $stats['info']['name'] }}">
                                    <div class="text-sm font-bold text-cyan-600">{{ $stats['uniqueSessions'] }}</div>
                                    <div class="text-xs text-gray-400">Sessions</div>
                                </a>
                                <div class="text-center p-2 rounded bg-purple-50 flex-1">
                                    <div class="text-sm font-bold text-purple-600">{{ $stats['uniqueUsers'] }}</div>
                                    <div class="text-xs text-gray-400">Users</div>
                                </div>
                                <div class="text-center p-2 rounded bg-orange-50 flex-1">
                                    <div class="text-sm font-bold text-orange-600">
                                        @if($stats['avgSessionDuration'] >= 60)
                                            {{ floor($stats['avgSessionDuration'] / 60) }}m
                                        @else
                                            {{ $stats['avgSessionDuration'] }}s
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400">Dur</div>
                                </div>
                            </div>
                            @if(!empty($stats['topEvents']))
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <div class="text-xs text-gray-500 mb-2">Top Events:</div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($stats['topEvents'] as $eventName => $count)
                                    <a href="{{ route('admin.analytics.tracking-events', ['app' => $appId, 'event_name' => $eventName, 'hours' => $hours, 'show_test' => $showTest]) }}"
                                       class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                                        {{ $eventName }} <span class="ml-1 text-gray-400">({{ $count }})</span>
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-chart-line text-3xl text-gray-400"></i>
            </div>
            <p class="text-gray-500 font-medium">No Analytics Data</p>
            <p class="text-gray-400 text-sm mt-1">Analytics events will appear here once tracking is enabled</p>
        </div>
        @endforelse
    </div>

    {{-- Two Column Layout: Top Events & Recent Sessions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Top Events --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-fire text-blue-600 text-sm"></i>
                    </div>
                    Top Events
                </h2>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @forelse($topEvents as $index => $event)
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 font-medium">{{ $event['name'] }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700">
                                        {{ $event['category'] }}
                                    </span>
                                    @if($event['screen'])
                                    <span class="text-xs text-gray-500">{{ $event['screen'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-gradient-to-r from-blue-100 to-cyan-100 text-blue-700">
                                {{ number_format($event['count']) }}x
                            </span>
                            <div class="text-xs text-gray-400 mt-1">{{ $event['uniqueSessions'] }} sessions</div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-bar text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No Events Yet</p>
                    <p class="text-gray-400 text-sm mt-1">Events will appear here once tracking starts</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Sessions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                        <i class="fas fa-users text-cyan-600 text-sm"></i>
                    </div>
                    Recent Sessions
                </h2>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.analytics.geo-map', ['hours' => $hours, 'product' => $product, 'show_test' => $showTest]) }}"
                       target="_blank"
                       class="text-sm text-emerald-600 hover:text-emerald-800 font-medium flex items-center gap-1">
                        <i class="fas fa-map-marked-alt"></i> Map <i class="fas fa-external-link-alt text-xs ml-1"></i>
                    </a>
                    <a href="{{ route('admin.analytics.sessions', ['hours' => $hours, 'product' => $product, 'show_test' => $showTest]) }}"
                       class="text-sm text-cyan-600 hover:text-cyan-800 font-medium flex items-center gap-1">
                        View All <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @forelse($recentSessions as $session)
                <a href="{{ route('admin.analytics.session', ['sessionId' => $session['session_id'], 'show_test' => $showTest]) }}"
                   class="block p-4 hover:bg-gray-50 transition">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-8 h-8 rounded-lg {{ $session['is_active'] ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                                @if($session['is_active'])
                                <i class="fas fa-circle text-green-500 text-xs animate-pulse"></i>
                                @else
                                <i class="fas fa-user text-gray-500 text-sm"></i>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm text-gray-900 font-medium truncate">{{ $session['app_identifier'] }}</p>
                                @if($session['is_active'])
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span class="text-xs text-gray-500">{{ $session['platform'] }}</span>
                                <span class="text-xs text-gray-400">{{ $session['duration_formatted'] }}</span>
                                <span class="text-xs text-gray-400">{{ $session['event_count'] }} events</span>
                                <span class="text-xs text-gray-400">{{ $session['screens_viewed'] }} screens</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1" title="{{ $session['started_at'] }}">{{ $session['started_at_time'] ?? $session['started_at'] }}</div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-chevron-right text-gray-300"></i>
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No Sessions Yet</p>
                    <p class="text-gray-400 text-sm mt-1">Sessions will appear here once users are tracked</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- AI Summary Reports --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-white flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-robot text-purple-600 text-sm"></i>
                </div>
                AI Summary Reports
            </h2>
            <a href="{{ route('admin.analytics.summaries', ['product' => $product]) }}"
               class="text-sm text-purple-600 hover:text-purple-800 font-medium flex items-center gap-1">
                View All <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
        <div class="p-4">
            <p class="text-sm text-gray-600 mb-3">Daily AI-generated summaries of health events and user analytics, powered by Claude.</p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('admin.analytics.summaries', ['type' => 'health', 'product' => $product]) }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg bg-indigo-50 hover:bg-indigo-100 transition group">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 group-hover:bg-indigo-200 flex items-center justify-center transition">
                        <i class="fas fa-heartbeat text-indigo-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Health Reports</p>
                        <p class="text-xs text-gray-500">Error analysis & recommendations</p>
                    </div>
                </a>
                <a href="{{ route('admin.analytics.summaries', ['type' => 'analytics', 'product' => $product]) }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg bg-cyan-50 hover:bg-cyan-100 transition group">
                    <div class="w-10 h-10 rounded-lg bg-cyan-100 group-hover:bg-cyan-200 flex items-center justify-center transition">
                        <i class="fas fa-chart-line text-cyan-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Analytics Reports</p>
                        <p class="text-xs text-gray-500">User behavior insights</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Events by Category --}}
    @if(!empty($eventsByCategory))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-tags text-purple-600 text-sm"></i>
                </div>
                Events by Category
            </h2>
        </div>
        <div class="p-4">
            <div class="flex flex-wrap gap-3">
                @php
                    $totalCategoryEvents = collect($eventsByCategory)->sum('count');
                    $categoryColors = [
                        'navigation' => 'bg-blue-100 text-blue-700',
                        'interaction' => 'bg-green-100 text-green-700',
                        'form' => 'bg-yellow-100 text-yellow-700',
                        'feature' => 'bg-purple-100 text-purple-700',
                        'content' => 'bg-pink-100 text-pink-700',
                        'session' => 'bg-cyan-100 text-cyan-700',
                        'search' => 'bg-orange-100 text-orange-700',
                        'conversion' => 'bg-emerald-100 text-emerald-700',
                    ];
                @endphp
                @foreach($eventsByCategory as $cat)
                @php
                    $colorClass = $categoryColors[$cat['category']] ?? 'bg-gray-100 text-gray-700';
                    $percentage = $totalCategoryEvents > 0 ? round(($cat['count'] / $totalCategoryEvents) * 100, 1) : 0;
                @endphp
                <div class="flex items-center gap-2 px-4 py-2 rounded-lg {{ $colorClass }}">
                    <span class="font-medium">{{ $cat['category'] }}</span>
                    <span class="text-sm opacity-75">{{ number_format($cat['count']) }}</span>
                    <span class="text-xs opacity-50">({{ $percentage }}%)</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Hourly Trend Chart --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-chart-area text-blue-600 text-sm"></i>
                </div>
                Event Trends
                <span class="text-gray-400 font-normal text-sm ml-2">(Last {{ $hours }} Hours)</span>
            </h2>
        </div>
        <div class="p-4">
            <div class="overflow-x-auto">
                <div class="flex items-end gap-1" style="min-width: 600px; height: 160px;">
                    @php
                        $maxValue = max(1, collect($hourlyTrends)->max('events'));
                    @endphp
                    @foreach($hourlyTrends as $trend)
                    <div class="flex-1 flex flex-col items-center group">
                        <div class="w-full flex flex-col-reverse rounded-t-sm overflow-hidden" style="height: 120px;">
                            @if($trend['events'] > 0)
                            <div class="w-full bg-gradient-to-t from-cyan-600 to-blue-400 transition-all group-hover:from-cyan-700 group-hover:to-blue-500"
                                 style="height: {{ ($trend['events'] / $maxValue) * 100 }}%;"
                                 title="Events: {{ $trend['events'] }}, Sessions: {{ $trend['sessions'] }}"></div>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 mt-2 transform -rotate-45 origin-top-left whitespace-nowrap" style="font-size: 10px;">
                            {{ $trend['hour'] }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center justify-center gap-6 mt-6 pt-4 border-t border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded bg-gradient-to-t from-cyan-600 to-blue-400"></div>
                    <span class="text-sm text-gray-600">Events</span>
                </div>
            </div>
        </div>
    </div>
</div>

</x-app-layout>
