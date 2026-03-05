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
                        <i class="fas fa-edit mr-2 text-orange-600"></i> Edit Maintenance Notification
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Update the maintenance banner details</p>
                </div>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Status Badge -->
            <div class="mb-6">
                @if($notification->isCurrentlyActive())
                    <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">
                        <i class="fas fa-broadcast-tower mr-1"></i> Currently Active
                    </span>
                @elseif($notification->isUpcoming())
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
                        <i class="fas fa-clock mr-1"></i> Upcoming
                    </span>
                @elseif($notification->isExpired())
                    <span class="px-3 py-1 bg-gray-100 text-gray-500 text-sm font-medium rounded-full">
                        <i class="fas fa-history mr-1"></i> Expired
                    </span>
                @endif
                @if(!$notification->is_active)
                    <span class="px-3 py-1 bg-red-100 text-red-600 text-sm font-medium rounded-full ml-2">
                        <i class="fas fa-pause mr-1"></i> Disabled
                    </span>
                @endif
                @if($notification->is_test)
                    <span class="px-3 py-1 bg-orange-100 text-orange-600 text-sm font-medium rounded-full ml-2">
                        <i class="fas fa-flask mr-1"></i> Test Only
                    </span>
                @endif
            </div>

            <form id="edit-form" action="{{ route('admin.maintenance.update', $notification) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Notification Details</h2>

                    <!-- Title -->
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title', $notification->title) }}"
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
                                  placeholder="The system will be unavailable for maintenance from 2:00 AM to 4:00 AM CST."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>{{ old('message', $notification->message) }}</textarea>
                        @error('message')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Severity -->
                    <div class="mb-4" x-data="{ severity: '{{ old('severity', $notification->severity) }}' }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Severity</label>
                        <div class="flex gap-4">
                            @foreach(['info' => ['Info', 'bg-blue-100 text-blue-700 border-blue-300'], 'warning' => ['Warning', 'bg-yellow-100 text-yellow-700 border-yellow-300'], 'critical' => ['Critical', 'bg-red-100 text-red-700 border-red-300']] as $value => [$label, $classes])
                                <label class="flex items-center px-4 py-2 border rounded-lg cursor-pointer transition-colors"
                                       :class="severity === '{{ $value }}' ? '{{ $classes }}' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="radio" name="severity" value="{{ $value }}" class="sr-only"
                                           x-model="severity" {{ old('severity', $notification->severity) === $value ? 'checked' : '' }}>
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
                            <input type="datetime-local" name="starts_at" id="starts_at"
                                   value="{{ old('starts_at', $notification->starts_at->format('Y-m-d\TH:i')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @error('starts_at')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1">
                                Ends At <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" name="ends_at" id="ends_at"
                                   value="{{ old('ends_at', $notification->ends_at->format('Y-m-d\TH:i')) }}"
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
                                           {{ in_array($product->id, old('products', $selectedProducts)) ? 'checked' : '' }}>
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
                                   {{ old('dismissible', $notification->dismissible) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">Allow users to dismiss this notification</span>
                        </label>
                        <label class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   {{ old('is_active', $notification->is_active) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">Notification is active</span>
                        </label>
                        <label class="flex items-center">
                            <input type="hidden" name="is_test" value="0">
                            <input type="checkbox" name="is_test" value="1"
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                   {{ old('is_test', $notification->is_test) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">Test notification only <span class="text-gray-500 text-sm">(only shown to apps in test mode)</span></span>
                        </label>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-sm text-gray-500">
                    <div class="flex gap-6">
                        <div>
                            <span class="font-medium">Created by:</span>
                            {{ $notification->creator?->name ?? 'Unknown' }}
                        </div>
                        <div>
                            <span class="font-medium">Created:</span>
                            {{ $notification->created_at->format('M j, Y g:i A') }}
                        </div>
                        @if($notification->updated_at != $notification->created_at)
                            <div>
                                <span class="font-medium">Last updated:</span>
                                {{ $notification->updated_at->format('M j, Y g:i A') }}
                            </div>
                        @endif
                    </div>
                </div>

            </form>

            <!-- Action buttons (separate from main form to avoid nesting) -->
            <div class="flex justify-between">
                <form action="{{ route('admin.maintenance.destroy', $notification) }}" method="POST" class="inline" onsubmit="return confirm('Delete this notification?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-md font-medium">
                        <i class="fas fa-trash mr-2"></i> Delete
                    </button>
                </form>
                <div class="flex gap-3">
                    <a href="{{ route('admin.maintenance.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md font-medium">
                        Cancel
                    </a>
                    <button type="submit" form="edit-form" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
