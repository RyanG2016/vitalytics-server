<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center mb-6">
                <a href="{{ route('admin.registered-devices.index') }}" class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-laptop mr-2 text-green-600"></i> {{ $device->display_name }}
                    </h1>
                    <p class="text-gray-500 text-sm mt-1 font-mono">{{ $device->device_id }}</p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $device->is_revoked ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ $device->is_revoked ? 'Revoked' : 'Active' }}
                </span>
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

            <!-- New API Key Display (after regeneration) -->
            @if(session('newApiKey'))
                <div class="bg-green-50 border-2 border-green-300 rounded-lg p-6 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-key text-green-600 text-xl mt-1 mr-4"></i>
                        <div class="flex-1">
                            <h3 class="font-semibold text-green-800 text-lg">New API Key Generated</h3>
                            <p class="text-green-700 mt-1">
                                This key will only be shown once. Copy it now and update the device.
                            </p>
                            <div class="mt-4 relative">
                                <input type="text" id="key-input" readonly value="{{ session('newApiKey') }}"
                                       class="w-full font-mono text-sm bg-white border-green-300 rounded-md pr-24">
                                <button type="button" onclick="copyKey()"
                                        class="absolute right-1 top-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded">
                                    <i class="fas fa-copy mr-1"></i> Copy
                                </button>
                            </div>
                            <p id="copy-feedback" class="text-sm text-green-600 mt-2 hidden">
                                <i class="fas fa-check mr-1"></i> Copied to clipboard!
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Device Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Device Information</h2>

                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Device ID</dt>
                            <dd class="mt-1 font-mono text-sm bg-gray-50 px-2 py-1 rounded break-all">{{ $device->device_id }}</dd>
                        </div>

                        @if($device->device_name)
                            <div>
                                <dt class="text-sm text-gray-500">Device Name</dt>
                                <dd class="mt-1">{{ $device->device_name }}</dd>
                            </div>
                        @endif

                        @if($device->device_hostname)
                            <div>
                                <dt class="text-sm text-gray-500">Hostname</dt>
                                <dd class="mt-1">{{ $device->device_hostname }}</dd>
                            </div>
                        @endif

                        @if($device->device_os)
                            <div>
                                <dt class="text-sm text-gray-500">Operating System</dt>
                                <dd class="mt-1">{{ $device->device_os }}</dd>
                            </div>
                        @endif

                        <div>
                            <dt class="text-sm text-gray-500">Application</dt>
                            <dd class="mt-1 font-medium">{{ $device->app_identifier }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-gray-500">API Key Prefix</dt>
                            <dd class="mt-1 font-mono text-sm bg-gray-50 px-2 py-1 rounded inline-block">{{ $device->key_prefix }}...</dd>
                        </div>
                    </dl>
                </div>

                <!-- Registration Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Registration Details</h2>

                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Registered At</dt>
                            <dd class="mt-1">{{ $device->created_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}</dd>
                        </div>

                        @if($device->registered_ip)
                            <div>
                                <dt class="text-sm text-gray-500">Registered From IP</dt>
                                <dd class="mt-1 font-mono text-sm">{{ $device->registered_ip }}</dd>
                            </div>
                        @endif

                        @if($device->registrationToken)
                            <div>
                                <dt class="text-sm text-gray-500">Registration Token</dt>
                                <dd class="mt-1">
                                    <a href="{{ route('admin.registration-tokens.show', $device->registrationToken) }}"
                                       class="text-blue-600 hover:underline">
                                        {{ $device->registrationToken->token_prefix }}...
                                    </a>
                                    @if($device->registrationToken->name)
                                        <span class="text-gray-500">({{ $device->registrationToken->name }})</span>
                                    @endif
                                </dd>
                            </div>
                        @endif

                        <div>
                            <dt class="text-sm text-gray-500">Last Used</dt>
                            <dd class="mt-1">
                                @if($device->last_used_at)
                                    {{ $device->last_used_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    <span class="text-gray-500">({{ $device->last_used_at->diffForHumans() }})</span>
                                @else
                                    <span class="text-gray-400">Never</span>
                                @endif
                            </dd>
                        </div>

                        @if($device->last_used_ip)
                            <div>
                                <dt class="text-sm text-gray-500">Last Used From IP</dt>
                                <dd class="mt-1 font-mono text-sm">{{ $device->last_used_ip }}</dd>
                            </div>
                        @endif

                        @if($device->is_revoked)
                            <div class="pt-4 border-t border-gray-200">
                                <dt class="text-sm text-red-500 font-medium">Revocation Details</dt>
                                <dd class="mt-2 space-y-1">
                                    <p class="text-sm">
                                        <span class="text-gray-500">Revoked at:</span>
                                        {{ $device->revoked_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    </p>
                                    @if($device->revokedByUser)
                                        <p class="text-sm">
                                            <span class="text-gray-500">Revoked by:</span>
                                            {{ $device->revokedByUser->name }}
                                        </p>
                                    @endif
                                    @if($device->revoke_reason)
                                        <p class="text-sm">
                                            <span class="text-gray-500">Reason:</span>
                                            {{ $device->revoke_reason }}
                                        </p>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Actions</h2>

                <div class="flex flex-wrap gap-4">
                    @if(!$device->is_revoked)
                        <form action="{{ route('admin.registered-devices.regenerate-key', $device) }}" method="POST"
                              onsubmit="return confirm('Generate a new API key? The old key will be revoked immediately.')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-md text-sm font-medium">
                                <i class="fas fa-sync-alt mr-2"></i> Regenerate API Key
                            </button>
                        </form>

                        <form action="{{ route('admin.registered-devices.revoke', $device) }}" method="POST"
                              onsubmit="return confirm('Revoke this device\'s API key? It will no longer be able to access configs.')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium">
                                <i class="fas fa-ban mr-2"></i> Revoke API Key
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.registered-devices.destroy', $device) }}" method="POST"
                          onsubmit="return confirm('Delete this device completely? It will need to re-register to access configs.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium">
                            <i class="fas fa-trash mr-2"></i> Delete Device
                        </button>
                    </form>
                </div>
            </div>

            <!-- Audit Logs -->
            <div class="mt-6 bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Audit Log</h2>

                @if($logs->isEmpty())
                    <p class="text-gray-500 text-sm">No audit events recorded.</p>
                @else
                    <div class="space-y-3">
                        @foreach($logs as $log)
                            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                    {{ $log->severity === 'success' ? 'bg-green-100' : '' }}
                                    {{ $log->severity === 'info' ? 'bg-blue-100' : '' }}
                                    {{ $log->severity === 'warning' ? 'bg-amber-100' : '' }}
                                    {{ $log->severity === 'error' ? 'bg-red-100' : '' }}
                                ">
                                    <i class="fas fa-{{ $log->severity === 'success' ? 'check' : ($log->severity === 'error' ? 'times' : 'info') }} text-sm
                                        {{ $log->severity === 'success' ? 'text-green-600' : '' }}
                                        {{ $log->severity === 'info' ? 'text-blue-600' : '' }}
                                        {{ $log->severity === 'warning' ? 'text-amber-600' : '' }}
                                        {{ $log->severity === 'error' ? 'text-red-600' : '' }}
                                    "></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $log->event_label }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $log->created_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        @if($log->ip_address)
                                            &middot; {{ $log->ip_address }}
                                        @endif
                                        @if($log->user)
                                            &middot; by {{ $log->user->name }}
                                        @endif
                                    </p>
                                    @if($log->details && count($log->details) > 0)
                                        <div class="text-xs text-gray-600 mt-2 bg-white p-2 rounded">
                                            @foreach($log->details as $key => $value)
                                                @if($value)
                                                    <span class="inline-block mr-3">
                                                        <span class="text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        {{ is_array($value) ? json_encode($value) : $value }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function copyKey() {
            const input = document.getElementById('key-input');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);

            const feedback = document.getElementById('copy-feedback');
            feedback.classList.remove('hidden');
            setTimeout(() => feedback.classList.add('hidden'), 3000);
        }
    </script>
</x-app-layout>
