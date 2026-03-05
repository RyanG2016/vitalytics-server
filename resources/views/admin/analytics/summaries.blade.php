<x-app-layout>
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('dashboard', ['mode' => 'analytics']) }}" class="text-cyan-600 hover:text-cyan-800 text-sm mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-robot mr-2 text-purple-600"></i> AI Summary Reports
                </h1>
                <p class="text-gray-500 text-sm mt-1">AI-generated daily health and analytics summaries (last 90 days)</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Product:</label>
                <select name="product" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Products</option>
                    @foreach($products as $slug => $info)
                        <option value="{{ $slug }}" {{ $product == $slug ? 'selected' : '' }}>{{ $info['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Type:</label>
                <select name="type" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="all" {{ $type == 'all' ? 'selected' : '' }}>All Reports</option>
                    <option value="health" {{ $type == 'health' ? 'selected' : '' }}>Health Reports</option>
                    <option value="analytics" {{ $type == 'analytics' ? 'selected' : '' }}>Analytics Reports</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <!-- Latest Health Report -->
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-heartbeat text-indigo-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Health Reports</h3>
                        <p class="text-sm text-gray-500">{{ $healthCount }} reports in last 90 days</p>
                    </div>
                </div>
                @if($latestHealth)
                <div class="text-right">
                    <p class="text-sm text-gray-400">Latest:</p>
                    <p class="text-sm font-medium text-gray-700">{{ $latestHealth->generated_at->format('M d, Y') }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Latest Analytics Report -->
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-cyan-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-cyan-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Analytics Reports</h3>
                        <p class="text-sm text-gray-500">{{ $analyticsCount }} reports in last 90 days</p>
                    </div>
                </div>
                @if($latestAnalytics)
                <div class="text-right">
                    <p class="text-sm text-gray-400">Latest:</p>
                    <p class="text-sm font-medium text-gray-700">{{ $latestAnalytics->generated_at->format('M d, Y') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Summaries List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-900">All Reports</h2>
        </div>

        @forelse($summaries as $summary)
        <div x-data="{ expanded: false }" class="border-b border-gray-100 last:border-b-0">
            <!-- Summary Header -->
            <div class="px-6 py-4 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition"
                 @click="expanded = !expanded">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $summary->type === 'health' ? 'bg-indigo-100' : 'bg-cyan-100' }}">
                        <i class="fas {{ $summary->type === 'health' ? 'fa-heartbeat text-indigo-600' : 'fa-chart-line text-cyan-600' }}"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900">{{ $summary->product->name ?? 'Unknown' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $summary->type === 'health' ? 'bg-indigo-100 text-indigo-700' : 'bg-cyan-100 text-cyan-700' }}">
                                {{ ucfirst($summary->type) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ $summary->generated_at->format('M d, Y') }} at {{ $summary->generated_at->format('g:i A') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-400">{{ $summary->generated_at->diffForHumans() }}</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" :class="expanded ? 'rotate-180' : ''"></i>
                </div>
            </div>

            <!-- Summary Content (Expandable) -->
            <div x-show="expanded"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <div class="prose prose-sm max-w-none">
                    <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm text-sm leading-relaxed">
                        {!! $summary->rendered_content !!}
                    </div>
                </div>

                @if($summary->summary_data)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-400 mb-2">Summary Stats:</p>
                    <div class="flex flex-wrap gap-3">
                        @if($summary->type === 'health')
                            @if(isset($summary->summary_data['total']))
                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-xs text-gray-600">
                                <i class="fas fa-chart-bar mr-1"></i> {{ number_format($summary->summary_data['total']) }} events
                            </span>
                            @endif
                            @if(isset($summary->summary_data['devices_affected']))
                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-xs text-gray-600">
                                <i class="fas fa-mobile-alt mr-1"></i> {{ $summary->summary_data['devices_affected'] }} devices affected
                            </span>
                            @endif
                        @else
                            @if(isset($summary->summary_data['total_events']))
                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-xs text-gray-600">
                                <i class="fas fa-chart-bar mr-1"></i> {{ number_format($summary->summary_data['total_events']) }} events
                            </span>
                            @endif
                            @if(isset($summary->summary_data['unique_sessions']))
                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-xs text-gray-600">
                                <i class="fas fa-users mr-1"></i> {{ number_format($summary->summary_data['unique_sessions']) }} sessions
                            </span>
                            @endif
                            @if(isset($summary->summary_data['unique_users']))
                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-xs text-gray-600">
                                <i class="fas fa-user mr-1"></i> {{ number_format($summary->summary_data['unique_users']) }} users
                            </span>
                            @endif
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-robot text-3xl text-gray-400"></i>
            </div>
            <p class="text-gray-500 font-medium">No AI Summaries Yet</p>
            <p class="text-gray-400 text-sm mt-1">Summaries will appear here once they are generated by the daily analysis jobs</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($summaries->hasPages())
    <div class="mt-6">
        {{ $summaries->withQueryString()->links() }}
    </div>
    @endif
</x-app-layout>
