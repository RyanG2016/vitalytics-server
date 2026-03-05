<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-tools mr-2 text-orange-600"></i> Maintenance Notifications
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Schedule maintenance banners for client applications</p>
                </div>
                <a href="{{ route('admin.maintenance.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                    <i class="fas fa-plus mr-2"></i> New Notification
                </a>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Status Filter Tabs -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        @foreach(['all' => 'All', 'active' => 'Active Now', 'upcoming' => 'Upcoming', 'expired' => 'Expired'] as $key => $label)
                            <a href="{{ route('admin.maintenance.index', ['status' => $key]) }}"
                               class="px-6 py-3 text-sm font-medium {{ $status === $key ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>

            <!-- Notifications List -->
            @if($notifications->isEmpty())
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tools text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No maintenance notifications</p>
                    <a href="{{ route('admin.maintenance.create') }}" class="inline-block mt-4 text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-1"></i> Create one
                    </a>
                </div>
            @else
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notification</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Products</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($notifications as $notification)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            @php
                                                $severityColors = [
                                                    'info' => 'bg-blue-100 text-blue-600',
                                                    'warning' => 'bg-yellow-100 text-yellow-600',
                                                    'critical' => 'bg-red-100 text-red-600',
                                                ];
                                                $severityIcons = [
                                                    'info' => 'fa-info-circle',
                                                    'warning' => 'fa-exclamation-circle',
                                                    'critical' => 'fa-exclamation-triangle',
                                                ];
                                            @endphp
                                            <span class="w-8 h-8 rounded-lg {{ $severityColors[$notification->severity] ?? 'bg-gray-100' }} flex items-center justify-center">
                                                <i class="fas {{ $severityIcons[$notification->severity] ?? 'fa-info-circle' }}"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $notification->title }}</p>
                                                <p class="text-sm text-gray-500">{{ Str::limit($notification->message, 50) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($notification->products->take(3) as $product)
                                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded">{{ $product->name }}</span>
                                            @endforeach
                                            @if($notification->products->count() > 3)
                                                <span class="px-2 py-0.5 bg-gray-200 text-gray-600 text-xs rounded">+{{ $notification->products->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div>{{ $notification->starts_at->format('M j, Y g:i A') }}</div>
                                        <div class="text-xs">to {{ $notification->ends_at->format('M j, Y g:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($notification->isCurrentlyActive())
                                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded">Active</span>
                                        @elseif($notification->isUpcoming())
                                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">Upcoming</span>
                                        @elseif($notification->isExpired())
                                            <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded">Expired</span>
                                        @endif
                                        @if(!$notification->is_active)
                                            <span class="px-2 py-1 bg-red-100 text-red-600 text-xs font-medium rounded ml-1">Disabled</span>
                                        @endif
                                        @if($notification->is_test)
                                            <span class="px-2 py-1 bg-orange-100 text-orange-600 text-xs font-medium rounded ml-1">Test</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('admin.maintenance.toggle', $notification) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 text-sm {{ $notification->is_active ? 'text-yellow-600 hover:bg-yellow-50' : 'text-green-600 hover:bg-green-50' }} rounded-md" title="{{ $notification->is_active ? 'Disable' : 'Enable' }}">
                                                    <i class="fas fa-{{ $notification->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.maintenance.edit', $notification) }}" class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-md" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.maintenance.destroy', $notification) }}" method="POST" class="inline" onsubmit="return confirm('Delete this notification?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-md" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
