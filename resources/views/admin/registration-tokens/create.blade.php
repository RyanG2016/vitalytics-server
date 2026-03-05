<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center mb-6">
                <a href="{{ route('admin.registration-tokens.index') }}" class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-key mr-2 text-amber-600"></i> Create Registration Token
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Generate a token for device provisioning</p>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <form method="POST" action="{{ route('admin.registration-tokens.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <!-- App Selection -->
                        <div>
                            <label for="app_identifier" class="block text-sm font-medium text-gray-700 mb-1">
                                Application <span class="text-red-500">*</span>
                            </label>
                            <select name="app_identifier" id="app_identifier" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">Select an application...</option>
                                @foreach($apps as $app)
                                    <option value="{{ $app->identifier }}" {{ old('app_identifier') === $app->identifier ? 'selected' : '' }}>
                                        {{ $app->name }} ({{ $app->identifier }})
                                    </option>
                                @endforeach
                            </select>
                            @error('app_identifier')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Token Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Token Name (optional)
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                   placeholder="e.g., Clinic ABC - January deployment"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            <p class="text-xs text-gray-500 mt-1">A friendly name to help identify this token's purpose</p>
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Expiry -->
                        <div>
                            <label for="expires_in_hours" class="block text-sm font-medium text-gray-700 mb-1">
                                Expires In <span class="text-red-500">*</span>
                            </label>
                            <select name="expires_in_hours" id="expires_in_hours" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="24" {{ old('expires_in_hours', '72') == '24' ? 'selected' : '' }}>24 hours (1 day)</option>
                                <option value="48" {{ old('expires_in_hours', '72') == '48' ? 'selected' : '' }}>48 hours (2 days)</option>
                                <option value="72" {{ old('expires_in_hours', '72') == '72' ? 'selected' : '' }}>72 hours (3 days)</option>
                                <option value="168" {{ old('expires_in_hours', '72') == '168' ? 'selected' : '' }}>168 hours (1 week)</option>
                                <option value="336" {{ old('expires_in_hours', '72') == '336' ? 'selected' : '' }}>336 hours (2 weeks)</option>
                                <option value="720" {{ old('expires_in_hours', '72') == '720' ? 'selected' : '' }}>720 hours (30 days)</option>
                            </select>
                            @error('expires_in_hours')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Max Uses -->
                        <div>
                            <label for="max_uses" class="block text-sm font-medium text-gray-700 mb-1">
                                Maximum Uses
                            </label>
                            <input type="number" name="max_uses" id="max_uses" value="{{ old('max_uses', '1') }}"
                                   min="1" max="1000" placeholder="Leave empty for unlimited"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            <p class="text-xs text-gray-500 mt-1">How many devices can register with this token (leave empty for unlimited)</p>
                            @error('max_uses')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Warning -->
                    <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-amber-600 mt-0.5 mr-3"></i>
                            <div>
                                <p class="text-sm text-amber-800 font-medium">Important</p>
                                <p class="text-sm text-amber-700 mt-1">
                                    The full token will only be shown once after creation. Make sure to copy it immediately.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('admin.registration-tokens.index') }}"
                           class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md font-medium">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-md font-medium">
                            <i class="fas fa-key mr-2"></i> Generate Token
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
