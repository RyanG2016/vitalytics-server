<x-app-layout>
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to {{ $product->name }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">
                <i class="fas fa-edit mr-2"></i> Edit App: {{ $app->name }}
            </h1>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('admin.products.apps.update', [$product, $app]) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">App Identifier</label>
                    <div class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-md font-mono text-gray-600">
                        {{ $app->identifier }}
                    </div>
                    <p class="text-gray-500 text-xs mt-1">Identifier cannot be changed after creation.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                    <div class="flex items-center gap-2 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600">
                        <i class="fab {{ $app->platform_icon }}"></i>
                        <span>{{ $app->platform_name }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">App Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $app->name) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color (Tailwind) *</label>
                    <select name="color" id="color" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['blue', 'green', 'red', 'yellow', 'purple', 'indigo', 'pink', 'orange', 'teal', 'cyan', 'gray'] as $color)
                        <option value="{{ $color }}" {{ old('color', $app->color) == $color ? 'selected' : '' }}>
                            {{ ucfirst($color) }}
                        </option>
                        @endforeach
                    </select>
                    @error('color')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                    <div class="flex items-center gap-2">
                        @if($app->api_key_prefix)
                        <div class="flex-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md font-mono text-gray-600">
                            {{ $app->api_key_prefix }}
                        </div>
                        <span class="text-xs text-gray-500">Generated {{ $app->api_key_generated_at?->diffForHumans() }}</span>
                        @else
                        <div class="flex-1 px-3 py-2 bg-red-50 border border-red-200 rounded-md text-red-600">
                            No API key generated
                        </div>
                        @endif
                    </div>
                    <p class="text-gray-500 text-xs mt-1">
                        To regenerate the API key, go back to the product page and use the regenerate button.
                    </p>
                </div>

                <div class="mb-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ $app->is_active ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <p class="text-gray-500 text-xs mt-1 ml-6">Inactive apps will not accept health events.</p>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.products.edit', $product) }}" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
