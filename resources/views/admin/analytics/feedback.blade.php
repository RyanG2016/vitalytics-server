<x-app-layout>

<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-4xl font-bold text-white flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                        <i class="fas fa-comments text-2xl text-white"></i>
                    </div>
                    User Feedback
                </h1>
                <p class="text-white/80 mt-2 text-sm sm:text-base">Feedback submitted from your apps via SDK</p>
            </div>
            <a href="{{ route('dashboard', ['mode' => 'analytics']) }}" class="bg-white/20 backdrop-blur hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['total']) }}</div>
            <div class="text-sm text-gray-500">Total Feedback</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-orange-200 p-4">
            <div class="text-3xl font-bold text-orange-600">{{ number_format($stats['unread']) }}</div>
            <div class="text-sm text-gray-500">Unread</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-3xl font-bold text-blue-600">{{ number_format($stats['today']) }}</div>
            <div class="text-sm text-gray-500">Today</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-3xl font-bold text-purple-600">{{ number_format($stats['thisWeek']) }}</div>
            <div class="text-sm text-gray-500">This Week</div>
        </div>
        @if($stats['avgRating'])
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-3xl font-bold text-yellow-600 flex items-center gap-1">
                {{ $stats['avgRating'] }}
                <i class="fas fa-star text-lg"></i>
            </div>
            <div class="text-sm text-gray-500">Avg Rating</div>
        </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <form id="filters-form" method="GET" action="{{ route('admin.product-feedback.index') }}" class="flex flex-wrap items-center gap-4 flex-1">
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-gray-400"></i>
                    <select name="product" onchange="document.querySelector('select[name=app]').value=''; this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">All Products</option>
                        @foreach($products as $slug => $name)
                            <option value="{{ $slug }}" {{ $filters['product'] == $slug ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <select name="app" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-blue-200 rounded-lg bg-blue-50 text-gray-700 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Sub-Products</option>
                    @php $currentProductName = ''; @endphp
                    @foreach($apps as $appItem)
                        @if($currentProductName !== $appItem['product_name'])
                            @if($currentProductName !== '')</optgroup>@endif
                            <optgroup label="{{ $appItem['product_name'] }}">
                            @php $currentProductName = $appItem['product_name']; @endphp
                        @endif
                        <option value="{{ $appItem['identifier'] }}" {{ $filters['app'] == $appItem['identifier'] ? 'selected' : '' }}>
                            {{ $appItem['name'] }}
                        </option>
                    @endforeach
                    @if($currentProductName !== '')</optgroup>@endif
                </select>

                <select name="category" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ $filters['category'] == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="rating" onchange="this.form.submit()" class="pl-3 pr-8 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">Any Rating</option>
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" {{ $filters['rating'] == $i ? 'selected' : '' }}>{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>

                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-orange-200 bg-orange-50 cursor-pointer hover:bg-orange-100 transition">
                    <input type="checkbox" name="unread_only" value="1" onchange="this.form.submit()" {{ $filters['unread_only'] ? 'checked' : '' }} class="rounded border-orange-300 text-orange-500 focus:ring-orange-500">
                    <span class="text-orange-700 text-sm font-medium">Unread Only</span>
                </label>

                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 cursor-pointer hover:bg-gray-100 transition">
                    <input type="checkbox" name="show_test" value="1" onchange="this.form.submit()" {{ $filters['show_test'] ? 'checked' : '' }} class="rounded border-gray-300 text-gray-500 focus:ring-gray-500">
                    <span class="text-gray-600 text-sm font-medium"><i class="fas fa-flask mr-1"></i> Test Data</span>
                </label>

                @if($filters['product'] || $filters['app'] || $filters['category'] || $filters['rating'] || $filters['unread_only'])
                    <a href="{{ route('admin.product-feedback.index', ['show_test' => $filters['show_test']]) }}" class="text-sm text-gray-500 hover:text-orange-600 flex items-center gap-1">
                        <i class="fas fa-times"></i> Clear filters
                    </a>
                @endif
            </form>

            @if($stats['unread'] > 0)
            <form method="POST" action="{{ route('admin.product-feedback.mark-all-read') }}" class="ml-auto">
                @csrf
                <input type="hidden" name="product" value="{{ $filters['product'] }}">
                <input type="hidden" name="app" value="{{ $filters['app'] }}">
                <input type="hidden" name="category" value="{{ $filters['category'] }}">
                <input type="hidden" name="rating" value="{{ $filters['rating'] }}">
                <input type="hidden" name="show_test" value="{{ $filters['show_test'] ? '1' : '0' }}">
                <button type="submit" onclick="return confirm('Mark all {{ $stats['unread'] }} unread feedback as read?')" class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <i class="fas fa-check-double"></i>
                    Mark All Read ({{ $stats['unread'] }})
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Feedback List --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($feedback as $item)
            <div class="p-5 hover:bg-gray-50 transition {{ !$item->is_read ? 'bg-orange-50/50' : '' }}" x-data="{ expanded: false }">
                <div class="flex items-start gap-4">
                    {{-- Status indicator --}}
                    <div class="flex-shrink-0 mt-1">
                        @if(!$item->is_read)
                            <div class="w-3 h-3 rounded-full bg-orange-500" title="Unread"></div>
                        @else
                            <div class="w-3 h-3 rounded-full bg-gray-300" title="Read"></div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            {{-- Category badge --}}
                            @php
                                $categoryColors = [
                                    'general' => 'bg-gray-100 text-gray-700',
                                    'bug' => 'bg-red-100 text-red-700',
                                    'feature-request' => 'bg-purple-100 text-purple-700',
                                    'praise' => 'bg-green-100 text-green-700',
                                ];
                                $categoryIcons = [
                                    'general' => 'fa-comment',
                                    'bug' => 'fa-bug',
                                    'feature-request' => 'fa-lightbulb',
                                    'praise' => 'fa-heart',
                                ];
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $categoryColors[$item->category] ?? 'bg-gray-100 text-gray-700' }}">
                                <i class="fas {{ $categoryIcons[$item->category] ?? 'fa-comment' }}"></i>
                                {{ $categories[$item->category] ?? ucfirst($item->category) }}
                            </span>

                            {{-- Rating --}}
                            @if($item->rating)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $item->rating ? 'text-yellow-500' : 'text-gray-300' }}" style="font-size: 10px;"></i>
                                @endfor
                            </span>
                            @endif

                            {{-- Product / App Name --}}
                            @if($item->app && $item->app->product)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                <i class="fas fa-cube"></i>
                                {{ $item->app->product->name }}
                                <span class="text-blue-500">/ {{ $item->app->name }}</span>
                            </span>
                            @else
                            <span class="text-xs text-gray-500 font-mono">{{ $item->app_identifier }}</span>
                            @endif

                            {{-- Platform --}}
                            @if($item->platform)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs text-gray-500 bg-gray-100">
                                @php
                                    $platformIcons = [
                                        'ios' => 'fa-apple',
                                        'android' => 'fa-android',
                                        'chrome-extension' => 'fa-chrome',
                                        'chrome' => 'fa-chrome',
                                        'windows' => 'fa-windows',
                                        'macos' => 'fa-apple',
                                        'web' => 'fa-globe',
                                        'Laravel' => 'fa-server',
                                    ];
                                    $platformIcon = $platformIcons[strtolower($item->platform)] ?? $platformIcons[$item->platform] ?? 'fa-desktop';
                                @endphp
                                <i class="fab {{ $platformIcon }}"></i>
                                {{ $item->platform }}
                            </span>
                            @endif

                            {{-- Test badge --}}
                            @if($item->is_test)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">
                                <i class="fas fa-flask mr-1"></i> Test
                            </span>
                            @endif
                        </div>

                        {{-- Message --}}
                        <p class="text-gray-900 whitespace-pre-wrap">{{ $item->message }}</p>

                        {{-- Meta info --}}
                        <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-gray-500">
                            <span title="{{ $item->created_at }}">
                                <i class="far fa-clock mr-1"></i>
                                {{ $item->created_at->diffForHumans() }}
                            </span>

                            @if($item->email)
                            <a href="mailto:{{ $item->email }}" class="text-blue-600 hover:underline">
                                <i class="far fa-envelope mr-1"></i>
                                {{ $item->email }}
                            </a>
                            @endif

                            @if($item->screen)
                            <span>
                                <i class="fas fa-desktop mr-1"></i>
                                {{ $item->screen }}
                            </span>
                            @endif

                            @if($item->country)
                            <span>
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ $item->city ? $item->city . ', ' : '' }}{{ $item->country }}
                            </span>
                            @endif

                            @if($item->app_version)
                            <span>
                                <i class="fas fa-code-branch mr-1"></i>
                                v{{ $item->app_version }}
                            </span>
                            @endif
                        </div>

                        {{-- Expandable metadata --}}
                        @if($item->metadata || $item->device_id || $item->session_id)
                        <button @click="expanded = !expanded" class="mt-2 text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1">
                            <i class="fas fa-chevron-down transition-transform" :class="expanded ? 'rotate-180' : ''"></i>
                            <span x-text="expanded ? 'Hide details' : 'Show details'"></span>
                        </button>
                        <div x-show="expanded" x-collapse class="mt-2 p-3 bg-gray-50 rounded-lg text-xs font-mono text-gray-600">
                            @if($item->device_id)
                            <div><strong>Device ID:</strong> {{ $item->device_id }}</div>
                            @endif
                            @if($item->session_id)
                            <div><strong>Session ID:</strong> {{ $item->session_id }}</div>
                            @endif
                            @if($item->user_id)
                            <div><strong>User ID:</strong> {{ $item->user_id }}</div>
                            @endif
                            @if($item->metadata)
                            <div class="mt-2"><strong>Metadata:</strong></div>
                            <pre class="mt-1 overflow-x-auto">{{ json_encode($item->metadata, JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex-shrink-0 flex items-center gap-2">
                        @if(!$item->is_read)
                        <form method="POST" action="{{ route('admin.product-feedback.mark-read', $item) }}">
                            @csrf
                            <button type="submit" class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition" title="Mark as read">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('admin.product-feedback.mark-unread', $item) }}">
                            @csrf
                            <button type="submit" class="p-2 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition" title="Mark as unread">
                                <i class="fas fa-envelope"></i>
                            </button>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('admin.product-feedback.destroy', $item) }}" onsubmit="return confirm('Delete this feedback?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-comments text-3xl text-gray-400"></i>
                </div>
                <p class="text-gray-500 font-medium">No Feedback Yet</p>
                <p class="text-gray-400 text-sm mt-1">Feedback from users will appear here once submitted via SDK</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($feedback->hasPages())
        <div class="p-4 border-t border-gray-100">
            {{ $feedback->links() }}
        </div>
        @endif
    </div>
</div>

</x-app-layout>
