<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center mb-6">
                <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('admin.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Leave blank to keep current password">
                        <p class="text-gray-500 text-sm mt-1">Leave blank to keep the current password.</p>
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" id="role" onchange="toggleViewerOptions()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @php $currentRole = $user->roles->first()?->slug ?? 'viewer'; @endphp
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" {{ old('role', $currentRole) == $role->slug ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6" id="productAccess" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Access</label>
                        <p class="text-sm text-gray-500 mb-3">Select which products this user can access:</p>
                        <div class="space-y-2">
                            @foreach($products as $productSlug => $product)
                                <label class="flex items-center">
                                    <input type="checkbox" name="products[]" value="{{ $productSlug }}"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        {{ in_array($productSlug, old('products', $assignedProducts)) ? 'checked' : '' }}>
                                    <span class="ml-2 text-gray-700">{{ $product['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('products')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6" id="dashboardAccess" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dashboard Access</label>
                        <p class="text-sm text-gray-500 mb-3">Select which dashboards this user can access (at least one required):</p>
                        <div class="flex gap-6">
                            <label class="flex items-center px-4 py-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="has_health_access" value="1"
                                    class="rounded border-red-300 text-red-600 focus:ring-red-500"
                                    {{ old('has_health_access', $user->has_health_access) ? 'checked' : '' }}>
                                <span class="ml-3">
                                    <i class="fas fa-heartbeat text-red-500 mr-1"></i>
                                    <span class="font-medium text-gray-700">Health</span>
                                    <span class="block text-xs text-gray-500">Errors, crashes, heartbeats</span>
                                </span>
                            </label>
                            <label class="flex items-center px-4 py-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="has_analytics_access" value="1"
                                    class="rounded border-purple-300 text-purple-600 focus:ring-purple-500"
                                    {{ old('has_analytics_access', $user->has_analytics_access) ? 'checked' : '' }}>
                                <span class="ml-3">
                                    <i class="fas fa-chart-line text-purple-500 mr-1"></i>
                                    <span class="font-medium text-gray-700">Analytics</span>
                                    <span class="block text-xs text-gray-500">Sessions, events, feedback</span>
                                </span>
                            </label>
                        </div>
                        @error('has_health_access')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleViewerOptions() {
            const role = document.getElementById('role').value;
            const isViewer = role === 'viewer';
            document.getElementById('productAccess').style.display = isViewer ? 'block' : 'none';
            document.getElementById('dashboardAccess').style.display = isViewer ? 'block' : 'none';
        }
        // Run on page load
        toggleViewerOptions();
    </script>
</x-app-layout>
