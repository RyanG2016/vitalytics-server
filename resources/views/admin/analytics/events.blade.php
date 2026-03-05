<x-app-layout>

<div x-data="{ 
    showDismissModal: false, 
    selectedEvents: [],
    selectAll: false,
    toggleAll() {
        if (this.selectAll) {
            this.selectedEvents = [...document.querySelectorAll('input[name=event_ids]')].map(el => el.value);
        } else {
            this.selectedEvents = [];
        }
    },
    updateSelectAll() {
        const total = document.querySelectorAll('input[name=event_ids]').length;
        this.selectAll = this.selectedEvents.length === total && total > 0;
    }
}">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('dashboard', ['hours' => $hours, 'show_test' => $showTest ?? false]) }}" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-list mr-2"></i> {{ $title }}
            </h1>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard', ['hours' => $hours, 'show_test' => $showTest ?? false]) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-semibold">
                <i class="fas fa-chart-line mr-2"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-600"></i>
        <p class="text-green-800">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.analytics.events') }}" class="flex flex-wrap items-center gap-4">
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
            <div>
                <label class="text-sm font-medium text-gray-700">Product:</label>
                <select name="product" class="ml-2 p-2 border border-gray-300 rounded-md bg-white text-gray-900">
                    <option value="all" {{ ($product ?? '') == 'all' || !($product ?? '') ? 'selected' : '' }}>All Products</option>
                    @foreach($products as $productId => $productInfo)
                    <option value="{{ $productId }}" {{ ($product ?? '') == $productId ? 'selected' : '' }}>{{ $productInfo['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">App:</label>
                <select name="app" class="ml-2 p-2 border border-gray-300 rounded-md bg-white text-gray-900">
                    <option value="all" {{ $app == 'all' || !$app ? 'selected' : '' }}>All Apps</option>
                    @foreach($apps as $appId => $appInfo)
                    <option value="{{ $appId }}" {{ $app == $appId ? 'selected' : '' }}>{{ $appInfo['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Level:</label>
                <select name="level" class="ml-2 p-2 border border-gray-300 rounded-md bg-white text-gray-900">
                    <option value="all" {{ $level == 'all' || !$level ? 'selected' : '' }}>All Levels</option>
                    <option value="crash" {{ $level == 'crash' ? 'selected' : '' }}>Crashes</option>
                    <option value="error" {{ $level == 'error' ? 'selected' : '' }}>Errors</option>
                    <option value="warning" {{ $level == 'warning' ? 'selected' : '' }}>Warnings</option>
                    <option value="networkError" {{ $level == 'networkError' ? 'selected' : '' }}>Network Errors</option>
                    <option value="info" {{ $level == 'info' ? 'selected' : '' }}>Info</option>
                    <option value="heartbeat" {{ $level == 'heartbeat' ? 'selected' : '' }}>Heartbeats</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="show_test" value="1" {{ ($showTest ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="text-orange-600 font-medium">Test Data</span>
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="show_dismissed" value="1" {{ ($showDismissed ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-gray-600 focus:ring-gray-500">
                <span class="text-gray-600 font-medium">Dismissed</span>
            </label>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
        </form>
    </div>

    {{-- Results count and actions --}}
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Showing {{ $events->firstItem() ?? 0 }} - {{ $events->lastItem() ?? 0 }} of {{ $events->total() }} events
            <span x-show="selectedEvents.length > 0" class="ml-2 text-indigo-600 font-medium">
                (<span x-text="selectedEvents.length"></span> selected)
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button x-show="selectedEvents.length > 0" 
                    @click="showDismissModal = true" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-semibold text-sm">
                <i class="fas fa-check-circle mr-2"></i> Dismiss Selected (<span x-text="selectedEvents.length"></span>)
            </button>
        </div>
    </div>

    {{-- Events Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" 
                                   x-model="selectAll" 
                                   @change="toggleAll()"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($events as $event)
                    <tr class="hover:bg-gray-50" :class="selectedEvents.includes('{{ $event->id }}') ? 'bg-indigo-50' : ''">
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if(!$event->dismissed_at)
                            <input type="checkbox" 
                                   name="event_ids" 
                                   value="{{ $event->id }}"
                                   x-model="selectedEvents"
                                   @change="updateSelectAll()"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            @else
                            <i class="fas fa-check-circle text-green-500" title="Dismissed"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                            $levelColors = [
                                'crash' => 'bg-red-100 text-red-800',
                                'error' => 'bg-orange-100 text-orange-800',
                                'warning' => 'bg-yellow-100 text-yellow-800',
                                'networkError' => 'bg-purple-100 text-purple-800',
                                'info' => 'bg-blue-100 text-blue-800',
                                'heartbeat' => 'bg-green-100 text-green-800',
                            ];
                            $levelIcons = [
                                'crash' => 'fa-bomb',
                                'error' => 'fa-exclamation-circle',
                                'warning' => 'fa-exclamation-triangle',
                                'networkError' => 'fa-wifi',
                                'info' => 'fa-info-circle',
                                'heartbeat' => 'fa-heartbeat',
                            ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $levelColors[$event->level] ?? 'bg-gray-100 text-gray-800' }} {{ $event->dismissed_at ? 'opacity-50' : '' }}">
                                <i class="fas {{ $levelIcons[$event->level] ?? 'fa-circle' }} mr-1"></i>
                                {{ ucfirst($event->level) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 {{ $event->dismissed_at ? 'opacity-50' : '' }}">
                            {{ $apps[$event->app_identifier]['name'] ?? $event->app_identifier }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 max-w-md truncate {{ $event->dismissed_at ? 'opacity-50' : '' }}" title="{{ $event->message }}">
                            {{ \Illuminate\Support\Str::limit($event->message, 60) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 {{ $event->dismissed_at ? 'opacity-50' : '' }}">
                            <div>{{ $event->device_model ?? 'Unknown' }}</div>
                            <div class="text-xs">{{ $event->platform }} {{ $event->os_version }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 {{ $event->dismissed_at ? 'opacity-50' : '' }}">
                            {{ $event->app_version ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 {{ $event->dismissed_at ? 'opacity-50' : '' }}">
                            <div>{{ \Carbon\Carbon::parse($event->event_timestamp, 'UTC')->setTimezone(config('app.timezone'))->format('M d, Y') }}</div>
                            <div class="text-xs">{{ \Carbon\Carbon::parse($event->event_timestamp, 'UTC')->setTimezone(config('app.timezone'))->format('g:i:s A') }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.analytics.show', ['id' => $event->id, 'show_test' => $showTest ?? false]) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                            <p>No events found for the selected filters</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $events->appends(['hours' => $hours, 'app' => $app, 'level' => $level, 'show_test' => $showTest ? '1' : null, 'show_dismissed' => $showDismissed ? '1' : null, 'product' => $product ?? null])->links() }}
    </div>

    {{-- Dismiss Modal --}}
    <div x-show="showDismissModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDismissModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl sm:max-w-lg sm:w-full mx-auto z-10">
                <form method="POST" action="{{ route('admin.analytics.dismiss') }}">
                    @csrf
                    @method('DELETE')
                    <template x-for="id in selectedEvents" :key="id">
                        <input type="hidden" name="event_ids[]" :value="id">
                    </template>
                    
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-600"></i>
                            Dismiss Events
                        </h3>
                    </div>
                    
                    <div class="px-6 py-4">
                        <p class="text-sm text-gray-600 mb-4">
                            This will mark <strong x-text="selectedEvents.length"></strong> event(s) as dismissed. They will no longer affect the health score but will remain in history.
                        </p>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Resolution Note <span class="text-gray-400">(optional)</span>
                            </label>
                            <textarea name="note" rows="3" 
                                      placeholder="What was the issue and how was it resolved?"
                                      class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            <p class="mt-1 text-xs text-gray-500">This note will be saved with all dismissed events for future reference.</p>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                        <button type="button" @click="showDismissModal = false" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                            <i class="fas fa-check mr-2"></i> Dismiss Events
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</x-app-layout>
