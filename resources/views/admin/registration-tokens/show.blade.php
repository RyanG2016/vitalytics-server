<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center mb-6">
                <a href="{{ route('admin.registration-tokens.index') }}" class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-key mr-2 text-amber-600"></i> Token Details
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">
                        {{ $token->name ?? $token->token_prefix . '...' }}
                    </p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    {{ $token->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $token->status === 'expired' ? 'bg-gray-100 text-gray-600' : '' }}
                    {{ $token->status === 'exhausted' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $token->status === 'revoked' ? 'bg-red-100 text-red-800' : '' }}
                ">
                    {{ ucfirst($token->status) }}
                </span>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Token Display (only shown once after creation) -->
            @if($plainToken)
                <div class="bg-amber-50 border-2 border-amber-300 rounded-lg p-6 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-amber-600 text-xl mt-1 mr-4"></i>
                        <div class="flex-1">
                            <h3 class="font-semibold text-amber-800 text-lg">Copy Your Registration Token</h3>
                            <p class="text-amber-700 mt-1">
                                This token will only be shown once. Copy it now and store it securely.
                            </p>
                            <div class="mt-4 relative">
                                <input type="text" id="token-input" readonly value="{{ $plainToken }}"
                                       class="w-full font-mono text-sm bg-white border-amber-300 rounded-md pr-24">
                                <button type="button" onclick="copyToken()"
                                        class="absolute right-1 top-1 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded">
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
                <!-- Token Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Token Information</h2>

                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Token Prefix</dt>
                            <dd class="mt-1 font-mono text-sm bg-gray-50 px-2 py-1 rounded inline-block">{{ $token->token_prefix }}...</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-gray-500">Application</dt>
                            <dd class="mt-1 font-medium">{{ $token->app_identifier }}</dd>
                        </div>

                        @if($token->name)
                            <div>
                                <dt class="text-sm text-gray-500">Name</dt>
                                <dd class="mt-1">{{ $token->name }}</dd>
                            </div>
                        @endif

                        <div>
                            <dt class="text-sm text-gray-500">Created By</dt>
                            <dd class="mt-1">{{ $token->creator?->name ?? 'System' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-gray-500">Created At</dt>
                            <dd class="mt-1">{{ $token->created_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-gray-500">Expires At</dt>
                            <dd class="mt-1 {{ $token->isExpired() ? 'text-red-600' : '' }}">
                                {{ $token->expires_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                @if(!$token->isExpired())
                                    <span class="text-gray-500">({{ $token->expires_at->diffForHumans() }})</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm text-gray-500">Usage</dt>
                            <dd class="mt-1">
                                {{ $token->uses_display }}
                                @if($token->max_uses && $token->uses_count >= $token->max_uses)
                                    <span class="text-amber-600">(exhausted)</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if($token->status === 'active')
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <form action="{{ route('admin.registration-tokens.destroy', $token) }}" method="POST"
                                  onsubmit="return confirm('Revoke this token? It cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium">
                                    <i class="fas fa-ban mr-2"></i> Revoke Token
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- Registered Devices -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Registered Devices
                        <span class="text-sm font-normal text-gray-500">({{ $token->deviceApiKeys->count() }})</span>
                    </h2>

                    @if($token->deviceApiKeys->isEmpty())
                        <div class="text-center py-8">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-laptop text-gray-400"></i>
                            </div>
                            <p class="text-gray-500 text-sm">No devices registered yet</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($token->deviceApiKeys as $device)
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-sm">
                                                {{ $device->display_name }}
                                                @if($device->is_revoked)
                                                    <span class="ml-1 px-1.5 py-0.5 text-xs bg-red-100 text-red-700 rounded">Revoked</span>
                                                @endif
                                            </p>
                                            @if($device->device_os)
                                                <p class="text-xs text-gray-500">{{ $device->device_os }}</p>
                                            @endif
                                            <p class="text-xs text-gray-400 mt-1">
                                                Registered {{ $device->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <a href="{{ route('admin.registered-devices.show', $device) }}"
                                           class="text-sm text-blue-600 hover:text-blue-800">
                                            View <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Usage Instructions -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i> Client Usage</h3>
                <p class="text-sm text-blue-700 mb-2">Share this token with your deployment technician. The client application should:</p>
                <ol class="text-sm text-blue-700 list-decimal list-inside space-y-1 ml-2">
                    <li>Store the token in a bootstrap configuration file</li>
                    <li>Call <code class="bg-blue-100 px-1 rounded">POST /api/v1/devices/register</code> with the token</li>
                    <li>Receive and securely store the permanent API key</li>
                    <li>Delete the bootstrap file (token no longer needed)</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        function copyToken() {
            const input = document.getElementById('token-input');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);

            const feedback = document.getElementById('copy-feedback');
            feedback.classList.remove('hidden');
            setTimeout(() => feedback.classList.add('hidden'), 3000);
        }
    </script>
</x-app-layout>
