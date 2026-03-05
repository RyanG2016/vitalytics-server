<x-app-layout>
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-cubes mr-2"></i> Products & Apps
        </h1>
        <a href="{{ route('admin.products.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold">
            <i class="fas fa-plus mr-2"></i> Add Product
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-600"></i>
        <p class="text-green-800">{{ session('success') }}</p>
    </div>
    @endif

    <div id="reorder-toast" class="hidden mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center justify-between">
        <span class="text-blue-800"><i class="fas fa-arrows-alt mr-2"></i> Drag products to reorder. Order saved automatically.</span>
    </div>

    @if($products->isEmpty())
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <i class="fas fa-cubes text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-600 mb-4">No products configured yet.</p>
        <a href="{{ route('admin.products.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">
            <i class="fas fa-plus mr-1"></i> Add your first product
        </a>
    </div>
    @else
    <div id="products-list" class="space-y-4">
        @foreach($products as $product)
        <div class="bg-white rounded-lg shadow overflow-hidden product-item" data-id="{{ $product->id }}">
            <div class="p-4 flex items-center justify-between" style="border-left: 4px solid {{ $product->color }}">
                <div class="flex items-center gap-4">
                    <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 px-2">
                        <i class="fas fa-grip-vertical text-lg"></i>
                    </div>
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: {{ $product->color }}20">
                        <i class="fas {{ $product->icon }} text-xl" style="color: {{ $product->color }}"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h2>
                        <p class="text-sm text-gray-500">
                            <span class="font-mono">{{ $product->slug }}</span>
                            <span class="mx-2">•</span>
                            {{ $product->apps->count() }} {{ Str::plural('app', $product->apps->count()) }}
                            @if(!$product->is_active)
                            <span class="mx-2">•</span>
                            <span class="text-red-600">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline"
                          onsubmit="return confirm('Delete {{ $product->name }} and all its apps? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>

            @if($product->apps->isNotEmpty())
            <div class="border-t border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-wrap gap-2">
                    @foreach($product->apps as $app)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white rounded-lg border border-gray-200 text-sm">
                        <i class="fab {{ $app->platform_icon }} text-gray-500"></i>
                        <span class="text-gray-700">{{ $app->name }}</span>
                        @if($app->api_key_prefix)
                        <span class="text-xs font-mono text-gray-400">{{ $app->api_key_prefix }}</span>
                        @else
                        <span class="text-xs text-red-500">No key</span>
                        @endif
                        @if(!$app->is_active)
                        <span class="text-xs text-red-500">(inactive)</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    @if($products->count() > 1)
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productsList = document.getElementById('products-list');
            const toast = document.getElementById('reorder-toast');

            if (productsList) {
                new Sortable(productsList, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    onStart: function() {
                        toast.classList.remove('hidden');
                    },
                    onEnd: function() {
                        const items = productsList.querySelectorAll('.product-item');
                        const order = Array.from(items).map(item => item.dataset.id);

                        fetch('{{ route('admin.products.reorder') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ order: order })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toast.innerHTML = '<span class="text-green-800"><i class="fas fa-check mr-2"></i> Order saved!</span>';
                                toast.classList.remove('bg-blue-50', 'border-blue-200');
                                toast.classList.add('bg-green-50', 'border-green-200');
                                setTimeout(() => {
                                    toast.classList.add('hidden');
                                    toast.innerHTML = '<span class="text-blue-800"><i class="fas fa-arrows-alt mr-2"></i> Drag products to reorder. Order saved automatically.</span>';
                                    toast.classList.remove('bg-green-50', 'border-green-200');
                                    toast.classList.add('bg-blue-50', 'border-blue-200');
                                }, 2000);
                            }
                        })
                        .catch(error => {
                            console.error('Error saving order:', error);
                            toast.innerHTML = '<span class="text-red-800"><i class="fas fa-exclamation-triangle mr-2"></i> Error saving order. Please try again.</span>';
                            toast.classList.remove('bg-blue-50', 'border-blue-200');
                            toast.classList.add('bg-red-50', 'border-red-200');
                        });
                    }
                });
            }
        });
    </script>
    @endif
</x-app-layout>
