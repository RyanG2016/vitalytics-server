<x-app-layout>
    {{-- Mode Toggle - Centered above content --}}
    <div class="flex justify-center mb-4">
        <div class="bg-gray-200 rounded-lg p-1 flex shadow-sm">
            <a href="{{ route('dashboard', ['mode' => 'health']) }}"
               class="px-5 py-2 rounded-md text-sm font-medium transition text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                <i class="fas fa-heartbeat mr-1"></i> Health
            </a>
            <a href="{{ route('dashboard', ['mode' => 'analytics']) }}"
               class="px-5 py-2 rounded-md text-sm font-medium transition text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                <i class="fas fa-chart-line mr-1"></i> Analytics
            </a>
            <a href="{{ route('admin.system.index') }}"
               class="px-5 py-2 rounded-md text-sm font-medium transition bg-white text-emerald-600 shadow">
                <i class="fas fa-server mr-1"></i> Vitalytics
            </a>
        </div>
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-server mr-2 text-emerald-500"></i> Vitalytics System Status
        </h1>
        <p class="text-gray-500 text-sm mt-1">System health, database metrics, and operational status</p>
    </div>

    {{-- OOM Risk Warning Banner --}}
    @if(!empty($memory) && (!($memory['swap_enabled'] ?? false) || ($memory['oom_risk'] ?? false)))
    <div class="mb-6 p-4 rounded-xl border-2 {{ ($memory['oom_risk'] ?? false) ? 'bg-red-50 border-red-300' : 'bg-yellow-50 border-yellow-300' }}">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 {{ ($memory['oom_risk'] ?? false) ? 'bg-red-100' : 'bg-yellow-100' }}">
                <i class="fas fa-exclamation-triangle {{ ($memory['oom_risk'] ?? false) ? 'text-red-600' : 'text-yellow-600' }}"></i>
            </div>
            <div>
                @if($memory['oom_risk'] ?? false)
                <h3 class="font-bold text-red-800">High OOM Risk Detected</h3>
                <p class="text-red-700 text-sm mt-1">
                    Memory usage is at {{ $memory['used_percent'] ?? 0 }}% with no swap configured.
                    The system may become unresponsive if memory pressure increases.
                </p>
                @else
                <h3 class="font-bold text-yellow-800">Swap Not Enabled</h3>
                <p class="text-yellow-700 text-sm mt-1">
                    No swap space is configured. This increases the risk of OOM (Out of Memory) events
                    if memory usage spikes. Consider adding swap space for stability.
                </p>
                @endif
                <div class="mt-2 text-xs {{ ($memory['oom_risk'] ?? false) ? 'text-red-600' : 'text-yellow-600' }}">
                    <code>sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile</code>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Overall System Health --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        {{-- System Health --}}
        @php
            $allHealthy = $system['disk_healthy'] && $system['memory_healthy'] && $system['cpu_healthy'] && $queue['queue_healthy'];
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center {{ $allHealthy ? 'bg-green-100' : 'bg-yellow-100' }}">
                        <i class="fas fa-heartbeat text-xl {{ $allHealthy ? 'text-green-600' : 'text-yellow-600' }}"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">System Health</p>
                        <p class="text-xl font-bold {{ $allHealthy ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $allHealthy ? 'Healthy' : 'Warning' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Uptime --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Uptime</p>
                    <p class="text-xl font-bold text-gray-900">{{ $system['uptime'] }}</p>
                </div>
            </div>
        </div>

        {{-- Database Size --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-database text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Database Size</p>
                    <p class="text-xl font-bold text-gray-900">{{ $database['total_size'] }}</p>
                </div>
            </div>
        </div>

        {{-- Queue Status --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center {{ $queue['queue_healthy'] ? 'bg-green-100' : 'bg-red-100' }}">
                    <i class="fas fa-tasks text-xl {{ $queue['queue_healthy'] ? 'text-green-600' : 'text-red-600' }}"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Queue Status</p>
                    <p class="text-xl font-bold {{ $queue['queue_healthy'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $queue['pending_jobs'] }} pending
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Overview Stats --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-chart-bar text-emerald-600"></i> Platform Overview
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-indigo-600">{{ number_format($overview['total_health_events']) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Health Events</div>
                    <div class="text-xs text-gray-400">{{ number_format($overview['events_today']) }} today</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-cyan-600">{{ number_format($overview['total_analytics_events']) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Analytics Events</div>
                    <div class="text-xs text-gray-400">{{ number_format($overview['analytics_today']) }} today</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600">{{ number_format($overview['total_sessions']) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total Sessions</div>
                    <div class="text-xs text-gray-400">{{ number_format($overview['sessions_today']) }} today</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">{{ $overview['total_products'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Products</div>
                    <div class="text-xs text-gray-400">{{ $overview['active_products'] }} active</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-orange-600">{{ $overview['total_apps'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Apps</div>
                    <div class="text-xs text-gray-400">{{ $overview['active_apps'] }} active</div>
                </div>
            </div>
        </div>
    </div>

    {{-- System Resources - Full Width --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-microchip text-blue-600"></i> System Resources
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Disk Usage --}}
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Disk Usage</span>
                        <span class="text-sm text-gray-500">{{ $system['disk_used'] }} / {{ $system['disk_total'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full {{ $system['disk_used_percent'] > 85 ? 'bg-red-500' : ($system['disk_used_percent'] > 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                             style="width: {{ min($system['disk_used_percent'], 100) }}%"></div>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ $system['disk_used_percent'] }}% used, {{ $system['disk_free'] }} free</div>
                </div>

                {{-- CPU Usage --}}
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">CPU Usage</span>
                        <span class="text-sm text-gray-500">{{ $system['cpu_cores'] }} cores</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full {{ $system['cpu_used_percent'] > 85 ? 'bg-red-500' : ($system['cpu_used_percent'] > 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                             style="width: {{ min($system['cpu_used_percent'], 100) }}%"></div>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ $system['cpu_used_percent'] }}% utilized</div>
                </div>
            </div>

            {{-- Memory & Swap Section --}}
            @if(!empty($memory))
            <div class="mt-6 pt-6 border-t border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-700">
                        <i class="fas fa-memory text-blue-500 mr-2"></i> Memory & Swap
                    </h3>
                    {{-- Memory Pressure Indicator --}}
                    @php
                        $pressureLevel = $memory['pressure_level'] ?? 'low';
                        $pressureColors = [
                            'low' => 'bg-green-100 text-green-700',
                            'medium' => 'bg-yellow-100 text-yellow-700',
                            'high' => 'bg-orange-100 text-orange-700',
                            'critical' => 'bg-red-100 text-red-700'
                        ];
                        $pressureColor = $pressureColors[$pressureLevel] ?? $pressureColors['low'];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pressureColor }}">
                        <i class="fas fa-tachometer-alt mr-1"></i>
                        Pressure: {{ ucfirst($pressureLevel) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- RAM Usage --}}
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">RAM Usage</span>
                            <span class="text-sm text-gray-500">{{ $memory['used'] ?? 'N/A' }} / {{ $memory['total'] ?? 'N/A' }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full {{ ($memory['used_percent'] ?? 0) > 85 ? 'bg-red-500' : (($memory['used_percent'] ?? 0) > 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min($memory['used_percent'] ?? 0, 100) }}%"></div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-400">{{ $memory['used_percent'] ?? 0 }}% used</span>
                            <span class="text-xs text-gray-400">{{ $memory['free'] ?? 'N/A' }} available</span>
                        </div>
                        @if(!empty($memory['buffers_cached']))
                        <div class="text-xs text-gray-400 mt-1">
                            Buffers/Cache: {{ $memory['buffers_cached'] }}
                        </div>
                        @endif
                    </div>

                    {{-- Swap Usage --}}
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">
                                Swap Usage
                                @if(!($memory['swap_enabled'] ?? false))
                                <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                    <i class="fas fa-times-circle mr-1"></i> Disabled
                                </span>
                                @endif
                            </span>
                            @if($memory['swap_enabled'] ?? false)
                            <span class="text-sm text-gray-500">{{ $memory['swap_used'] ?? '0' }} / {{ $memory['swap_total'] ?? '0' }}</span>
                            @endif
                        </div>
                        @if($memory['swap_enabled'] ?? false)
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full {{ ($memory['swap_used_percent'] ?? 0) > 80 ? 'bg-red-500' : (($memory['swap_used_percent'] ?? 0) > 50 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min($memory['swap_used_percent'] ?? 0, 100) }}%"></div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-400">{{ $memory['swap_used_percent'] ?? 0 }}% used</span>
                            <span class="text-xs text-gray-400">{{ $memory['swap_free'] ?? 'N/A' }} free</span>
                        </div>
                        @else
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full bg-gray-300" style="width: 0%"></div>
                        </div>
                        <div class="text-xs text-red-500 mt-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            No swap configured - OOM risk when memory is full
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- System Info Row --}}
            <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Load Average:</span>
                    <span class="font-medium text-gray-900 ml-2">{{ $system['load_average'] }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Hostname:</span>
                    <span class="font-medium text-gray-900 ml-2">{{ $system['hostname'] }}</span>
                </div>
                <div>
                    <span class="text-gray-500">PHP:</span>
                    <span class="font-medium text-gray-900 ml-2">{{ $system['php_version'] }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Laravel:</span>
                    <span class="font-medium text-gray-900 ml-2">{{ $system['laravel_version'] }}</span>
                </div>
            </div>

            {{-- Top Processes --}}
            <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Top CPU Processes --}}
                @if(count($topCpuProcesses) > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-microchip text-orange-500 mr-1"></i> Top CPU Processes
                    </h3>
                    <div class="space-y-2">
                        @foreach($topCpuProcesses as $process)
                        <div class="flex items-center justify-between text-xs bg-gray-50 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <span class="text-gray-400 font-mono">{{ $process['pid'] }}</span>
                                <span class="text-gray-700 truncate" title="{{ $process['command'] }}">{{ $process['command'] }}</span>
                            </div>
                            <span class="text-orange-600 font-medium ml-2">{{ $process['cpu'] }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Top Memory Processes --}}
                @if(count($topMemoryProcesses) > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-memory text-blue-500 mr-1"></i> Top Memory Processes
                    </h3>
                    <div class="space-y-2">
                        @foreach($topMemoryProcesses as $process)
                        <div class="flex items-center justify-between text-xs bg-gray-50 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <span class="text-gray-400 font-mono">{{ $process['pid'] }}</span>
                                <span class="text-gray-700 truncate" title="{{ $process['command'] }}">{{ $process['command'] }}</span>
                            </div>
                            <div class="flex items-center gap-2 ml-2">
                                <span class="text-blue-600 font-medium">{{ $process['memory'] }}%</span>
                                <span class="text-gray-400 text-[10px]">{{ $process['rss'] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- OOM Events Section --}}
            @if(!empty($oomEvents) && count($oomEvents) > 0)
            <div class="mt-6 pt-6 border-t border-gray-100">
                <h3 class="text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-skull-crossbones text-red-500 mr-1"></i> Recent OOM Killer Events
                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                        {{ count($oomEvents) }} events
                    </span>
                </h3>
                <div class="bg-red-50 rounded-lg p-3 border border-red-100">
                    <div class="space-y-2">
                        @foreach($oomEvents as $event)
                        <div class="text-xs bg-white rounded px-3 py-2 border border-red-100">
                            <div class="flex items-center gap-2">
                                @if(!empty($event['process']))
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-medium">
                                    {{ $event['process'] }}
                                </span>
                                @endif
                                @if(!empty($event['pid']))
                                <span class="text-gray-400 font-mono">PID: {{ $event['pid'] }}</span>
                                @endif
                                @if(!empty($event['timestamp']))
                                <span class="text-gray-400">@ {{ $event['timestamp'] }}s</span>
                                @endif
                            </div>
                            <div class="mt-1 text-gray-600 font-mono text-[10px] truncate" title="{{ $event['raw'] }}">
                                {{ $event['raw'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-3 text-xs text-red-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        OOM events indicate the kernel killed processes to free memory. Consider increasing RAM or swap space.
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Database Status - Full Width --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-white flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-database text-purple-600"></i> Database Status
            </h2>
            <div class="flex items-center gap-2">
                @if($database['connected'])
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                    Connected
                </span>
                @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                    Disconnected
                </span>
                @endif
            </div>
        </div>
        <div class="p-6">
            @if($database['connected'])
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                {{-- Database Name --}}
                <div class="text-center p-4 rounded-lg bg-purple-50">
                    <div class="text-lg font-bold text-purple-600">{{ $database['database_name'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Database</div>
                </div>
                {{-- MySQL Version --}}
                <div class="text-center p-4 rounded-lg bg-blue-50">
                    <div class="text-lg font-bold text-blue-600">{{ $database['mysql_version'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">MySQL Version</div>
                </div>
                {{-- Database Uptime --}}
                <div class="text-center p-4 rounded-lg bg-green-50">
                    <div class="text-lg font-bold text-green-600">{{ $database['uptime'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">DB Uptime</div>
                </div>
                {{-- Tables --}}
                <div class="text-center p-4 rounded-lg bg-indigo-50">
                    <div class="text-lg font-bold text-indigo-600">{{ $database['table_count'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Tables</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Connection Pool --}}
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Connection Pool</span>
                        <span class="text-sm text-gray-500">{{ $database['active_connections'] }} / {{ $database['max_connections'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full {{ $database['connection_usage_percent'] > 80 ? 'bg-red-500' : ($database['connection_usage_percent'] > 60 ? 'bg-yellow-500' : 'bg-green-500') }}"
                             style="width: {{ min($database['connection_usage_percent'], 100) }}%"></div>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ $database['connection_usage_percent'] }}% utilized</div>
                </div>

                {{-- Buffer Pool Hit Rate --}}
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Buffer Pool Hit Rate</span>
                        <span class="text-sm text-gray-500">{{ $database['buffer_pool_hit_rate'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full {{ $database['buffer_pool_hit_rate'] < 95 ? 'bg-yellow-500' : 'bg-green-500' }}"
                             style="width: {{ min($database['buffer_pool_hit_rate'], 100) }}%"></div>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ $database['buffer_pool_hit_rate'] < 95 ? 'Consider increasing buffer pool size' : 'Optimal performance' }}</div>
                </div>
            </div>

            {{-- Query Stats --}}
            <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="text-center">
                    <div class="text-xl font-bold text-gray-900">{{ $database['total_size'] }}</div>
                    <div class="text-xs text-gray-500">Total Size</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-gray-900">{{ $database['data_size'] }}</div>
                    <div class="text-xs text-gray-500">Data Size</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-gray-900">{{ $database['index_size'] }}</div>
                    <div class="text-xs text-gray-500">Index Size</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-gray-900">{{ $database['queries_per_second'] }}/s</div>
                    <div class="text-xs text-gray-500">Queries/sec</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold {{ $database['slow_queries'] !== '0' ? 'text-yellow-600' : 'text-gray-900' }}">{{ $database['slow_queries'] }}</div>
                    <div class="text-xs text-gray-500">Slow Queries</div>
                </div>
            </div>
            @else
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-database text-red-500 text-2xl"></i>
                </div>
                <p class="text-red-600 font-medium">Database Connection Failed</p>
                @if(isset($database['error']))
                <p class="text-gray-500 text-sm mt-2">{{ $database['error'] }}</p>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- Queue Status & Recent Feedback - Two Columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 items-start">
        {{-- Queue & Jobs --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ showFailedJobs: false, selectedJob: null }">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-orange-50 to-white">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-tasks text-orange-600"></i> Queue Status
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 rounded-lg bg-blue-50">
                        <div class="text-2xl font-bold text-blue-600">{{ $queue['pending_jobs'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">Pending Jobs</div>
                    </div>
                    @if($queue['failed_jobs'] > 0)
                    <button @click="showFailedJobs = true"
                            class="text-center p-4 rounded-lg bg-red-50 hover:bg-red-100 transition cursor-pointer border-2 border-transparent hover:border-red-200">
                        <div class="text-2xl font-bold text-red-600">{{ $queue['failed_jobs'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">Failed Jobs <i class="fas fa-external-link-alt ml-1"></i></div>
                    </button>
                    @else
                    <div class="text-center p-4 rounded-lg bg-green-50">
                        <div class="text-2xl font-bold text-green-600">{{ $queue['failed_jobs'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">Failed Jobs</div>
                    </div>
                    @endif
                    <div class="text-center p-4 rounded-lg bg-yellow-50">
                        <div class="text-2xl font-bold text-yellow-600">{{ $queue['failed_today'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">Failed Today</div>
                    </div>
                </div>

                @if($queue['oldest_job_age'])
                <div class="p-3 rounded-lg bg-yellow-50 border border-yellow-100">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-clock mr-2"></i>
                        Oldest pending job: <strong>{{ $queue['oldest_job_age'] }}</strong> old
                    </p>
                </div>
                @else
                <div class="p-3 rounded-lg bg-green-50 border border-green-100">
                    <p class="text-sm text-green-800">
                        <i class="fas fa-check-circle mr-2"></i>
                        Queue is empty - all jobs processed
                    </p>
                </div>
                @endif
            </div>

            {{-- Failed Jobs Modal --}}
            <div x-show="showFailedJobs" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="showFailedJobs = false; selectedJob = null"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[80vh] overflow-hidden"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.away="showFailedJobs = false; selectedJob = null">
                        <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-red-50">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                Failed Jobs ({{ $queue['failed_jobs'] }})
                            </h3>
                            <button @click="showFailedJobs = false; selectedJob = null" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="overflow-y-auto max-h-[60vh]">
                            @if(count($failedJobs) > 0)
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Job</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Queue</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Failed At</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exception</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($failedJobs as $job)
                                    <tr class="hover:bg-gray-50 cursor-pointer" @click="selectedJob = selectedJob === {{ $job['id'] }} ? null : {{ $job['id'] }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $job['job_name'] }}</div>
                                            <div class="text-xs text-gray-400 font-mono">{{ \Illuminate\Support\Str::limit($job['uuid'], 8) }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $job['queue'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ $job['failed_at']->diffForHumans() }}
                                            <div class="text-xs text-gray-400">{{ $job['failed_at']->format('M j, g:i A') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-red-600 max-w-xs truncate" title="{{ $job['exception'] }}">
                                            {{ $job['exception'] }}
                                        </td>
                                    </tr>
                                    <tr x-show="selectedJob === {{ $job['id'] }}" x-cloak class="bg-red-50">
                                        <td colspan="4" class="px-4 py-3">
                                            <div class="text-xs font-medium text-gray-700 mb-2">Full Exception:</div>
                                            <pre class="text-xs text-red-700 bg-red-100 p-3 rounded overflow-x-auto max-h-48 whitespace-pre-wrap">{{ $job['full_exception'] }}</pre>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <div class="p-8 text-center text-gray-500">
                                <i class="fas fa-check-circle text-4xl text-green-400 mb-3"></i>
                                <p>No failed jobs</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Feedback --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-white flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-comment-dots text-cyan-600"></i> Recent Feedback
                </h2>
                <a href="{{ route('admin.feedback.index') }}" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentFeedback as $feedback)
                <div class="p-3 hover:bg-gray-50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    @if($feedback['type'] === 'bug') bg-red-100 text-red-700
                                    @elseif($feedback['type'] === 'feature') bg-purple-100 text-purple-700
                                    @elseif($feedback['type'] === 'enhancement') bg-blue-100 text-blue-700
                                    @else bg-gray-100 text-gray-700
                                    @endif">
                                    {{ ucfirst($feedback['type']) }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    @if($feedback['status'] === 'new') bg-yellow-100 text-yellow-700
                                    @elseif($feedback['status'] === 'in_progress') bg-blue-100 text-blue-700
                                    @elseif($feedback['status'] === 'completed') bg-green-100 text-green-700
                                    @elseif($feedback['status'] === 'declined') bg-gray-100 text-gray-700
                                    @else bg-gray-100 text-gray-700
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $feedback['status'])) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 mb-1 line-clamp-2">{{ $feedback['message'] }}</p>
                            <div class="text-xs text-gray-400">
                                {{ $feedback['user_name'] }} &bull; {{ \Carbon\Carbon::parse($feedback['created_at'])->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                    <p class="text-sm">No feedback yet</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Software Versions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-white">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-code text-purple-600"></i> Software Stack
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fab fa-php text-purple-600"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">PHP</div>
                        <div class="font-medium text-gray-900">{{ $system['php_version'] }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fab fa-laravel text-red-600"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Laravel</div>
                        <div class="font-medium text-gray-900">{{ $system['laravel_version'] }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-database text-blue-600"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">MySQL</div>
                        <div class="font-medium text-gray-900">{{ $database['mysql_version'] }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-globe text-green-600"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Timezone</div>
                        <div class="font-medium text-gray-900">{{ $system['timezone'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Database Tables --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-white">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-table text-indigo-600"></i> Database Tables (Top 15 by size)
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rows</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Data Size</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Index Size</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Size</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($tables as $table)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                            <i class="fas fa-table text-gray-400 mr-2"></i>{{ $table['name'] }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500 text-right">{{ $table['rows'] }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500 text-right">{{ $table['data_size'] }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500 text-right">{{ $table['index_size'] }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900 text-right">{{ $table['size'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 text-sm text-gray-500">
            Database: <strong>{{ $database['database_name'] }}</strong> |
            Tables: <strong>{{ $database['table_count'] }}</strong> |
            Total Size: <strong>{{ $database['total_size'] }}</strong> |
            Active Connections: <strong>{{ $database['active_connections'] }}</strong>
        </div>
    </div>
</x-app-layout>
