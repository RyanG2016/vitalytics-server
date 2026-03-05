<x-app-layout>
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.products.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Products
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">
                <i class="fas fa-edit mr-2"></i> Edit Product: {{ $product->name }}
            </h1>
        </div>

        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
            <i class="fas fa-check-circle text-green-600"></i>
            <p class="text-green-800">{{ session('success') }}</p>
        </div>
        @endif

        {{-- New API Key Display --}}
        @if(session('new_api_key'))
        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-key text-yellow-600 mt-1"></i>
                <div class="flex-1">
                    <h3 class="font-semibold text-yellow-800">New API Key Generated for {{ session('new_api_key_app') }}</h3>
                    <p class="text-yellow-700 text-sm mt-1 mb-3">Copy this key now. It will not be shown again!</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 bg-white px-3 py-2 rounded border border-yellow-300 font-mono text-sm break-all">{{ session('new_api_key') }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ session('new_api_key') }}'); this.innerHTML='<i class=\'fas fa-check\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy\'></i>', 2000)"
                                class="px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- New Secret Display --}}
        @if(session('new_secret'))
        <div class="mb-6 bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-shield-alt text-purple-600 mt-1"></i>
                <div class="flex-1">
                    <h3 class="font-semibold text-purple-800">New Secret Generated for {{ session('new_secret_app') }}</h3>
                    <p class="text-purple-700 text-sm mt-1 mb-3">Copy this secret now. It will not be shown again!</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 bg-white px-3 py-2 rounded border border-purple-300 font-mono text-sm break-all">{{ session('new_secret') }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ session('new_secret') }}'); this.innerHTML='<i class=\'fas fa-check\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy\'></i>', 2000)"
                                class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Product Details Form --}}
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Product Details</h2>
            <form action="{{ route('admin.products.update', $product) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                        <input type="text" name="slug" id="slug" value="{{ old('slug', $product->slug) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono" required>
                        @error('slug')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="icon" class="block text-sm font-medium text-gray-700 mb-1">Icon *</label>
                        <input type="text" name="icon" id="icon" value="{{ old('icon', $product->icon) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono" required>
                    </div>
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color *</label>
                        <div class="flex gap-2">
                            <input type="color" name="color" id="color" value="{{ old('color', $product->color) }}"
                                class="h-10 w-14 p-1 border border-gray-300 rounded-md cursor-pointer">
                            <input type="text" id="color_hex" value="{{ old('color', $product->color) }}"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md font-mono text-sm" readonly>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 pb-2">
                            <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Apps Section --}}
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-mobile-alt mr-2"></i> Apps ({{ $product->apps->count() }})
                </h2>
                <a href="{{ route('admin.products.apps.create', $product) }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-sm font-medium">
                    <i class="fas fa-plus mr-1"></i> Add App
                </a>
            </div>

            @if($product->apps->isEmpty())
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-mobile-alt text-3xl mb-3"></i>
                <p>No apps yet. Add your first app to start monitoring.</p>
            </div>
            @else
            <div class="divide-y divide-gray-200">
                @foreach($product->apps as $app)
                <div class="p-4" x-data="{
                    showSecrets: false,
                    showApiKey: false,
                    apiKey: null,
                    loading: false,
                    copied: false,
                    async fetchApiKey() {
                        if (this.apiKey) {
                            this.showApiKey = !this.showApiKey;
                            return;
                        }
                        this.loading = true;
                        try {
                            const response = await fetch('{{ route('admin.products.apps.show-key', [$product, $app]) }}');
                            const data = await response.json();
                            if (data.success) {
                                this.apiKey = data.apiKey;
                                this.showApiKey = true;
                            } else {
                                alert(data.error || 'Failed to load API key');
                            }
                        } catch (e) {
                            alert('Failed to load API key');
                        }
                        this.loading = false;
                    },
                    copyKey() {
                        navigator.clipboard.writeText(this.apiKey);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    }
                }">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-100">
                                <i class="fab {{ $app->platform_icon }} text-gray-600"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900">{{ $app->name }}</span>
                                    @if(!$app->is_active)
                                    <span class="text-xs px-2 py-0.5 bg-red-100 text-red-700 rounded">Inactive</span>
                                    @endif
                                    @if($app->linked_app_id)
                                    <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded" title="Uses API key from {{ $app->linkedApp->name }}">
                                        <i class="fas fa-link mr-1"></i>Linked
                                    </span>
                                    @endif
                                    @if($app->linkedFrom->count() > 0)
                                    <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded" title="{{ $app->linkedFrom->pluck('name')->join(', ') }} use this app's key">
                                        <i class="fas fa-share-alt mr-1"></i>{{ $app->linkedFrom->count() }} linked
                                    </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">
                                    <span class="font-mono">{{ $app->identifier }}</span>
                                    <span class="mx-2">•</span>
                                    {{ $app->platform_name }}
                                    @if($app->linked_app_id)
                                    <span class="mx-2">•</span>
                                    <span class="text-blue-600">Uses key from <span class="font-mono">{{ $app->linkedApp->identifier }}</span></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            @if($app->api_key_prefix)
                            <button @click="fetchApiKey()"
                                    class="text-xs font-mono bg-gray-100 hover:bg-yellow-100 px-2 py-1 rounded flex items-center gap-1 transition"
                                    :class="{ 'bg-yellow-100': showApiKey }"
                                    title="Click to show/hide API key">
                                <i class="fas fa-key text-yellow-600" x-show="!loading"></i>
                                <i class="fas fa-spinner fa-spin text-yellow-600" x-show="loading" x-cloak></i>
                                <span x-show="!showApiKey" class="text-gray-400">{{ $app->api_key_prefix }}</span>
                                <span x-show="showApiKey" x-text="apiKey" class="text-gray-700" x-cloak></span>
                            </button>
                            <button x-show="showApiKey" x-cloak @click="copyKey()"
                                    class="text-xs text-gray-500 hover:text-gray-700" title="Copy API key">
                                <i class="fas fa-copy" x-show="!copied"></i>
                                <i class="fas fa-check text-green-600" x-show="copied" x-cloak></i>
                            </button>
                            @else
                            <span class="text-xs text-red-500">No API key</span>
                            @endif

                            <div class="flex items-center gap-2">
                                <button @click="showSecrets = !showSecrets"
                                        class="text-purple-600 hover:text-purple-800 text-sm"
                                        title="Show/Hide Secrets"
                                        :class="{ 'bg-purple-100 rounded px-2 py-1': showSecrets }">
                                    <i class="fas fa-shield-alt"></i>
                                    <span class="text-xs ml-1">{{ $app->activeSecrets->count() }}</span>
                                </button>

                                {{-- Link/Unlink API Key --}}
                                @if($app->linked_app_id)
                                <form action="{{ route('admin.products.apps.unlink', [$product, $app]) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Unlink from {{ $app->linkedApp->name }}? This app will need its own API key.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm" title="Unlink API Key">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </form>
                                @elseif($product->apps->count() > 1)
                                <div x-data="{ showLinkMenu: false }" class="relative inline-block">
                                    <button @click="showLinkMenu = !showLinkMenu" @click.outside="showLinkMenu = false"
                                            class="text-blue-600 hover:text-blue-800 text-sm" title="Link to another app's API key">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <div x-show="showLinkMenu" x-cloak
                                         class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                        <div class="p-2 border-b border-gray-100">
                                            <span class="text-xs text-gray-500">Link to share API key with:</span>
                                        </div>
                                        @foreach($product->apps->filter(fn($a) => $a->id !== $app->id && $a->linked_app_id === null) as $otherApp)
                                        <form action="{{ route('admin.products.apps.link', [$product, $app]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="linked_app_id" value="{{ $otherApp->id }}">
                                            <button type="submit" class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm flex items-center gap-2">
                                                <i class="fab {{ $otherApp->platform_icon }} text-gray-500"></i>
                                                <span>{{ $otherApp->name }}</span>
                                            </button>
                                        </form>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                <form action="{{ route('admin.products.apps.regenerate-key', [$product, $app]) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Generate a new API key? The old key will stop working immediately.')">
                                    @csrf
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 text-sm" title="Regenerate API Key">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.products.apps.edit', [$product, $app]) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.products.apps.destroy', [$product, $app]) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Delete {{ $app->name }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Secrets Panel --}}
                    <div x-show="showSecrets" x-cloak class="mt-4 bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-semibold text-gray-700">
                                <i class="fas fa-shield-alt mr-1"></i> Client Secrets
                            </h4>
                            <form action="{{ route('admin.products.apps.generate-secret', [$product, $app]) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="grace_period" value="30">
                                @if($app->activeSecrets->isEmpty())
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs font-medium">
                                    <i class="fas fa-plus mr-1"></i> Generate Secret
                                </button>
                                @else
                                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs font-medium"
                                        onclick="return confirm('This will rotate the secret. Current secrets will expire in 30 days. Continue?')">
                                    <i class="fas fa-sync-alt mr-1"></i> Rotate Secret
                                </button>
                                @endif
                            </form>
                        </div>

                        @php $secrets = $app->secrets()->orderBy('created_at', 'desc')->get(); @endphp
                        
                        @if($secrets->isEmpty())
                        <div class="text-sm text-gray-500 text-center py-3">
                            <i class="fas fa-key text-gray-300 text-xl mb-2"></i>
                            <p>No secrets generated yet</p>
                            <p class="text-xs text-gray-400 mt-1">Secrets are used by clients to retrieve API keys</p>
                        </div>
                        @else
                        <div class="space-y-2">
                            @foreach($secrets as $secret)
                            <div class="flex items-center justify-between bg-white rounded px-3 py-2 text-sm {{ !$secret->isActive() ? 'opacity-50' : '' }}">
                                <div class="flex items-center gap-3">
                                    @if($secret->isActive())
                                        @if($secret->expires_at)
                                            @if($secret->isExpiringSoon())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Expires in {{ (int)now()->diffInDays($secret->expires_at, false) }} days
                                            </span>
                                            @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                <i class="fas fa-hourglass-half mr-1"></i> Expires {{ $secret->expires_at->format('M d') }}
                                            </span>
                                            @endif
                                        @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Active
                                        </span>
                                        @endif
                                    @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <i class="fas fa-times-circle mr-1"></i> Expired
                                    </span>
                                    @endif
                                    <span class="text-gray-600">{{ $secret->label }}</span>
                                    <span class="text-xs text-gray-400">{{ $secret->created_at->format('M d, Y') }}</span>
                                </div>

                                @if($secret->isActive() && $secret->expires_at)
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('admin.secrets.extend', $secret) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="days" value="30">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs">
                                            <i class="fas fa-plus-circle"></i> +30d
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.secrets.revoke', $secret) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs"
                                                onclick="return confirm('Revoke this secret immediately?')">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
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

    <script>
        document.getElementById('color').addEventListener('input', function() {
            document.getElementById('color_hex').value = this.value;
        });
    </script>
</x-app-layout>
