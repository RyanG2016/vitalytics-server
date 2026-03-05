<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center mb-6">
                <a href="{{ route('admin.maintenance.index') }}" class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-plus mr-2 text-orange-600"></i> New Maintenance Notification
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Schedule a maintenance banner for client applications</p>
                </div>
            </div>

            <form action="{{ route('admin.maintenance.store') }}" method="POST">
                @csrf

                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Notification Details</h2>

                    <!-- Title -->
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}"
                               placeholder="e.g., Scheduled Maintenance"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        @error('title')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Message -->
                    <div class="mb-4">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">
                            Message <span class="text-red-500">*</span>
                        </label>
                        <textarea name="message" id="message" rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>{{ old('message', 'The system will be offline for maintenance between 8:30 AM to 9:30 AM CST.') }}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Update the times to match your scheduled maintenance window.</p>
                        @error('message')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Severity -->
                    <div class="mb-4" x-data="{ severity: '{{ old('severity', 'info') }}' }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Severity</label>
                        <div class="flex gap-4">
                            @foreach(['info' => ['Info', 'bg-blue-100 text-blue-700 border-blue-300'], 'warning' => ['Warning', 'bg-yellow-100 text-yellow-700 border-yellow-300'], 'critical' => ['Critical', 'bg-red-100 text-red-700 border-red-300']] as $value => [$label, $classes])
                                <label class="flex items-center px-4 py-2 border rounded-lg cursor-pointer transition-colors"
                                       :class="severity === '{{ $value }}' ? '{{ $classes }}' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="radio" name="severity" value="{{ $value }}" class="sr-only"
                                           x-model="severity" {{ old('severity', 'info') === $value ? 'checked' : '' }}>
                                    <span class="font-medium">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Schedule</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1">
                                Starts At <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" name="starts_at" id="starts_at" value="{{ old('starts_at') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @error('starts_at')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1">
                                Ends At <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" name="ends_at" id="ends_at" value="{{ old('ends_at') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @error('ends_at')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Times are in {{ config('app.timezone') }} timezone.</p>
                </div>

                <!-- Products -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Affected Products</h2>
                    <p class="text-sm text-gray-500 mb-3">Select which products should display this notification:</p>

                    @if($products->isEmpty())
                        <p class="text-gray-500 italic">No products available.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($products as $product)
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="products[]" value="{{ $product->id }}"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           {{ in_array($product->id, old('products', [])) ? 'checked' : '' }}>
                                    <span class="ml-3">
                                        <span class="font-medium text-gray-700">{{ $product->name }}</span>
                                        <span class="text-sm text-gray-500 ml-2">({{ $product->apps->count() }} apps)</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('products')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Options -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Options</h2>

                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="hidden" name="dismissible" value="0">
                            <input type="checkbox" name="dismissible" value="1"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   {{ old('dismissible', true) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">Allow users to dismiss this notification</span>
                        </label>
                        <label class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">Activate immediately (when schedule starts)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="hidden" name="is_test" value="0">
                            <input type="checkbox" name="is_test" value="1"
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                   {{ old('is_test', false) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">Test notification only <span class="text-gray-500 text-sm">(only shown to apps in test mode)</span></span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.maintenance.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                        <i class="fas fa-save mr-2"></i> Create Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
