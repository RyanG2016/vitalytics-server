<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-key mr-2 text-amber-600"></i> Registration Tokens
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Create tokens for device provisioning</p>
                </div>
                <a href="{{ route('admin.registration-tokens.create') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md font-medium">
                    <i class="fas fa-plus mr-2"></i> Create Token
                </a>
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
                            <option value="expired" {{ $statusFilter === 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="exhausted" {{ $statusFilter === 'exhausted' ? 'selected' : '' }}>Exhausted</option>
                            <option value="revoked" {{ $statusFilter === 'revoked' ? 'selected' : '' }}>Revoked</option>
                        </select>
                    </div>
                    @if($appFilter || $statusFilter)
                        <a href="{{ route('admin.registration-tokens.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    @endif
                </form>
            </div>

            <!-- Tokens List -->
            @if($tokens->isEmpty())
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-key text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No registration tokens found</p>
                    <p class="text-gray-400 text-sm mt-1">Create a token to start provisioning devices</p>
                    <a href="{{ route('admin.registration-tokens.create') }}" class="inline-block mt-4 text-amber-600 hover:text-amber-800">
                        <i class="fas fa-plus mr-1"></i> Create Token
                    </a>
                </div>
            @else
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uses</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($tokens as $token)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div>
                                            <code class="text-sm bg-gray-100 px-2 py-0.5 rounded">{{ $token->token_prefix }}...</code>
                                            @if($token->name)
                                                <p class="text-sm text-gray-600 mt-1">{{ $token->name }}</p>
                                            @endif
                                            <p class="text-xs text-gray-400 mt-1">
                                                by {{ $token->creator?->name ?? 'System' }}
                                                &middot; {{ $token->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-900">{{ $token->app_identifier }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm">{{ $token->uses_display }}</span>
                                        @if($token->deviceApiKeys->count() > 0)
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-laptop mr-1"></i> {{ $token->deviceApiKeys->count() }} device(s)
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm {{ $token->isExpired() ? 'text-red-600' : 'text-gray-900' }}">
                                            {{ $token->expires_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        </span>
                                        @if(!$token->isExpired())
                                            <p class="text-xs text-gray-500">{{ $token->expires_at->diffForHumans() }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @php $status = $token->status; @endphp
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            {{ $status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $status === 'expired' ? 'bg-gray-100 text-gray-600' : '' }}
                                            {{ $status === 'exhausted' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $status === 'revoked' ? 'bg-red-100 text-red-800' : '' }}
                                        ">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.registration-tokens.show', $token) }}"
                                               class="text-sm text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            @if($token->status === 'active')
                                                <form action="{{ route('admin.registration-tokens.destroy', $token) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Revoke this token? Devices that already registered will not be affected.')">
                                                    @csrf
                                                    @method('DELETE')
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
                    {{ $tokens->appends(request()->query())->links() }}
                </div>
            @endif

            <!-- Help Text -->
            <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <h3 class="font-semibold text-amber-800 mb-2"><i class="fas fa-info-circle mr-2"></i> How It Works</h3>
                <ul class="text-sm text-amber-700 space-y-1">
                    <li><strong>1.</strong> Create a registration token for your app</li>
                    <li><strong>2.</strong> Share the token with your deployment technician</li>
                    <li><strong>3.</strong> Device uses token to register and receive a permanent API key</li>
                    <li><strong>4.</strong> Token expires or reaches max uses automatically</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
