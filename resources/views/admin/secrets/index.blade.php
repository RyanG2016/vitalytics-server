<x-app-layout>

<div>
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-key mr-2"></i> App Secrets
            </h1>
            <p class="text-gray-600 mt-1">Manage secrets for client API key retrieval</p>
        </div>
        <a href="{{ route('dashboard') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-semibold">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>

    {{-- Success Message with New Secret --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-0.5 mr-3"></i>
            <div class="flex-1">
                <p class="text-green-800 font-medium">{{ session('success') }}</p>
                @if(session('new_secret'))
                <div class="mt-3 bg-green-100 rounded-lg p-4">
                    <p class="text-sm text-green-800 mb-2">
                        <strong>New secret for {{ session('new_secret_app') }}:</strong>
                    </p>
                    <div class="flex items-center gap-2">
                        <code id="new-secret" class="bg-white px-3 py-2 rounded border border-green-300 text-green-900 font-mono text-sm flex-1">{{ session('new_secret') }}</code>
                        <button onclick="copySecret()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <p class="text-xs text-green-700 mt-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Save this secret now! It will not be shown again.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Info Box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">How App Secrets Work</p>
                <ul class="list-disc list-inside space-y-1 text-blue-700">
                    <li>Each sub-product needs a secret to retrieve its API key</li>
                    <li>Secrets are bundled in client apps and rarely change</li>
                    <li>When rotating, old secrets have a grace period before expiring</li>
                    <li>API keys (retrieved via secrets) can be rotated in config anytime</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Products and Secrets --}}
    <div class="space-y-6">
        @foreach($productSecrets as $productId => $product)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            {{-- Product Header --}}
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $product['color'] }}20;">
                        <i class="fas {{ $product['icon'] }} text-xl" style="color: {{ $product['color'] }};"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ $product['name'] }}</h2>
                        <p class="text-sm text-gray-500">{{ count($product['subProducts']) }} sub-products</p>
                    </div>
                </div>
            </div>

            {{-- Sub-Products --}}
            <div class="divide-y divide-gray-100">
                @foreach($product['subProducts'] as $appId => $subProduct)
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fab {{ $subProduct['icon'] }} text-lg text-gray-500"></i>
                            <div>
                                <h3 class="font-medium text-gray-900">{{ $subProduct['name'] }}</h3>
                                <p class="text-xs text-gray-400 font-mono">{{ $appId }}</p>
                            </div>
                        </div>

                        {{-- Generate Button --}}
                        <form action="{{ route('admin.secrets.generate', $appId) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="grace_period" value="30">
                            @if(!$subProduct['has_active_secret'])
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-medium">
                                <i class="fas fa-plus mr-1"></i> Generate Secret
                            </button>
                            @else
                            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded text-sm font-medium"
                                    onclick="return confirm('This will rotate the secret. Current secrets will expire in 30 days. Continue?')">
                                <i class="fas fa-sync-alt mr-1"></i> Rotate Secret
                            </button>
                            @endif
                        </form>
                    </div>

                    {{-- Secrets List --}}
                    @if($subProduct['secrets']->count() > 0)
                    <div class="mt-4 space-y-2">
                        @foreach($subProduct['secrets'] as $secret)
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3 {{ !$secret['is_active'] ? 'opacity-50' : '' }}">
                            <div class="flex items-center gap-3">
                                @if($secret['is_active'])
                                    @if($secret['expires_at'])
                                        @if($secret['is_expiring_soon'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i> Expires in {{ $secret['days_until_expiry'] }} days
                                        </span>
                                        @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            <i class="fas fa-hourglass-half mr-1"></i> Expires {{ $secret['expires_at'] }}
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
                                <span class="text-sm text-gray-600">{{ $secret['label'] }}</span>
                                <span class="text-xs text-gray-400">Created {{ $secret['created_at'] }}</span>
                            </div>

                            @if($secret['is_active'] && $secret['expires_at'])
                            <div class="flex items-center gap-2">
                                <form action="{{ route('admin.secrets.extend', $secret['id']) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="days" value="30">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-plus-circle mr-1"></i> +30 days
                                    </button>
                                </form>
                                <form action="{{ route('admin.secrets.revoke', $secret['id']) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm"
                                            onclick="return confirm('Revoke this secret immediately? Any clients using it will stop working.')">
                                        <i class="fas fa-ban mr-1"></i> Revoke
                                    </button>
                                </form>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="mt-4 text-sm text-gray-500 bg-gray-50 rounded-lg p-4 text-center">
                        <i class="fas fa-key text-gray-400 text-2xl mb-2"></i>
                        <p>No secrets generated yet</p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<script>
function copySecret() {
    const secret = document.getElementById('new-secret').textContent;
    navigator.clipboard.writeText(secret).then(() => {
        alert('Secret copied to clipboard!');
    });
}
</script>

</x-app-layout>
