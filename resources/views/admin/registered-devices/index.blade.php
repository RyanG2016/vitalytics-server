<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-laptop mr-2 text-green-600"></i> Registered Devices
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Devices registered via provisioning tokens</p>
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

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">App:</label>
                        <select name="app" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">All Apps</option>
                            @foreach($apps as $app)
                                <option value="{{ $app->identifier }}" {{ $appFilter === $app->identifier ? 'selected' : '' }}>
                                    {{ $app->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Status:</label>
                        <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">All Status</option>
                            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="revoked" {{ $statusFilter === 'revoked' ? 'selected' : '' }}>Revoked</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 flex-1">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search device ID, name, hostname..."
                               class="rounded-md border-gray-300 shadow-sm text-sm w-64">
                        <button type="submit" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    @if($appFilter || $statusFilter || $search)
                        <a href="{{ route('admin.registered-devices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    @endif
                </form>
            </div>

            <!-- Devices List -->
            @if($devices->isEmpty())
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-laptop text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No registered devices found</p>
                    <p class="text-gray-400 text-sm mt-1">Devices will appear here after registering with a token</p>
                    <a href="{{ route('admin.registration-tokens.create') }}" class="inline-block mt-4 text-amber-600 hover:text-amber-800">
                        <i class="fas fa-key mr-1"></i> Create Registration Token
                    </a>
                </div>
            @else
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Used</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($devices as $device)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                {{ $device->device_hostname ?? $device->device_name ?? 'Unknown' }}
                                            </p>
                                            <p class="text-xs text-gray-500 font-mono">{{ Str::limit($device->device_id, 24) }}</p>
                                            @if($device->device_os)
                                                <p class="text-xs text-gray-400 mt-1">{{ $device->device_os }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-900">{{ $device->app_identifier }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-900">
                                            {{ $device->created_at->setTimezone(config('app.timezone'))->format('M j, Y') }}
                                        </span>
                                        <p class="text-xs text-gray-500">{{ $device->created_at->diffForHumans() }}</p>
                                        @if($device->registrationToken)
                                            <p class="text-xs text-gray-400 mt-1">
                                                via <a href="{{ route('admin.registration-tokens.show', $device->registrationToken) }}"
                                                       class="text-blue-600 hover:underline">{{ $device->registrationToken->token_prefix }}...</a>
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($device->last_used_at)
                                            <span class="text-sm text-gray-900">
                                                {{ $device->last_used_at->diffForHumans() }}
                                            </span>
                                            @if($device->last_used_ip)
                                                <p class="text-xs text-gray-500">{{ $device->last_used_ip }}</p>
                                            @endif
                                        @else
                                            <span class="text-sm text-gray-400">Never</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($device->is_revoked)
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                Revoked
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.registered-devices.show', $device) }}"
                                               class="text-sm text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            @if(!$device->is_revoked)
                                                <form action="{{ route('admin.registered-devices.revoke', $device) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Revoke this device\'s API key? The device will no longer be able to access configs.')">
                                                    @csrf
                                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                                        <i class="fas fa-ban"></i> Revoke
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $devices->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
