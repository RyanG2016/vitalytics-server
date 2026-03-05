<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-file-code mr-2 text-blue-600"></i> Remote Configurations
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Manage configuration files served to client applications</p>
                </div>
                <a href="{{ route('admin.configs.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                    <i class="fas fa-plus mr-2"></i> Add Config
                </a>
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

            <!-- Filter -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">Filter by App:</label>
                    <select name="app" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">All Apps</option>
                        @foreach($apps as $app)
                            <option value="{{ $app->identifier }}" {{ $appFilter === $app->identifier ? 'selected' : '' }}>
                                {{ $app->name }} ({{ $app->identifier }})
                            </option>
                        @endforeach
                    </select>
                    @if($appFilter)
                        <a href="{{ route('admin.configs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    @endif
                </form>
            </div>

            <!-- Configs List -->
            @if($groupedConfigs->isEmpty())
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-code text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No configurations found</p>
                    <p class="text-gray-400 text-sm mt-1">Create your first config to get started</p>
                    <a href="{{ route('admin.configs.create') }}" class="inline-block mt-4 text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-1"></i> Add Config
                    </a>
                </div>
            @else
                @foreach($groupedConfigs as $appIdentifier => $configs)
                    <div class="bg-white rounded-lg shadow mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                            <h2 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-cube mr-2 text-gray-500"></i> {{ $appIdentifier }}
                            </h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach($configs as $config)
                                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg {{ $config->is_active ? 'bg-blue-100' : 'bg-gray-100' }} flex items-center justify-center">
                                            @switch($config->content_type)
                                                @case('json')
                                                    <i class="fas fa-brackets-curly {{ $config->is_active ? 'text-blue-600' : 'text-gray-400' }}"></i>
                                                    @break
                                                @case('xml')
                                                    <i class="fas fa-code {{ $config->is_active ? 'text-blue-600' : 'text-gray-400' }}"></i>
                                                    @break
                                                @default
                                                    <i class="fas fa-file-alt {{ $config->is_active ? 'text-blue-600' : 'text-gray-400' }}"></i>
                                            @endswitch
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                {{ $config->config_key }}
                                                @if(!$config->is_active)
                                                    <span class="ml-2 px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded">Inactive</span>
                                                @endif
                                            </p>
                                            <p class="text-sm text-gray-500">{{ $config->filename }}</p>
                                            @if($config->description)
                                                <p class="text-xs text-gray-400 mt-1">{{ Str::limit($config->description, 60) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-6">
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-700">
                                                Version {{ $config->current_version }}
                                            </p>
                                            @if($config->versions->first())
                                                <p class="text-xs text-gray-500">
                                                    {{ $config->versions->first()->created_at->diffForHumans() }}
                                                </p>
                                            @else
                                                <p class="text-xs text-gray-400">No content yet</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.configs.edit', $config) }}"
                                               class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-md">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                            <form action="{{ route('admin.configs.destroy', $config) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Delete this config and all its versions? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-md">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Help Text -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i> API Access</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li><strong>Get all configs:</strong> <code class="bg-blue-100 px-1 rounded">GET /api/v1/config/{app_identifier}</code></li>
                    <li><strong>Get specific config:</strong> <code class="bg-blue-100 px-1 rounded">GET /api/v1/config/{app_identifier}/{config_key}</code></li>
                    <li>Include <code class="bg-blue-100 px-1 rounded">X-API-Key</code> header for authentication</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
