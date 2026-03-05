<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <a href="{{ route('admin.configs.index') }}" class="text-gray-600 hover:text-gray-800 mr-4">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-file-code mr-2 text-blue-600"></i> {{ $config->config_key }}
                        </h1>
                        <p class="text-gray-500 text-sm mt-1">{{ $config->app_identifier }} / {{ $config->filename }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 text-sm {{ $config->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }} rounded-full">
                        {{ $config->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                        Version {{ $config->current_version }}
                    </span>
                </div>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Edit Form -->
                <div class="lg:col-span-2">
                    <form action="{{ route('admin.configs.update', $config) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Content Editor -->
                        <div class="bg-white shadow rounded-lg p-6 mb-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Config Content</h2>

                            <div class="mb-4">
                                <textarea name="content" id="content" rows="20"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                                          required>{{ old('content', $config->getCurrentContent()) }}</textarea>
                                @error('content')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="change_notes" class="block text-sm font-medium text-gray-700 mb-1">
                                    Change Notes (for this version)
                                </label>
                                <input type="text" name="change_notes" id="change_notes" value="{{ old('change_notes') }}"
                                       placeholder="Describe what changed..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                                    <i class="fas fa-save mr-2"></i> Save Changes
                                </button>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Settings</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="filename" class="block text-sm font-medium text-gray-700 mb-1">Filename</label>
                                    <input type="text" name="filename" id="filename" value="{{ old('filename', $config->filename) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                <div>
                                    <label for="content_type" class="block text-sm font-medium text-gray-700 mb-1">Content Type</label>
                                    <select name="content_type" id="content_type"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @foreach($contentTypes as $type)
                                            <option value="{{ $type }}" {{ old('content_type', $config->content_type) === $type ? 'selected' : '' }}>
                                                {{ strtoupper($type) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <input type="text" name="description" id="description" value="{{ old('description', $config->description) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="hidden" name="embed_version_header" value="0">
                                    <input type="checkbox" name="embed_version_header" value="1"
                                           {{ old('embed_version_header', $config->embed_version_header) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Embed version header</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label for="version_header_template" class="block text-sm font-medium text-gray-700 mb-1">
                                    Custom Header Template
                                </label>
                                <textarea name="version_header_template" id="version_header_template" rows="4"
                                          placeholder="Leave empty for default"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">{{ old('version_header_template', $config->version_header_template) }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1"
                                           {{ old('is_active', $config->is_active) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Active (available to clients)</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Version History Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-history mr-2 text-gray-500"></i> Version History
                        </h2>

                        @if($config->versions->isEmpty())
                            <p class="text-gray-500 text-sm">No versions yet. Save content to create the first version.</p>
                        @else
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                @foreach($config->versions as $version)
                                    <div class="p-3 rounded-lg border {{ $version->version === $config->current_version ? 'border-blue-300 bg-blue-50' : 'border-gray-200' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-medium {{ $version->version === $config->current_version ? 'text-blue-800' : 'text-gray-800' }}">
                                                v{{ $version->version }}
                                                @if($version->version === $config->current_version)
                                                    <span class="ml-1 text-xs bg-blue-200 text-blue-800 px-1.5 py-0.5 rounded">current</span>
                                                @endif
                                            </span>
                                            <span class="text-xs text-gray-500">{{ $version->formatted_size }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-1">
                                            {{ $version->created_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        </p>
                                        @if($version->creator)
                                            <p class="text-xs text-gray-400">by {{ $version->creator->name }}</p>
                                        @endif
                                        @if($version->change_notes)
                                            <p class="text-xs text-gray-600 mt-1 italic">{{ Str::limit($version->change_notes, 50) }}</p>
                                        @endif

                                        <div class="flex gap-2 mt-2">
                                            <button type="button"
                                                    onclick="viewVersion({{ $version->version }})"
                                                    class="text-xs text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            @if($version->version !== $config->current_version)
                                                <form action="{{ route('admin.configs.rollback', $config) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Rollback to version {{ $version->version }}? This will create a new version with the old content.')">
                                                    @csrf
                                                    <input type="hidden" name="version" value="{{ $version->version }}">
                                                    <button type="submit" class="text-xs text-orange-600 hover:text-orange-800">
                                                        <i class="fas fa-undo"></i> Rollback
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- API Info -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-6">
                        <h3 class="font-semibold text-gray-700 mb-2 text-sm">
                            <i class="fas fa-link mr-1"></i> API Endpoint
                        </h3>
                        <code class="text-xs bg-white px-2 py-1 rounded border block break-all">
                            GET /api/v1/config/{{ $config->app_identifier }}/{{ $config->config_key }}
                        </code>
                        @if($config->current_version > 0)
                            <p class="text-xs text-gray-500 mt-2">
                                Hash: <code class="bg-white px-1 rounded">{{ Str::limit($config->getCurrentHash(), 16) }}...</code>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Version View Modal -->
    <div id="version-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold" id="modal-title">Version</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <div class="mb-4 flex gap-4 text-sm text-gray-600">
                    <span id="modal-date"></span>
                    <span id="modal-author"></span>
                    <span id="modal-size"></span>
                </div>
                <div id="modal-notes" class="mb-4 text-sm text-gray-600 italic"></div>
                <pre id="modal-content" class="bg-gray-50 p-4 rounded-lg overflow-x-auto text-sm font-mono border"></pre>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function viewVersion(version) {
            fetch(`/admin/configs/{{ $config->id }}/version/${version}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modal-title').textContent = `Version ${data.version}`;
                    document.getElementById('modal-date').textContent = data.createdAt;
                    document.getElementById('modal-author').textContent = `by ${data.createdBy}`;
                    document.getElementById('modal-size').textContent = data.size;
                    document.getElementById('modal-notes').textContent = data.notes || '';
                    document.getElementById('modal-content').textContent = data.content;
                    document.getElementById('version-modal').classList.remove('hidden');
                })
                .catch(error => {
                    alert('Error loading version');
                    console.error(error);
                });
        }

        function closeModal() {
            document.getElementById('version-modal').classList.add('hidden');
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // Close modal on backdrop click
        document.getElementById('version-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</x-app-layout>
