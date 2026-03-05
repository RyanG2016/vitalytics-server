<x-app-layout>

<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('dashboard', ['mode' => 'analytics', 'hours' => $hours, 'show_test' => $showTest]) }}" class="text-gray-400 hover:text-cyan-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
        </div>
        <p class="text-gray-500 text-sm">Last {{ $hours }} hours</p>
    </div>

    {{-- Filters Bar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('admin.analytics.tracking-events') }}" class="flex flex-wrap items-center gap-4">
            {{-- Product Filter --}}
            <div class="flex items-center gap-2">
                <i class="fas fa-cube text-gray-400"></i>
                <select name="product" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">All Products</option>
                    @foreach($products as $productId => $productInfo)
                        <option value="{{ $productId }}" {{ ($product ?? null) == $productId ? 'selected' : '' }}>{{ $productInfo['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- App Filter --}}
            <div class="flex items-center gap-2">
                <i class="fas fa-mobile-alt text-gray-400"></i>
                <select name="app" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">All Apps</option>
                    @foreach($apps as $appId => $appInfo)
                        <option value="{{ $appId }}" {{ ($app ?? null) == $appId ? 'selected' : '' }}>{{ $appInfo['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Category Filter --}}
            <div class="flex items-center gap-2">
                <i class="fas fa-tag text-gray-400"></i>
                <select name="category" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ ($category ?? null) == $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Time Filter --}}
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

            {{-- Test Data Toggle --}}
            <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-orange-200 bg-orange-50 cursor-pointer hover:bg-orange-100 transition">
                <input type="checkbox" name="show_test" value="1" onchange="this.form.submit()" {{ ($showTest ?? false) ? 'checked' : '' }} class="rounded border-orange-300 text-orange-500 focus:ring-orange-500">
                <span class="text-orange-700 text-sm font-medium"><i class="fas fa-flask mr-1"></i> Test Data</span>
            </label>

            {{-- Clear Filters --}}
            @if(($product ?? null) || ($app ?? null) || ($category ?? null))
                <a href="{{ route('admin.analytics.tracking-events', ['hours' => $hours, 'show_test' => $showTest]) }}" class="text-sm text-gray-500 hover:text-cyan-600 flex items-center gap-1">
                    <i class="fas fa-times"></i> Clear filters
                </a>
            @endif
        </form>
    </div>

    {{-- Events List --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                    <i class="fas fa-chart-bar text-cyan-600 text-sm"></i>
                </div>
                Analytics Events
                <span class="text-gray-400 font-normal text-sm">({{ number_format($events->total()) }} total)</span>
            </h2>
        </div>

        @if($events->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-bar text-3xl text-gray-400"></i>
                </div>
                <p class="text-gray-500 font-medium">No Events Found</p>
                <p class="text-gray-400 text-sm mt-1">Try adjusting your filters or time range</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Screen</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">App</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($events as $event)
                        @php
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
                            $colorClass = $categoryColors[$event->event_category] ?? 'bg-gray-100 text-gray-700';
                            $timestamp = \Carbon\Carbon::parse($event->event_timestamp, 'UTC')->setTimezone(config('app.timezone'));
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 text-sm">{{ $event->event_name }}</div>
                                @if($event->element_id)
                                    <div class="text-xs text-gray-400">{{ $event->element_id }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                    {{ $event->event_category }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-600">{{ $event->screen_name ?: '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-600 font-mono">{{ $event->app_identifier }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.analytics.session', ['sessionId' => $event->session_id, 'show_test' => $showTest]) }}"
                                   class="text-sm text-cyan-600 hover:text-cyan-800 font-mono">
                                    {{ Str::limit($event->session_id, 8) }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-600" title="{{ $timestamp->format('M d, Y g:i:s A') }}">
                                    {{ $timestamp->diffForHumans() }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $timestamp->format('g:i:s A') }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($events->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $events->appends(request()->query())->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

</x-app-layout>
