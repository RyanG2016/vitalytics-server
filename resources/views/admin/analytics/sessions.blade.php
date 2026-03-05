<x-app-layout>

<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 p-6 sm:p-8 mb-6 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('dashboard', ['mode' => 'analytics', 'hours' => $hours, 'product' => $product, 'show_test' => $showTest]) }}"
                       class="text-white/70 hover:text-white transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                            <i class="fas fa-users text-xl text-white"></i>
                        </div>
                        User Sessions
                    </h1>
                </div>
                <p class="text-white/80 text-sm">View and explore all user sessions</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/20 backdrop-blur rounded-lg px-4 py-2 text-white text-sm">
                    <i class="fas fa-clock mr-2"></i>Last {{ $hours }} Hours
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('admin.analytics.sessions') }}" class="flex flex-wrap items-center gap-4">
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
                <a href="{{ route('admin.analytics.sessions', ['hours' => $hours, 'show_test' => $showTest]) }}" class="text-sm text-gray-500 hover:text-cyan-600 flex items-center gap-1">
                    <i class="fas fa-times"></i> Clear filter
                </a>
            @endif
        </form>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center">
                    <i class="fas fa-users text-cyan-600"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($totalSessions) }}</div>
                    <div class="text-xs text-gray-500">Total Sessions</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fas fa-circle text-green-500 text-xs animate-pulse"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($activeSessions) }}</div>
                    <div class="text-xs text-gray-500">Active Now</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-clock text-orange-600"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">
                        @if($avgDuration >= 60)
                            {{ floor($avgDuration / 60) }}m
                        @else
                            {{ $avgDuration }}s
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">Avg Duration</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-chart-bar text-blue-600"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $avgEvents }}</div>
                    <div class="text-xs text-gray-500">Avg Events</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-purple-600"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $avgScreens }}</div>
                    <div class="text-xs text-gray-500">Avg Screens</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sessions List --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-list text-gray-400"></i>
                Sessions
                <span class="text-gray-400 font-normal text-sm">({{ $sessions->total() }} total)</span>
            </h2>
        </div>

        @if($sessions->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Events</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Screens</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($sessions as $session)
                    <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location='{{ route('admin.analytics.session', ['sessionId' => $session['session_id'], 'show_test' => $showTest]) }}'">
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($session['is_active'])
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <i class="fas fa-circle text-xs mr-1 animate-pulse"></i> Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <i class="fas fa-circle text-xs mr-1"></i> Ended
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if(isset($apps[$session['app_identifier']]))
                                    <i class="fab {{ $apps[$session['app_identifier']]['icon'] ?? 'fa-cube' }} text-gray-400"></i>
                                @endif
                                <span class="text-sm font-medium text-gray-900">{{ $session['app_identifier'] }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ $session['platform'] ?? '-' }}</span>
                            @if($session['app_version'])
                                <span class="text-xs text-gray-400 ml-1">v{{ $session['app_version'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900" title="{{ $session['started_at_full'] }}">{{ $session['started_at_time'] }}</div>
                            <div class="text-xs text-gray-500">{{ $session['started_at_date'] }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $session['duration_formatted'] }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $session['event_count'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                {{ $session['screens_viewed'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($session['city'] || $session['country'])
                                <div class="text-sm text-gray-900">
                                    @if($session['city'])
                                        {{ $session['city'] }}@if($session['region']), {{ $session['region'] }}@endif
                                    @elseif($session['country'])
                                        {{ $session['country'] }}
                                    @endif
                                </div>
                                @if($session['city'] && $session['country'])
                                    <div class="text-xs text-gray-500">{{ $session['country'] }}</div>
                                @endif
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <a href="{{ route('admin.analytics.session', ['sessionId' => $session['session_id'], 'show_test' => $showTest]) }}"
                               class="text-cyan-600 hover:text-cyan-800 transition">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
            {{ $sessions->appends(request()->query())->links() }}
        </div>
        @else
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-3xl text-gray-400"></i>
            </div>
            <p class="text-gray-500 font-medium">No Sessions Found</p>
            <p class="text-gray-400 text-sm mt-1">Sessions will appear here once users are tracked</p>
        </div>
        @endif
    </div>
</div>

</x-app-layout>
