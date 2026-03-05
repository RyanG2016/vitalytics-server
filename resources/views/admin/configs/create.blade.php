<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center mb-6">
                <a href="{{ route('admin.configs.index') }}" class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-plus mr-2 text-blue-600"></i> Create Configuration
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Add a new configuration file for client applications</p>
                </div>
            </div>

            <form action="{{ route('admin.configs.store') }}" method="POST">
                @csrf

                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Config Details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- App Identifier -->
                        <div>
                            <label for="app_identifier" class="block text-sm font-medium text-gray-700 mb-1">
                                App Identifier <span class="text-red-500">*</span>
                            </label>
                            <select name="app_identifier" id="app_identifier" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select an app...</option>
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

                        <!-- Config Key -->
                        <div>
                            <label for="config_key" class="block text-sm font-medium text-gray-700 mb-1">
                                Config Key <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="config_key" id="config_key" value="{{ old('config_key') }}"
                                   pattern="[a-z0-9_-]+"
                                   placeholder="e.g., main, local, rules"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <p class="text-xs text-gray-500 mt-1">Lowercase letters, numbers, underscores, hyphens only</p>
                            @error('config_key')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- Filename -->
                        <div>
                            <label for="filename" class="block text-sm font-medium text-gray-700 mb-1">
                                Filename <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="filename" id="filename" value="{{ old('filename') }}"
                                   placeholder="e.g., config.ini"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @error('filename')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Content Type -->
                        <div>
                            <label for="content_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Content Type <span class="text-red-500">*</span>
                            </label>
                            <select name="content_type" id="content_type" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach($contentTypes as $type)
                                    <option value="{{ $type }}" {{ old('content_type', 'ini') === $type ? 'selected' : '' }}>
                                        {{ strtoupper($type) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('content_type')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <input type="text" name="description" id="description" value="{{ old('description') }}"
                               placeholder="Brief description of this config's purpose"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Version Header Settings -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Version Header</h2>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="hidden" name="embed_version_header" value="0">
                            <input type="checkbox" name="embed_version_header" value="1"
                                   {{ old('embed_version_header', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Embed version header in config content</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Automatically prepends version info when config is downloaded</p>
                    </div>

                    <div>
                        <label for="version_header_template" class="block text-sm font-medium text-gray-700 mb-1">
                            Custom Header Template (optional)
                        </label>
                        <textarea name="version_header_template" id="version_header_template" rows="6"
                                  placeholder="Leave empty to use default template"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">{{ old('version_header_template') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            Placeholders: <code class="bg-gray-100 px-1">{filename}</code>, <code class="bg-gray-100 px-1">{version}</code>,
                            <code class="bg-gray-100 px-1">{updated_at}</code>, <code class="bg-gray-100 px-1">{hash}</code>,
                            <code class="bg-gray-100 px-1">{config_key}</code>, <code class="bg-gray-100 px-1">{app_identifier}</code>
                        </p>
                    </div>
                </div>

                <!-- Initial Content -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Initial Content (optional)</h2>

                    <div class="mb-4">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                            Config Content
                        </label>
                        <textarea name="content" id="content" rows="12"
                                  placeholder="Paste your config content here, or leave empty to add later"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">{{ old('content') }}</textarea>
                        @error('content')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="change_notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Version Notes
                        </label>
                        <input type="text" name="change_notes" id="change_notes" value="{{ old('change_notes', 'Initial version') }}"
                               placeholder="e.g., Initial version"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.configs.index') }}"
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                        <i class="fas fa-save mr-2"></i> Create Config
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
