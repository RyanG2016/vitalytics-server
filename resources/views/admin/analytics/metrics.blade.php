<x-app-layout>

<div class="max-w-7xl mx-auto">
    {{-- Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-4xl font-bold text-white flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-pie text-2xl text-white"></i>
                    </div>
                    Metrics Dashboard
                </h1>
                <p class="text-white/80 mt-2 text-sm sm:text-base">AI token usage, API calls, and custom metrics</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('dashboard', ['mode' => 'analytics']) }}" class="bg-white/20 backdrop-blur rounded-lg px-4 py-2 text-white text-sm hover:bg-white/30 transition flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Analytics</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('admin.analytics.metrics') }}" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-gray-400"></i>
                <select name="product" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All Products</option>
                    @foreach($products as $productId => $productInfo)
                        <option value="{{ $productId }}" {{ ($product ?? null) == $productId ? 'selected' : '' }}>{{ $productInfo['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <i class="fas fa-calendar text-gray-400"></i>
                <select name="days" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="1" {{ $days == 1 ? 'selected' : '' }}>Last 1 Day</option>
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="14" {{ $days == 14 ? 'selected' : '' }}>Last 14 Days</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 Days</option>
                </select>
            </div>

            @if(!empty($metricTypes))
            <div class="flex items-center gap-2">
                <i class="fas fa-tags text-gray-400"></i>
                <select name="metric" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    @foreach($metricTypes as $type)
                        <option value="{{ $type }}" {{ $metricName == $type ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-orange-200 bg-orange-50 cursor-pointer hover:bg-orange-100 transition">
                <input type="checkbox" name="show_test" value="1" onchange="this.form.submit()" {{ ($showTest ?? false) ? 'checked' : '' }} class="rounded border-orange-300 text-orange-500 focus:ring-orange-500">
                <span class="text-orange-700 text-sm font-medium"><i class="fas fa-flask mr-1"></i> Test Data</span>
            </label>
        </form>
    </div>

    {{-- Stats Overview Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- Total Requests --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-hashtag text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($totals['count']) }}</div>
                <div class="text-blue-100 text-sm mt-1">Total Requests</div>
            </div>
        </div>

        {{-- Total Tokens --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-coins text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">{{ number_format($totals['total_tokens']) }}</div>
                <div class="text-purple-100 text-sm mt-1">Total Tokens</div>
            </div>
        </div>

        {{-- Input/Output Tokens --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-cyan-500 to-teal-500 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-exchange-alt text-white"></i>
                </div>
                <div class="text-xl font-bold text-white">
                    <span class="text-cyan-200">In:</span> {{ number_format($totals['total_input_tokens']) }}
                </div>
                <div class="text-xl font-bold text-white">
                    <span class="text-teal-200">Out:</span> {{ number_format($totals['total_output_tokens']) }}
                </div>
            </div>
        </div>

        {{-- Total Cost --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 shadow-lg">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-dollar-sign text-white"></i>
                </div>
                <div class="text-3xl font-bold text-white">${{ number_format($totals['total_cost_cents'] / 100, 2) }}</div>
                <div class="text-green-100 text-sm mt-1">Estimated Cost</div>
            </div>
        </div>
    </div>

    {{-- Daily Usage Chart --}}
    @if($chartData->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-chart-bar text-orange-600 text-sm"></i>
                </div>
                Daily Token Usage
                <span class="text-gray-400 font-normal text-sm ml-2">(Last {{ $days }} Days)</span>
            </h2>
        </div>
        <div class="p-4">
            <div class="overflow-x-auto">
                <div class="flex items-end gap-2" style="min-width: 600px; height: 200px;">
                    @php
                        $maxTokens = max(1, $chartData->max('total_tokens'));
                    @endphp
                    @foreach($chartData as $date => $data)
                    <div class="flex-1 flex flex-col items-center group">
                        <div class="w-full flex flex-col-reverse rounded-t-sm overflow-hidden" style="height: 160px;">
                            @if($data['total_tokens'] > 0)
                            <div class="w-full transition-all group-hover:opacity-90" style="height: {{ ($data['total_tokens'] / $maxTokens) * 100 }}%;">
                                <div class="h-full bg-gradient-to-t from-orange-500 to-amber-400"
                                     style="height: {{ $data['total_tokens'] > 0 ? (($data['output_tokens'] / $data['total_tokens']) * 100) : 0 }}%;"
                                     title="Output: {{ number_format($data['output_tokens']) }}"></div>
                                <div class="bg-gradient-to-t from-purple-500 to-violet-400"
                                     style="height: {{ $data['total_tokens'] > 0 ? (($data['input_tokens'] / $data['total_tokens']) * 100) : 0 }}%;"
                                     title="Input: {{ number_format($data['input_tokens']) }}"></div>
                            </div>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 mt-2 whitespace-nowrap" style="font-size: 10px;">
                            {{ \Carbon\Carbon::parse($date)->format('M d') }}
                        </div>
                        <div class="hidden group-hover:block absolute bg-gray-900 text-white text-xs rounded px-2 py-1 -mt-16 z-10">
                            {{ number_format($data['total_tokens']) }} tokens<br>
                            ${{ number_format($data['cost_cents'] / 100, 2) }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center justify-center gap-6 mt-6 pt-4 border-t border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded bg-gradient-to-t from-purple-500 to-violet-400"></div>
                    <span class="text-sm text-gray-600">Input Tokens</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded bg-gradient-to-t from-orange-500 to-amber-400"></div>
                    <span class="text-sm text-gray-600">Output Tokens</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Breakdown by App --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-layer-group text-purple-600 text-sm"></i>
                    </div>
                    Usage by App
                </h2>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @forelse($appBreakdown as $app)
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ $apps[$app->app_identifier]['name'] ?? $app->app_identifier }}</p>
                            <p class="text-xs text-gray-500 font-mono mt-1">{{ $app->app_identifier }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-purple-600">{{ number_format($app->total_tokens) }} tokens</p>
                            <p class="text-sm text-gray-500">{{ number_format($app->total_count) }} requests</p>
                            <p class="text-xs text-green-600">${{ number_format($app->total_cost / 100, 2) }}</p>
                        </div>
                    </div>
                    {{-- Usage bar --}}
                    @php
                        $maxAppTokens = $appBreakdown->max('total_tokens') ?: 1;
                        $percentage = ($app->total_tokens / $maxAppTokens) * 100;
                    @endphp
                    <div class="mt-3 h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-purple-500 to-violet-400 rounded-full transition-all" style="width: {{ $percentage }}%;"></div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-pie text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No Data Yet</p>
                    <p class="text-gray-400 text-sm mt-1">Usage breakdown will appear here once metrics are tracked</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Metrics --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                        <i class="fas fa-clock text-cyan-600 text-sm"></i>
                    </div>
                    Recent Metrics
                </h2>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @forelse($recentMetrics as $metric)
                @php
                    $data = json_decode($metric->data, true);
                @endphp
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-900">{{ $apps[$metric->app_identifier]['name'] ?? $metric->app_identifier }}</p>
                                @if(isset($data['provider']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $data['provider'] }}
                                </span>
                                @endif
                                @if(isset($data['model']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ $data['model'] }}
                                </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                @if(isset($data['input_tokens']))
                                <span><i class="fas fa-arrow-right text-purple-400 mr-1"></i>{{ number_format($data['input_tokens']) }} in</span>
                                @endif
                                @if(isset($data['output_tokens']))
                                <span><i class="fas fa-arrow-left text-orange-400 mr-1"></i>{{ number_format($data['output_tokens']) }} out</span>
                                @endif
                                @if(isset($data['cost_cents']))
                                <span><i class="fas fa-dollar-sign text-green-400 mr-1"></i>${{ number_format($data['cost_cents'] / 100, 4) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($metric->metric_timestamp)->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No Recent Metrics</p>
                    <p class="text-gray-400 text-sm mt-1">Recent tracking data will appear here</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Empty State if no data --}}
    @if($totals['count'] == 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-20 h-20 bg-gradient-to-br from-orange-100 to-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-chart-pie text-4xl text-orange-500"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Metrics Data Yet</h3>
        <p class="text-gray-500 mb-6 max-w-md mx-auto">Start tracking metrics from your applications using the Laravel SDK to see usage data here.</p>
        <div class="bg-gray-50 rounded-lg p-4 max-w-lg mx-auto text-left">
            <p class="text-sm font-medium text-gray-700 mb-2">Example usage:</p>
            <pre class="text-xs text-gray-600 overflow-x-auto"><code>VitalyticsAnalytics::trackAiTokens(
    'anthropic',
    inputTokens: 1500,
    outputTokens: 500,
    options: [
        'model' => 'claude-3-sonnet',
        'cost_cents' => 2.5,
    ]
);</code></pre>
        </div>
    </div>
    @endif
</div>

</x-app-layout>
