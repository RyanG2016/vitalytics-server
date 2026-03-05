<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard', ['mode' => 'analytics']) }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Screen Activity
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Events grouped by screen
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="flex items-center gap-3">
                <input type="hidden" name="hours" value="{{ $hours }}">

                <select name="product" onchange="this.form.submit()"
                    class="text-sm border-gray-300 rounded-lg focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="all">All Products</option>
                    @foreach($products as $productId => $productInfo)
                        <option value="{{ $productId }}" {{ $product === $productId ? 'selected' : '' }}>
                            {{ $productInfo['name'] }}
                        </option>
                    @endforeach
                </select>

                <select name="hours" onchange="this.form.submit()"
                    class="text-sm border-gray-300 rounded-lg focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="24" {{ $hours == 24 ? 'selected' : '' }}>Last 24 Hours</option>
                    <option value="168" {{ $hours == 168 ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="720" {{ $hours == 720 ? 'selected' : '' }}>Last 30 Days</option>
                </select>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="show_test" value="1" {{ $showTest ? 'checked' : '' }}
                        onchange="this.form.submit()"
                        class="rounded text-cyan-600 focus:ring-cyan-500">
                    Show Test
                </label>
            </form>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(count($screens) > 0)
                <div class="space-y-4">
                    @foreach($screens as $screen)
                        @php
                            $screenKey = $screen->screen_name . '_' . $screen->app_identifier;
                            $events = $screenDetails[$screenKey] ?? [];
                            $appInfo = $apps[$screen->app_identifier] ?? null;
                        @endphp
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: false }">
                            <!-- Screen Header -->
                            <div class="p-4 cursor-pointer hover:bg-gray-50 transition-colors" @click="open = !open">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                                            <i class="fas fa-mobile-screen text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800">{{ $screen->screen_name }}</h3>
                                            <p class="text-sm text-gray-500">
                                                @if($appInfo)
                                                    <i class="{{ $appInfo['icon'] ?? 'fas fa-cube' }} mr-1"></i>
                                                    {{ $appInfo['name'] }}
                                                @else
                                                    {{ $screen->app_identifier }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-6">
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-gray-800">{{ number_format($screen->event_count) }}</div>
                                            <div class="text-xs text-gray-500">events</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-cyan-600">{{ number_format($screen->unique_sessions) }}</div>
                                            <div class="text-xs text-gray-500">sessions</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-blue-600">{{ number_format($screen->unique_users) }}</div>
                                            <div class="text-xs text-gray-500">users</div>
                                        </div>
                                        <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Event Breakdown (Expandable) -->
                            <div x-show="open" x-collapse x-cloak class="border-t border-gray-100">
                                @if(count($events) > 0)
                                    <div class="p-4">
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Top Events on This Screen</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @foreach($events as $event)
                                                @php
                                                    $categoryColors = [
                                                        'navigation' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                        'interaction' => 'bg-purple-100 text-purple-800 border-purple-200',
                                                        'form' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                        'feature' => 'bg-green-100 text-green-800 border-green-200',
                                                        'error' => 'bg-red-100 text-red-800 border-red-200',
                                                        'performance' => 'bg-orange-100 text-orange-800 border-orange-200',
                                                        'lifecycle' => 'bg-gray-100 text-gray-800 border-gray-200',
                                                    ];
                                                    $categoryColor = $categoryColors[$event->event_category ?? 'lifecycle'] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                                @endphp
                                                <div class="flex items-center justify-between p-3 rounded-lg border {{ $categoryColor }}">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="font-medium truncate">{{ $event->event_name }}</span>
                                                    </div>
                                                    <span class="font-bold ml-2">{{ number_format($event->count) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="p-6 text-center text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        No event breakdown available
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <i class="fas fa-mobile-screen text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-600 mb-2">No Screen Activity</h3>
                    <p class="text-gray-500">No analytics events with screen data found for the selected time period.</p>
                </div>
            @endif
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
