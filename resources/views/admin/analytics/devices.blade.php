<x-app-layout>

<div>
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('dashboard', ['hours' => $hours, 'show_test' => $showTest ?? false]) }}" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-mobile-alt mr-2"></i> {{ $title }}
            </h1>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard', ['hours' => $hours, 'show_test' => $showTest ?? false]) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-semibold">
                <i class="fas fa-chart-line mr-2"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.analytics.events') }}" class="flex flex-wrap items-center gap-4">
            <input type="hidden" name="view" value="devices">
            @if($product && $product !== 'all')
                {{-- When a specific product is selected, keep it as hidden field --}}
                <input type="hidden" name="product" value="{{ $product }}">
                <div class="flex items-center gap-2 px-3 py-2 bg-indigo-50 rounded-lg border border-indigo-200">
                    <i class="fas fa-cube text-indigo-500"></i>
                    <span class="text-sm font-medium text-indigo-700">{{ $products[$product]['name'] ?? $product }}</span>
                </div>
            @endif
            <div>
                <label class="text-sm font-medium text-gray-700">Time Range:</label>
                <select name="hours" class="ml-2 p-2 border border-gray-300 rounded-md bg-white text-gray-900">
                    <option value="1" {{ $hours == 1 ? 'selected' : '' }}>Last 1 Hour</option>
                    <option value="6" {{ $hours == 6 ? 'selected' : '' }}>Last 6 Hours</option>
                    <option value="24" {{ $hours == 24 ? 'selected' : '' }}>Last 24 Hours</option>
                    <option value="48" {{ $hours == 48 ? 'selected' : '' }}>Last 48 Hours</option>
                    <option value="168" {{ $hours == 168 ? 'selected' : '' }}>Last 7 Days</option>
                </select>
            </div>
            @if(!$product || $product === 'all')
            <div>
                <label class="text-sm font-medium text-gray-700">App:</label>
                <select name="app" class="ml-2 p-2 border border-gray-300 rounded-md bg-white text-gray-900">
                    <option value="all" {{ $app == 'all' || !$app ? 'selected' : '' }}>All Apps</option>
                    @foreach($apps as $appId => $appInfo)
                    <option value="{{ $appId }}" {{ $app == $appId ? 'selected' : '' }}>{{ $appInfo['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="show_test" value="1" {{ ($showTest ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="text-orange-600 font-medium">Include Test Data</span>
            </label>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
        </form>
    </div>

    {{-- Results count --}}
    <div class="mb-4 text-sm text-gray-600">
        Showing {{ $devices->firstItem() ?? 0 }} - {{ $devices->lastItem() ?? 0 }} of {{ $devices->total() }} devices
    </div>

    {{-- Devices Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OS Version</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App Version</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Events</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($devices as $device)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $device->device_model ?? 'Unknown Device' }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ \Illuminate\Support\Str::limit($device->device_id, 20) }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                            @php
                            $platformIcons = [
                                'iOS' => 'fab fa-apple',
                                'Android' => 'fab fa-android',
                                'Chrome' => 'fab fa-chrome',
                                'Web' => 'fas fa-globe',
                            ];
                            @endphp
                            <i class="{{ $platformIcons[$device->platform] ?? 'fas fa-desktop' }} mr-1"></i>
                            {{ $device->platform }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $device->os_version ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $device->app_version ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $device->event_count }} events
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ \Carbon\Carbon::parse($device->last_seen, 'UTC')->setTimezone(config('app.timezone'))->format('M d, Y') }}</div>
                            <div class="text-xs">{{ \Carbon\Carbon::parse($device->last_seen, 'UTC')->setTimezone(config('app.timezone'))->diffForHumans() }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.analytics.events', ['hours' => $hours, 'app' => $app, 'device_id' => $device->device_id, 'show_test' => $showTest ? '1' : null, 'product' => $product ?? null]) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-list"></i> View Events
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-mobile-alt text-4xl text-gray-400 mb-3"></i>
                            <p>No devices found for the selected filters</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $devices->appends(['hours' => $hours, 'app' => $app, 'view' => 'devices', 'show_test' => $showTest ? '1' : null, 'product' => $product ?? null])->links() }}
    </div>
</div>

</x-app-layout>
