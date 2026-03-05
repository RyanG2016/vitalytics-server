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
            <a href="{{ route('dashboard', array_merge(request()->except('mode'), ['mode' => 'health'])) }}"
               class="px-5 py-2 rounded-md text-sm font-medium transition bg-white text-indigo-600 shadow">
                <i class="fas fa-heartbeat mr-1"></i> Health
            </a>
            @endif
            @if(Auth::user()->canAccessAnalytics())
            <a href="{{ route('dashboard', array_merge(request()->except('mode'), ['mode' => 'analytics'])) }}"
               class="px-5 py-2 rounded-md text-sm font-medium transition text-gray-600 hover:text-gray-900 hover:bg-gray-100">
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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-6 sm:p-8 mb-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-4xl font-bold text-white flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                        <i class="fas fa-heartbeat text-2xl text-white"></i>
                    </div>
                    Vitalytics
                </h1>
                <p class="text-white/80 mt-2 text-sm sm:text-base">Real-time application health monitoring</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="bg-white/20 backdrop-blur rounded-lg px-4 py-2 text-white text-sm hidden sm:block">
                    <i class="fas fa-clock mr-2"></i>{{ $lastUpdated }}
                </div>
                <label class="bg-white/20 backdrop-blur rounded-lg px-4 py-2 text-white text-sm flex items-center gap-2 cursor-pointer hover:bg-white/30 transition">
                    <input type="checkbox" x-model="autoRefresh" class="rounded border-white/50 bg-white/20 text-indigo-500 focus:ring-white/50">
                    <span class="hidden sm:inline">Auto-refresh</span>
                    <span class="sm:hidden">Auto</span>
                </label>
                <button onclick="window.location.reload()" class="bg-white text-indigo-600 hover:bg-white/90 px-4 py-2 rounded-lg text-sm font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-gray-400"></i>
                <select name="product" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Products</option>
                    @foreach($products as $productId => $productInfo)
                        <option value="{{ $productId }}" {{ ($product ?? null) == $productId ? 'selected' : '' }}>{{ $productInfo['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <i class="fas fa-clock text-gray-400"></i>
                <select name="hours" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="1" {{ $hours == 1 ? 'selected' : '' }}>Last 1 Hour</option>
                    <option value="6" {{ $hours == 6 ? 'selected' : '' }}>Last 6 Hours</option>
                    <option value="24" {{ $hours == 24 ? 'selected' : '' }}>Last 24 Hours</option>
                    <option value="48" {{ $hours == 48 ? 'selected' : '' }}>Last 48 Hours</option>
                    <option value="168" {{ $hours == 168 ? 'selected' : '' }}>Last 7 Days</option>
                </select>
            </div>

            <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-orange-200 bg-orange-50 cursor-pointer hover:bg-orange-100 transition">
                <input type="checkbox" name="show_test" value="1" onchange="this.form.submit()" {{ ($showTest ?? false) ? 'checked' : '' }} class="rounded border-orange-300 text-orange-500 focus:ring-orange-500">
                <span class="text-orange-700 text-sm font-medium"><i class="fas fa-flask mr-1"></i> Test Data</span>
            </label>

            @if($product ?? null)
                <a href="{{ route('dashboard', ['hours' => $hours]) }}" class="text-sm text-gray-500 hover:text-indigo-600 flex items-center gap-1">
                    <i class="fas fa-times"></i> Clear filter
                </a>
            @endif
        </form>
    </div>

    {{-- Stats Overview Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        {{-- Total Events --}}
        <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'show_test' => $showTest, 'product' => $product ?? null]) }}"
           class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-chart-bar text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['totalEvents']) }}</div>
                <div class="text-blue-100 text-sm mt-1">Total Events</div>
            </div>
        </a>

        {{-- Crashes --}}
        <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'show_test' => $showTest, 'level' => 'crash']) }}"
           class="group relative overflow-hidden bg-gradient-to-br from-red-500 to-rose-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-bomb text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['crashes']) }}</div>
                <div class="text-red-100 text-sm mt-1">Crashes</div>
            </div>
        </a>

        {{-- Errors --}}
        <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'show_test' => $showTest, 'level' => 'error']) }}"
           class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['errors']) }}</div>
                <div class="text-orange-100 text-sm mt-1">Errors</div>
            </div>
        </a>

        {{-- Warnings --}}
        <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'show_test' => $showTest, 'level' => 'warning']) }}"
           class="group relative overflow-hidden bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-exclamation-triangle text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['warnings']) }}</div>
                <div class="text-yellow-100 text-sm mt-1">Warnings</div>
            </div>
        </a>

        {{-- Network Errors --}}
        <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'show_test' => $showTest, 'level' => 'networkError']) }}"
           class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-wifi text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['networkErrors']) }}</div>
                <div class="text-purple-100 text-sm mt-1">Network</div>
            </div>
        </a>

        {{-- Active Devices --}}
        <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'show_test' => $showTest, 'view' => 'devices']) }}"
           class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-mobile-alt text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($overallStats['totalDevices']) }}</div>
                <div class="text-emerald-100 text-sm mt-1">Devices</div>
            </div>
        </a>
    </div>

    {{-- Product Status Cards --}}
    <div class="space-y-4 mb-8">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-cubes text-indigo-500"></i> Products
        </h2>

        @foreach($productStats as $productId => $pStats)
        <div x-data="{ expanded: localStorage.getItem('product_expanded_{{ $productId }}') === 'true' }" x-init="$watch('expanded', val => localStorage.setItem('product_expanded_{{ $productId }}', val))" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
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

                    {{-- Health Score & Status --}}
                    <div class="flex items-center gap-6">
                        {{-- Health Score Ring --}}
                        <div class="relative">
                            @if($pStats['status'] === 'no_data')
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-xl font-bold text-gray-400">--</span>
                            </div>
                            @else
                            <div class="w-16 h-16 rounded-full flex items-center justify-center
                                {{ $pStats['healthScore'] >= 80 ? 'bg-gradient-to-br from-green-400 to-emerald-500' :
                                   ($pStats['healthScore'] >= 50 ? 'bg-gradient-to-br from-yellow-400 to-amber-500' :
                                   'bg-gradient-to-br from-red-400 to-rose-500') }}">
                                <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center">
                                    <span class="text-lg font-bold {{ $pStats['healthScore'] >= 80 ? 'text-green-600' : ($pStats['healthScore'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $pStats['healthScore'] }}%
                                    </span>
                                </div>
                            </div>
                            @endif
                            <div class="text-xs text-gray-500 text-center mt-1">Health</div>
                        </div>

                        {{-- Status Badge --}}
                        @if($pStats['status'] === 'healthy')
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-sm">
                            <i class="fas fa-check-circle mr-2"></i> Healthy
                        </span>
                        @elseif($pStats['status'] === 'degraded')
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-yellow-500 to-amber-500 text-white shadow-sm">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Degraded
                        </span>
                        @elseif($pStats['status'] === 'no_data')
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gray-200 text-gray-600">
                            <i class="fas fa-question-circle mr-2"></i> No Data
                        </span>
                        @else
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-red-500 to-rose-500 text-white shadow-sm">
                            <i class="fas fa-times-circle mr-2"></i> Critical
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Stats Grid --}}
                <div class="mt-5 grid grid-cols-3 md:grid-cols-6 gap-3">
                    <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'product' => $productId, 'show_test' => $showTest]) }}"
                       class="text-center p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition group">
                        <div class="text-2xl font-bold text-gray-900 group-hover:text-indigo-600 transition">{{ number_format($pStats['totalEvents']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Events</div>
                    </a>
                    <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'product' => $productId, 'level' => 'crash', 'show_test' => $showTest]) }}"
                       class="text-center p-3 rounded-lg {{ $pStats['crashes'] > 0 ? 'bg-red-50 hover:bg-red-100' : 'bg-gray-50 hover:bg-gray-100' }} transition group">
                        <div class="text-2xl font-bold {{ $pStats['crashes'] > 0 ? 'text-red-600' : 'text-gray-900' }} group-hover:scale-110 transition">{{ $pStats['crashes'] }}</div>
                        <div class="text-xs {{ $pStats['crashes'] > 0 ? 'text-red-500' : 'text-gray-500' }} mt-1">Crashes</div>
                    </a>
                    <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'product' => $productId, 'level' => 'error', 'show_test' => $showTest]) }}"
                       class="text-center p-3 rounded-lg {{ $pStats['errors'] > 0 ? 'bg-orange-50 hover:bg-orange-100' : 'bg-gray-50 hover:bg-gray-100' }} transition group">
                        <div class="text-2xl font-bold {{ $pStats['errors'] > 0 ? 'text-orange-600' : 'text-gray-900' }} group-hover:scale-110 transition">{{ $pStats['errors'] }}</div>
                        <div class="text-xs {{ $pStats['errors'] > 0 ? 'text-orange-500' : 'text-gray-500' }} mt-1">Errors</div>
                    </a>
                    <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'product' => $productId, 'level' => 'warning', 'show_test' => $showTest]) }}"
                       class="text-center p-3 rounded-lg {{ $pStats['warnings'] > 0 ? 'bg-yellow-50 hover:bg-yellow-100' : 'bg-gray-50 hover:bg-gray-100' }} transition group">
                        <div class="text-2xl font-bold {{ $pStats['warnings'] > 0 ? 'text-yellow-600' : 'text-gray-900' }} group-hover:scale-110 transition">{{ $pStats['warnings'] }}</div>
                        <div class="text-xs {{ $pStats['warnings'] > 0 ? 'text-yellow-600' : 'text-gray-500' }} mt-1">Warnings</div>
                    </a>
                    <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'product' => $productId, 'level' => 'networkError', 'show_test' => $showTest]) }}"
                       class="text-center p-3 rounded-lg {{ $pStats['networkErrors'] > 0 ? 'bg-purple-50 hover:bg-purple-100' : 'bg-gray-50 hover:bg-gray-100' }} transition group">
                        <div class="text-2xl font-bold {{ $pStats['networkErrors'] > 0 ? 'text-purple-600' : 'text-gray-900' }} group-hover:scale-110 transition">{{ $pStats['networkErrors'] }}</div>
                        <div class="text-xs {{ $pStats['networkErrors'] > 0 ? 'text-purple-500' : 'text-gray-500' }} mt-1">Network</div>
                    </a>
                    <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'product' => $productId, 'view' => 'devices', 'show_test' => $showTest]) }}"
                       class="text-center p-3 rounded-lg bg-emerald-50 hover:bg-emerald-100 transition group">
                        <div class="text-2xl font-bold text-emerald-600 group-hover:scale-110 transition">{{ $pStats['activeDevices'] }}</div>
                        <div class="text-xs text-emerald-500 mt-1">Devices</div>
                    </a>
                </div>

                {{-- Expand Button --}}
                <button @click="expanded = !expanded"
                        class="mt-4 w-full flex items-center justify-center gap-2 text-sm text-gray-500 hover:text-indigo-600 py-2 border-t border-gray-100 transition">
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
                                @if($stats['status'] === 'healthy')
                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center" title="{{ $stats['healthScore'] }}% Health">
                                    <i class="fas fa-check text-green-600 text-xs"></i>
                                </div>
                                @elseif($stats['status'] === 'degraded')
                                <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center" title="{{ $stats['healthScore'] }}% Health">
                                    <i class="fas fa-exclamation text-yellow-600 text-xs"></i>
                                </div>
                                @else
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center" title="{{ $stats['healthScore'] }}% Health">
                                    <i class="fas fa-times text-red-600 text-xs"></i>
                                </div>
                                @endif
                            </div>
                            <div class="flex gap-1">
                                <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'app' => $appId, 'show_test' => $showTest]) }}"
                                   class="text-center p-2 rounded bg-indigo-50 hover:bg-indigo-100 transition flex-1">
                                    <div class="text-sm font-bold text-indigo-600">{{ $stats['totalEvents'] }}</div>
                                    <div class="text-xs text-gray-400">Events</div>
                                </a>
                                <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'app' => $appId, 'level' => 'crash', 'show_test' => $showTest]) }}"
                                   class="text-center p-2 rounded bg-gray-50 hover:bg-gray-100 transition flex-1">
                                    <div class="text-sm font-bold {{ $stats['crashes'] > 0 ? 'text-red-600' : 'text-gray-600' }}">{{ $stats['crashes'] }}</div>
                                    <div class="text-xs text-gray-400">Crash</div>
                                </a>
                                <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'app' => $appId, 'level' => 'error', 'show_test' => $showTest]) }}"
                                   class="text-center p-2 rounded bg-gray-50 hover:bg-gray-100 transition flex-1">
                                    <div class="text-sm font-bold {{ $stats['errors'] > 0 ? 'text-orange-600' : 'text-gray-600' }}">{{ $stats['errors'] }}</div>
                                    <div class="text-xs text-gray-400">Error</div>
                                </a>
                                <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'app' => $appId, 'level' => 'warning', 'show_test' => $showTest]) }}"
                                   class="text-center p-2 rounded bg-gray-50 hover:bg-gray-100 transition flex-1">
                                    <div class="text-sm font-bold {{ $stats['warnings'] > 0 ? 'text-yellow-600' : 'text-gray-600' }}">{{ $stats['warnings'] }}</div>
                                    <div class="text-xs text-gray-400">Warn</div>
                                </a>
                                <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'app' => $appId, 'level' => 'networkError', 'show_test' => $showTest]) }}"
                                   class="text-center p-2 rounded bg-gray-50 hover:bg-gray-100 transition flex-1">
                                    <div class="text-sm font-bold {{ $stats['networkErrors'] > 0 ? 'text-purple-600' : 'text-gray-600' }}">{{ $stats['networkErrors'] }}</div>
                                    <div class="text-xs text-gray-400">Net</div>
                                </a>
                                <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'app' => $appId, 'view' => 'devices', 'show_test' => $showTest]) }}"
                                   class="text-center p-2 rounded bg-gray-50 hover:bg-gray-100 transition flex-1">
                                    <div class="text-sm font-bold text-emerald-600">{{ $stats['activeDevices'] }}</div>
                                    <div class="text-xs text-gray-400">Dev</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Two Column Layout: Recent Events & Top Errors --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Recent Events --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-stream text-indigo-600 text-sm"></i>
                    </div>
                    Recent Events
                </h2>
                <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'show_test' => $showTest, 'product' => $product ?? null]) }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                    View All <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @forelse($recentEvents as $event)
                <a href="{{ route('admin.analytics.show', ['id' => $event['id'], 'show_test' => $showTest]) }}"
                   class="block p-4 hover:bg-gray-50 transition {{ $event['dismissed_at'] ? 'opacity-60' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5 relative">
                            @if($event['level'] === 'crash')
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <i class="fas fa-bomb text-red-500 text-sm"></i>
                            </div>
                            @elseif($event['level'] === 'error')
                            <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                                <i class="fas fa-exclamation-circle text-orange-500 text-sm"></i>
                            </div>
                            @elseif($event['level'] === 'warning')
                            <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 text-sm"></i>
                            </div>
                            @else
                            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-wifi text-purple-500 text-sm"></i>
                            </div>
                            @endif
                            @if($event['dismissed_at'])
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-green-500 flex items-center justify-center">
                                <i class="fas fa-check text-white text-[8px]"></i>
                            </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 truncate font-medium" title="{{ $event['message'] }}">
                                {{ strlen($event['message']) > 60 ? substr($event['message'], 0, 60) . '...' : $event['message'] }}
                            </p>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $event['level'] === 'crash' ? 'bg-red-100 text-red-700' :
                                       ($event['level'] === 'error' ? 'bg-orange-100 text-orange-700' :
                                       ($event['level'] === 'warning' ? 'bg-yellow-100 text-yellow-700' :
                                       'bg-purple-100 text-purple-700')) }}">
                                    {{ ucfirst($event['level']) }}
                                </span>
                                @if($event['dismissed_at'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <i class="fas fa-check mr-1"></i> Cleared
                                </span>
                                @endif
                                <span class="text-xs text-gray-500">{{ $event['app'] }}</span>
                                <span class="text-xs text-gray-400" title="{{ $event['timestamp_full'] }}">{{ $event['timestamp'] }}</span>
                            </div>
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-3xl text-green-500"></i>
                    </div>
                    <p class="text-gray-500 font-medium">All Clear!</p>
                    <p class="text-gray-400 text-sm mt-1">No issues in the selected time range</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Top Errors --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-fire text-orange-500 text-sm"></i>
                    </div>
                    Top Issues
                </h2>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @forelse($topErrors as $index => $error)
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 truncate font-medium" title="{{ $error['full_message'] }}">
                                    {{ $error['message'] }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $error['level'] === 'crash' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                                        {{ ucfirst($error['level']) }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ $error['app'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700">
                                {{ $error['count'] }}x
                            </span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-3xl text-green-500"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Looking Good!</p>
                    <p class="text-gray-400 text-sm mt-1">No errors in the selected time range</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

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
                        $maxValue = max(1, collect($hourlyTrends)->max(fn($t) => $t['crashes'] + $t['errors'] + $t['warnings']));
                    @endphp
                    @foreach($hourlyTrends as $trend)
                    <div class="flex-1 flex flex-col items-center group">
                        <div class="w-full flex flex-col-reverse rounded-t-sm overflow-hidden" style="height: 120px;">
                            @if($trend['crashes'] > 0)
                            <div class="w-full bg-gradient-to-t from-red-600 to-red-400 transition-all group-hover:from-red-700 group-hover:to-red-500"
                                 style="height: {{ ($trend['crashes'] / $maxValue) * 100 }}%;"
                                 title="Crashes: {{ $trend['crashes'] }}"></div>
                            @endif
                            @if($trend['errors'] > 0)
                            <div class="w-full bg-gradient-to-t from-orange-500 to-orange-400 transition-all group-hover:from-orange-600 group-hover:to-orange-500"
                                 style="height: {{ ($trend['errors'] / $maxValue) * 100 }}%;"
                                 title="Errors: {{ $trend['errors'] }}"></div>
                            @endif
                            @if($trend['warnings'] > 0)
                            <div class="w-full bg-gradient-to-t from-yellow-500 to-yellow-400 transition-all group-hover:from-yellow-600 group-hover:to-yellow-500"
                                 style="height: {{ ($trend['warnings'] / $maxValue) * 100 }}%;"
                                 title="Warnings: {{ $trend['warnings'] }}"></div>
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
                    <div class="w-4 h-4 rounded bg-gradient-to-t from-red-600 to-red-400"></div>
                    <span class="text-sm text-gray-600">Crashes</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded bg-gradient-to-t from-orange-500 to-orange-400"></div>
                    <span class="text-sm text-gray-600">Errors</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded bg-gradient-to-t from-yellow-500 to-yellow-400"></div>
                    <span class="text-sm text-gray-600">Warnings</span>
                </div>
            </div>
        </div>
    </div>
</div>

</x-app-layout>
