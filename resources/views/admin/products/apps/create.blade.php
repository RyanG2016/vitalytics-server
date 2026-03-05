<x-app-layout>
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to {{ $product->name }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">
                <i class="fas fa-plus mr-2"></i> Add App to {{ $product->name }}
            </h1>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('admin.products.apps.store', $product) }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label for="platform" class="block text-sm font-medium text-gray-700 mb-1">Platform *</label>
                    <select name="platform" id="platform" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select a platform...</option>
                        @foreach($platforms as $key => $platform)
                            <option value="{{ $key }}" {{ old('platform') == $key ? 'selected' : '' }}
                                data-used="{{ in_array($key, $usedPlatforms) ? '1' : '0' }}">
                                {{ $platform['name'] }}{{ in_array($key, $usedPlatforms) ? ' (exists - use suffix below)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('platform')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p id="platform-warning" class="text-amber-600 text-xs mt-1 hidden">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        This platform already exists. Enter a custom suffix below to create another app.
                    </p>
                </div>

                <div class="mb-4">
                    <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Identifier Suffix</label>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 font-mono">{{ $product->slug }}-</span>
                        <input type="text" name="suffix" id="suffix" value="{{ old('suffix') }}"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                            placeholder="portal, marketing, admin...">
                    </div>
                    <p class="text-gray-500 text-xs mt-1">
                        Leave blank to use default (e.g., "portal" for web, "ios" for iOS). Use custom suffix for multiple apps of same platform.
                    </p>
                    <p class="text-gray-500 text-xs mt-1">
                        Final identifier: <span id="identifier-preview" class="font-mono font-medium">{{ $product->slug }}-...</span>
                    </p>
                    @error('suffix')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">App Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Leave blank to auto-generate">
                    <p class="text-gray-500 text-xs mt-1">e.g., "{{ $product->name }} iOS" - auto-generated if left blank</p>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color (Tailwind) *</label>
                    <select name="color" id="color" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['blue', 'green', 'red', 'yellow', 'purple', 'indigo', 'pink', 'orange', 'teal', 'cyan', 'gray'] as $color)
                        <option value="{{ $color }}" {{ old('color', 'blue') == $color ? 'selected' : '' }}>
                            {{ ucfirst($color) }}
                        </option>
                        @endforeach
                    </select>
                    @error('color')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="generate_key" value="1" checked
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Generate API Key</span>
                    </label>
                    <p class="text-gray-500 text-xs mt-1 ml-6">The key will be shown once after creation. Save it securely.</p>
                </div>

                <div class="mb-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.products.edit', $product) }}" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium">
                        <i class="fas fa-plus mr-2"></i> Add App
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const platformSelect = document.getElementById('platform');
        const suffixInput = document.getElementById('suffix');
        const identifierPreview = document.getElementById('identifier-preview');
        const platformWarning = document.getElementById('platform-warning');
        const productSlug = '{{ $product->slug }}';

        function updatePreview() {
            const platform = platformSelect.value;
            const customSuffix = suffixInput.value.trim();

            if (!platform) {
                identifierPreview.textContent = productSlug + '-...';
                return;
            }

            let suffix;
            if (customSuffix) {
                suffix = customSuffix;
            } else {
                suffix = platform === 'web' ? 'portal' : platform;
            }
            identifierPreview.textContent = productSlug + '-' + suffix;
        }

        function updateWarning() {
            const selectedOption = platformSelect.options[platformSelect.selectedIndex];
            const isUsed = selectedOption && selectedOption.dataset.used === '1';
            platformWarning.classList.toggle('hidden', !isUsed);
        }

        platformSelect.addEventListener('change', function() {
            updatePreview();
            updateWarning();
        });

        suffixInput.addEventListener('input', updatePreview);

        // Initialize on page load
        updatePreview();
        updateWarning();
    </script>
</x-app-layout>
