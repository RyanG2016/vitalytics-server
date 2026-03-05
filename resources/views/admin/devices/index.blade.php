<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-heartbeat mr-2 text-red-600"></i> Device Monitoring
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Manage devices sending heartbeats for health monitoring</p>
                </div>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-server text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                            <p class="text-sm text-gray-500">Total Devices</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['active'] }}</p>
                            <p class="text-sm text-gray-500">Monitored</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-moon text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['snoozed'] }}</p>
                            <p class="text-sm text-gray-500">Snoozed</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-archive text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['archived'] }}</p>
                            <p class="text-sm text-gray-500">Archived</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Status:</label>
                        <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Monitored</option>
                            <option value="snoozed" {{ $status === 'snoozed' ? 'selected' : '' }}>Snoozed</option>
                            <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Product:</label>
                        <select name="product" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">All Products</option>
                            @foreach($products as $p)
                                <option value="{{ $p->slug }}" {{ $product === $p->slug ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search device ID or name..."
                               class="rounded-md border-gray-300 shadow-sm text-sm w-64">
                        <button type="submit" class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-md text-sm">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    @if($search || $product || $status !== 'all')
                        <a href="{{ route('admin.devices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i> Clear filters
                        </a>
                    @endif
                </form>
            </div>

            <!-- Devices Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                @if($devices->isEmpty())
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-heartbeat text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500 font-medium">No devices found</p>
                        <p class="text-gray-400 text-sm mt-1">Devices will appear here when they start sending heartbeats</p>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">App / Platform</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Heartbeat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($devices as $device)
                                <tr class="{{ !$device->is_monitoring ? 'bg-gray-50' : '' }}">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg {{ !$device->is_monitoring ? 'bg-gray-100' : 'bg-red-100' }} flex items-center justify-center">
                                                <i class="fas fa-heartbeat {{ !$device->is_monitoring ? 'text-gray-400' : 'text-red-600' }}"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 {{ !$device->is_monitoring ? 'line-through text-gray-500' : '' }}">
                                                    {{ $device->display_name }}
                                                </p>
                                                <p class="text-xs text-gray-500 font-mono">{{ Str::limit($device->device_id, 30) }}</p>
                                                @if($device->device_model && $device->device_model !== $device->display_name)
                                                    <p class="text-xs text-gray-400">{{ $device->device_model }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-gray-900">{{ $device->app_identifier }}</p>
                                        @if($device->os_version)
                                            <p class="text-xs text-gray-500">{{ $device->os_version }}</p>
                                        @endif
                                        @if($device->app_version)
                                            <p class="text-xs text-gray-400">v{{ $device->app_version }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($device->last_heartbeat_at)
                                            <p class="text-sm text-gray-900">{{ $device->last_heartbeat_at->diffForHumans() }}</p>
                                            <p class="text-xs text-gray-500">{{ $device->last_heartbeat_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>
                                        @else
                                            <span class="text-gray-400 text-sm">Never</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($device->isSnoozed())
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-moon mr-1"></i> Snoozed
                                            </span>
                                            <p class="text-xs text-yellow-600 mt-1">
                                                Until {{ $device->snoozed_until->setTimezone(config('app.timezone'))->format('M j, g:i A') }}
                                                <span class="text-yellow-500">({{ $device->snooze_remaining }})</span>
                                            </p>
                                        @elseif($device->is_monitoring)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Monitored
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                                <i class="fas fa-archive mr-1"></i> Archived
                                            </span>
                                        @endif
                                        @if($device->last_alert_at)
                                            <p class="text-xs text-orange-600 mt-1">
                                                <i class="fas fa-bell"></i> Alert sent {{ $device->last_alert_at->diffForHumans() }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        @if($device->is_monitoring)
                                            @if($device->isSnoozed())
                                                {{-- Cancel Snooze Button --}}
                                                <form action="{{ route('admin.devices.cancel-snooze', $device) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 mr-3" title="Cancel snooze and resume monitoring">
                                                        <i class="fas fa-sun"></i> Wake
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Snooze Dropdown --}}
                                                <div x-data="{ open: false }" class="relative inline-block">
                                                    <button @click="open = !open" @click.outside="open = false"
                                                            class="text-yellow-600 hover:text-yellow-800 mr-3">
                                                        <i class="fas fa-moon"></i> Snooze
                                                    </button>
                                                    <div x-show="open" x-cloak x-transition
                                                         class="absolute right-0 mt-2 w-36 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                                        <div class="py-1">
                                                            @foreach([1 => '1 hour', 4 => '4 hours', 8 => '8 hours', 12 => '12 hours', 24 => '24 hours', 48 => '48 hours'] as $hours => $label)
                                                            <form action="{{ route('admin.devices.snooze', $device) }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="hours" value="{{ $hours }}">
                                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-yellow-50 hover:text-yellow-800">
                                                                    <i class="fas fa-clock mr-2 text-yellow-500"></i> {{ $label }}
                                                                </button>
                                                            </form>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            <form action="{{ route('admin.devices.archive', $device) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Archive this device? It will no longer trigger heartbeat alerts.')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-orange-600 hover:text-orange-800 mr-3">
                                                    <i class="fas fa-archive"></i> Archive
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.devices.activate', $device) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-green-600 hover:text-green-800 mr-3">
                                                    <i class="fas fa-undo"></i> Reactivate
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.devices.destroy', $device) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Permanently delete this device? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    @if($devices->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $devices->links() }}
                        </div>
                    @endif
                @endif
            </div>

            <!-- Help Text -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i> About Device Monitoring</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li><strong>Monitored</strong> devices will trigger alerts if they stop sending heartbeats within the configured timeout.</li>
                    <li><strong>Snoozed</strong> devices are temporarily paused. Alerts auto-resume when the snooze expires. Use this for planned maintenance or intentional shutdowns.</li>
                    <li><strong>Archived</strong> devices are excluded from heartbeat monitoring. Use this for decommissioned or test devices.</li>
                    <li>Configure heartbeat timeout and alerts in the <a href="{{ route('admin.alerts.index') }}" class="underline">Alerts settings</a>.</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
