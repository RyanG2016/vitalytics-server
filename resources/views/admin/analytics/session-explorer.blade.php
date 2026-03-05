<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard', ['mode' => 'analytics']) }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Session Explorer
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $session->app_identifier }} &bull; {{ $session->platform ?? 'Unknown' }}
                        @if($session->anonymous_user_id)
                            &bull; {{ $session->anonymous_user_id }}
                        @endif
                    </p>
                </div>
            </div>
            @if($session->is_test)
                <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm font-medium">
                    <i class="fas fa-flask mr-1"></i> Test Data
                </span>
            @endif
        </div>
    </x-slot>

    @php
        // Pre-calculate category counts for filter chips
        $categoryCounts = [];
        foreach ($events as $event) {
            $cat = $event['category'] ?? 'other';
            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
        }
    @endphp

    <div class="py-6" x-data="sessionExplorer()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Session Info Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    <!-- Session ID -->
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Session ID</div>
                        <div class="text-sm font-mono text-gray-600 truncate" title="{{ $session->session_id }}">
                            {{ Str::limit($session->session_id, 12) }}
                        </div>
                    </div>

                    <!-- Device ID -->
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Device</div>
                        <div class="text-sm font-mono text-gray-600 truncate" title="{{ $session->device_id }}">
                            {{ Str::limit($session->device_id, 12) }}
                        </div>
                    </div>

                    <!-- Platform & Version -->
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Platform</div>
                        <div class="text-sm text-gray-800">
                            {{ $session->platform ?? 'Unknown' }}
                        </div>
                    </div>

                    <!-- App Version -->
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">App Version</div>
                        <div class="text-sm text-gray-800">
                            {{ $session->app_version ?? 'Unknown' }}
                        </div>
                    </div>

                    <!-- Started At -->
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Started</div>
                        <div class="text-sm text-gray-800">
                            {{ \Carbon\Carbon::parse($session->started_at)->format('M d, g:i:s A') }}
                        </div>
                    </div>

                    <!-- Duration -->
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Duration</div>
                        <div class="text-sm text-gray-800">
                            @if($session->duration_seconds)
                                @if($session->duration_seconds >= 3600)
                                    {{ floor($session->duration_seconds / 3600) }}h {{ floor(($session->duration_seconds % 3600) / 60) }}m
                                @elseif($session->duration_seconds >= 60)
                                    {{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}s
                                @else
                                    {{ $session->duration_seconds }}s
                                @endif
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Session Stats Row -->
                <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-cyan-600">{{ $session->event_count ?? count($events) }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Total Events</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ count($screenGroups ?? []) }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Screen Changes</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600">{{ $session->screens_viewed ?? 0 }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Screens Viewed</div>
                    </div>
                    <div class="text-center">
                        @php
                            $isActive = $session->last_activity_at &&
                                \Carbon\Carbon::parse($session->last_activity_at)->isAfter(now()->subMinutes(30)) &&
                                !$session->ended_at;
                        @endphp
                        <div class="text-3xl font-bold {{ $isActive ? 'text-green-600' : 'text-gray-400' }}">
                            @if($isActive)
                                <i class="fas fa-circle text-sm animate-pulse"></i>
                            @else
                                <i class="fas fa-check-circle"></i>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">
                            {{ $isActive ? 'Active Now' : 'Ended' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Toggle & Controls -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">User Journey</h3>
                            <p class="text-sm text-gray-500">Track user interactions through screens</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Export Button -->
                            <button @click="exportCSV()" class="px-3 py-1.5 bg-green-50 text-green-700 rounded-lg text-sm font-medium hover:bg-green-100 transition flex items-center gap-1">
                                <i class="fas fa-download"></i>
                                <span class="hidden sm:inline">Export CSV</span>
                            </button>

                            <!-- Expand/Collapse All (Details view only) -->
                            <button x-show="viewMode === 'details'" @click="toggleAll()" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition flex items-center gap-1">
                                <i class="fas" :class="allExpanded ? 'fa-compress-alt' : 'fa-expand-alt'"></i>
                                <span class="hidden sm:inline" x-text="allExpanded ? 'Collapse All' : 'Expand All'"></span>
                            </button>

                            <!-- View Mode Toggle -->
                            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                                <button @click="viewMode = 'flow'"
                                        :class="viewMode === 'flow' ? 'bg-white shadow-sm text-cyan-600' : 'text-gray-600 hover:text-gray-800'"
                                        class="px-3 py-1.5 rounded-md text-sm font-medium transition-all">
                                    <i class="fas fa-diagram-project mr-1"></i> Flow
                                </button>
                                <button @click="viewMode = 'details'"
                                        :class="viewMode === 'details' ? 'bg-white shadow-sm text-cyan-600' : 'text-gray-600 hover:text-gray-800'"
                                        class="px-3 py-1.5 rounded-md text-sm font-medium transition-all">
                                    <i class="fas fa-list-check mr-1"></i> Details
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Category Filter Chips -->
                    @if(count($events) > 0)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Filter:</span>
                            @php
                                $categoryChips = [
                                    'navigation' => ['icon' => 'fa-compass', 'color' => 'blue'],
                                    'interaction' => ['icon' => 'fa-hand-pointer', 'color' => 'purple'],
                                    'form' => ['icon' => 'fa-clipboard-list', 'color' => 'amber'],
                                    'feature' => ['icon' => 'fa-star', 'color' => 'green'],
                                    'error' => ['icon' => 'fa-exclamation-triangle', 'color' => 'red'],
                                    'session' => ['icon' => 'fa-clock', 'color' => 'gray'],
                                    'lifecycle' => ['icon' => 'fa-sync', 'color' => 'gray'],
                                ];
                            @endphp
                            @foreach($categoryChips as $category => $chip)
                                @if(isset($categoryCounts[$category]))
                                <button @click="toggleFilter('{{ $category }}')"
                                        :class="filters['{{ $category }}'] ? 'bg-{{ $chip['color'] }}-100 text-{{ $chip['color'] }}-800 border-{{ $chip['color'] }}-300' : 'bg-gray-100 text-gray-400 border-gray-200 line-through'"
                                        class="px-2.5 py-1 rounded-full text-xs font-medium border transition-all flex items-center gap-1">
                                    <i class="fas {{ $chip['icon'] }} text-[10px]"></i>
                                    {{ ucfirst($category) }}
                                    <span class="opacity-60">({{ $categoryCounts[$category] }})</span>
                                </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                @if(count($events) > 0)
                    <!-- Flow View (Horizontal Flow Diagram) -->
                    <div x-show="viewMode === 'flow'" x-cloak class="p-6"
                         x-data="{
                            expandedScreens: {},
                            scrollLeft() {
                                this.$refs.flowContainer.scrollBy({ left: -300, behavior: 'smooth' });
                            },
                            scrollRight() {
                                this.$refs.flowContainer.scrollBy({ left: 300, behavior: 'smooth' });
                            },
                            canScrollLeft: false,
                            canScrollRight: false,
                            updateScrollState() {
                                const el = this.$refs.flowContainer;
                                if (el) {
                                    this.canScrollLeft = el.scrollLeft > 0;
                                    this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 10);
                                }
                            },
                            toggleScreen(index) {
                                this.expandedScreens[index] = !this.expandedScreens[index];
                            },
                            isScreenExpanded(index) {
                                return this.expandedScreens[index] === true;
                            }
                         }"
                         x-init="$nextTick(() => updateScrollState())">
                        @if(count($screenGroups ?? []) > 0)
                            <div class="relative">
                                <!-- Prev/Next Navigation Buttons -->
                                <button @click="scrollLeft()"
                                        x-show="canScrollLeft"
                                        x-transition
                                        class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white/90 hover:bg-white shadow-lg rounded-full flex items-center justify-center text-gray-600 hover:text-cyan-600 transition-all border border-gray-200">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button @click="scrollRight()"
                                        x-show="canScrollRight"
                                        x-transition
                                        class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white/90 hover:bg-white shadow-lg rounded-full flex items-center justify-center text-gray-600 hover:text-cyan-600 transition-all border border-gray-200">
                                    <i class="fas fa-chevron-right"></i>
                                </button>

                                <div class="flow-container overflow-x-auto pb-4 px-6" x-ref="flowContainer" @scroll="updateScrollState()">
                                    <div class="flex items-start gap-0 min-w-max">
                                    @foreach($screenGroups as $groupIndex => $group)
                                        @php
                                            $screenEvents = $group['events'];
                                            // Count events by category for summary
                                            $eventSummary = [];
                                            foreach ($screenEvents as $evt) {
                                                $cat = $evt['category'] ?? 'other';
                                                $eventSummary[$cat] = ($eventSummary[$cat] ?? 0) + 1;
                                            }

                                            // Look for screen label
                                            $screenLabel = null;
                                            foreach ($screenEvents as $evt) {
                                                if (!empty($evt['properties']['screen_label'])) {
                                                    $screenLabel = $evt['properties']['screen_label'];
                                                    break;
                                                }
                                            }
                                            if (!$screenLabel && isset($labelMappings['screen'][$group['screen']])) {
                                                $screenLabel = $labelMappings['screen'][$group['screen']];
                                            }
                                            $screenDisplayName = $screenLabel ?: $group['screen'];
                                            $screenTooltip = $screenLabel ? "{$screenLabel} ({$group['screen']})" : $group['screen'];
                                        @endphp
                                        <!-- Screen Box -->
                                        <div class="flex items-center">
                                            <div class="flow-screen-box flex flex-col min-w-[200px] max-w-[260px]">
                                                <!-- Screen Card -->
                                                <div class="bg-gradient-to-br from-slate-50 to-slate-100 border-2 rounded-xl shadow-sm transition-all cursor-pointer"
                                                     :class="isScreenExpanded({{ $groupIndex }}) ? 'border-cyan-400 shadow-md' : 'border-slate-200 hover:border-cyan-300 hover:shadow-md'"
                                                     @click="toggleScreen({{ $groupIndex }})">
                                                    <!-- Screen Header -->
                                                    <div class="p-4 pb-3">
                                                        <div class="flex items-center gap-2 mb-2">
                                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                                                {{ $groupIndex + 1 }}
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="font-semibold text-gray-800 text-sm truncate" title="{{ $screenTooltip }}">
                                                                    {{ $screenDisplayName }}
                                                                </div>
                                                                <div class="text-xs text-gray-400">
                                                                    {{ $group['start_time'] }}
                                                                </div>
                                                            </div>
                                                            <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform"
                                                               :class="isScreenExpanded({{ $groupIndex }}) ? 'rotate-180' : ''"></i>
                                                        </div>

                                                        <!-- Summary badges (shown when collapsed) -->
                                                        <div x-show="!isScreenExpanded({{ $groupIndex }})" class="flex flex-wrap gap-1 mt-2">
                                                            @foreach($eventSummary as $cat => $count)
                                                                @php
                                                                    $badgeColors = [
                                                                        'navigation' => 'bg-blue-100 text-blue-700',
                                                                        'interaction' => 'bg-purple-100 text-purple-700',
                                                                        'form' => 'bg-amber-100 text-amber-700',
                                                                        'feature' => 'bg-green-100 text-green-700',
                                                                        'error' => 'bg-red-100 text-red-700',
                                                                        'session' => 'bg-gray-100 text-gray-700',
                                                                        'lifecycle' => 'bg-gray-100 text-gray-700',
                                                                    ];
                                                                    $badgeColor = $badgeColors[$cat] ?? 'bg-gray-100 text-gray-700';
                                                                @endphp
                                                                <span x-show="isVisible('{{ $cat }}')" class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $badgeColor }}">
                                                                    {{ $count }} {{ Str::limit($cat, 4, '') }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <!-- Events List (expanded) -->
                                                    <div x-show="isScreenExpanded({{ $groupIndex }})" x-collapse class="px-4 pb-3 border-t border-slate-200">
                                                        <div class="space-y-1.5 pt-3 max-h-[300px] overflow-y-auto">
                                                            @foreach($screenEvents as $eventIndex => $event)
                                                                @php
                                                                    $dotColors = [
                                                                        'navigation' => 'bg-blue-400',
                                                                        'interaction' => 'bg-purple-400',
                                                                        'form' => 'bg-amber-400',
                                                                        'feature' => 'bg-green-400',
                                                                        'error' => 'bg-red-400',
                                                                        'performance' => 'bg-orange-400',
                                                                        'lifecycle' => 'bg-gray-400',
                                                                        'session' => 'bg-gray-400',
                                                                    ];
                                                                    $dotColor = $dotColors[$event['category'] ?? 'other'] ?? 'bg-gray-400';
                                                                    $showElementAsName = ['button_clicked', 'click', 'interaction', 'form_submit', 'form_submitted', 'feature_used'];
                                                                    $elementValue = $event['element'] ?: ($event['properties']['form'] ?? null) ?: ($event['properties']['feature'] ?? null);
                                                                    $labelValue = $event['properties']['label'] ?? null;
                                                                    if (!$labelValue && $event['element'] && isset($labelMappings['element'][$event['element']])) {
                                                                        $labelValue = $labelMappings['element'][$event['element']];
                                                                    }
                                                                    if (!$labelValue && !empty($event['properties']['feature']) && isset($labelMappings['feature'][$event['properties']['feature']])) {
                                                                        $labelValue = $labelMappings['feature'][$event['properties']['feature']];
                                                                    }
                                                                    if (!$labelValue && !empty($event['properties']['form']) && isset($labelMappings['form'][$event['properties']['form']])) {
                                                                        $labelValue = $labelMappings['form'][$event['properties']['form']];
                                                                    }
                                                                    $eventTypeLabel = $labelMappings['event_type'][$event['name']] ?? null;
                                                                    $displayName = $labelValue ?: (($elementValue && in_array($event['name'], $showElementAsName)) ? $elementValue : ($eventTypeLabel ?: $event['name']));
                                                                    $tooltip = $event['name'] . ($elementValue ? ": {$elementValue}" : '');
                                                                @endphp
                                                                <div x-show="isVisible('{{ $event['category'] ?? 'other' }}')" class="flex items-center gap-2 text-xs group" @click.stop>
                                                                    <div class="w-2 h-2 rounded-full {{ $dotColor }} flex-shrink-0"></div>
                                                                    <span class="text-gray-700 truncate flex-1" title="{{ $tooltip }}">
                                                                        {{ Str::limit($displayName, 25) }}
                                                                    </span>
                                                                    @if($event['time_delta'] && $eventIndex > 0)
                                                                        <span class="text-gray-400 font-mono text-[10px]">{{ $event['time_delta'] }}</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <!-- Screen Stats Footer -->
                                                    <div class="px-4 py-2 border-t border-slate-200 flex items-center justify-between text-xs text-gray-500 bg-slate-50/50 rounded-b-xl">
                                                        <span>{{ count($screenEvents) }} events</span>
                                                        @if($group['duration'])
                                                            <span class="font-mono">{{ ltrim($group['duration'], '+') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Arrow Connector -->
                                            @if($groupIndex < count($screenGroups) - 1)
                                                <div class="flex flex-col items-center mx-2">
                                                    <div class="relative flex items-center">
                                                        <div class="w-12 h-0.5 bg-gradient-to-r from-slate-300 to-cyan-400"></div>
                                                        <div class="w-0 h-0 border-t-[6px] border-t-transparent border-b-[6px] border-b-transparent border-l-[8px] border-l-cyan-400"></div>
                                                    </div>
                                                    @if(isset($screenGroups[$groupIndex + 1]['time_since_previous']))
                                                        <div class="text-[10px] text-cyan-600 font-mono mt-1">
                                                            {{ $screenGroups[$groupIndex + 1]['time_since_previous'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                            <!-- Flow Legend -->
                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Click on a screen card to expand/collapse event details. Use arrow buttons to navigate.</span>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <i class="fas fa-diagram-project text-gray-300 text-5xl mb-4"></i>
                                <p class="text-gray-500">No screen data available for flow view</p>
                                <p class="text-sm text-gray-400 mt-1">Events need screen information to display the flow</p>
                                <button @click="viewMode = 'details'" class="mt-4 text-cyan-600 hover:text-cyan-700 text-sm font-medium">
                                    <i class="fas fa-list-check mr-1"></i> Switch to Details View
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Details View (Grouped by Screen) -->
                    <div x-show="viewMode === 'details'" x-cloak>
                        @if(count($screenGroups ?? []) > 0)
                            <div class="divide-y divide-gray-100">
                                @foreach($screenGroups as $groupIndex => $group)
                                    @php
                                        $detailsScreenLabel = null;
                                        foreach ($group['events'] as $evt) {
                                            if (!empty($evt['properties']['screen_label'])) {
                                                $detailsScreenLabel = $evt['properties']['screen_label'];
                                                break;
                                            }
                                        }
                                        if (!$detailsScreenLabel && isset($labelMappings['screen'][$group['screen']])) {
                                            $detailsScreenLabel = $labelMappings['screen'][$group['screen']];
                                        }
                                        $detailsScreenDisplayName = $detailsScreenLabel ?: $group['screen'];

                                        // Count visible events
                                        $eventCategoryCounts = [];
                                        foreach ($group['events'] as $evt) {
                                            $cat = $evt['category'] ?? 'other';
                                            $eventCategoryCounts[$cat] = ($eventCategoryCounts[$cat] ?? 0) + 1;
                                        }
                                    @endphp
                                    <div class="p-4" x-data="{ expanded: false }" @toggle-all-screens.window="expanded = $event.detail">
                                        <!-- Screen Header -->
                                        <div class="flex items-center justify-between cursor-pointer hover:bg-gray-50 -m-2 p-2 rounded-lg"
                                             @click="expanded = !expanded">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold">
                                                    {{ $groupIndex + 1 }}
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-800 flex items-center gap-2" title="{{ $detailsScreenLabel ? $group['screen'] : '' }}">
                                                        {{ $detailsScreenDisplayName }}
                                                        <span class="text-xs font-normal text-gray-400">
                                                            {{ $group['start_time'] }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        @foreach($eventCategoryCounts as $cat => $count)
                                                            @php
                                                                $pillColors = [
                                                                    'navigation' => 'bg-blue-100 text-blue-700',
                                                                    'interaction' => 'bg-purple-100 text-purple-700',
                                                                    'form' => 'bg-amber-100 text-amber-700',
                                                                    'feature' => 'bg-green-100 text-green-700',
                                                                    'error' => 'bg-red-100 text-red-700',
                                                                    'session' => 'bg-gray-100 text-gray-600',
                                                                    'lifecycle' => 'bg-gray-100 text-gray-600',
                                                                ];
                                                                $pillColor = $pillColors[$cat] ?? 'bg-gray-100 text-gray-600';
                                                            @endphp
                                                            <span x-show="isVisible('{{ $cat }}')" class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $pillColor }}">
                                                                {{ $count }}
                                                            </span>
                                                        @endforeach
                                                        @if($group['duration'])
                                                            <span class="text-xs text-gray-400 font-mono">{{ ltrim($group['duration'], '+') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs text-gray-500">{{ count($group['events']) }} events</span>
                                                <i class="fas fa-chevron-down text-gray-400 transition-transform"
                                                   :class="{ 'rotate-180': expanded }"></i>
                                            </div>
                                        </div>

                                        <!-- Events in this screen -->
                                        <div x-show="expanded" x-collapse class="mt-4 ml-6 border-l-2 border-cyan-200 pl-4 space-y-3">
                                            @foreach($group['events'] as $eventIndex => $event)
                                                @php
                                                    $categoryColors = [
                                                        'navigation' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                        'interaction' => 'bg-purple-100 text-purple-800 border-purple-200',
                                                        'form' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                        'feature' => 'bg-green-100 text-green-800 border-green-200',
                                                        'error' => 'bg-red-100 text-red-800 border-red-200',
                                                        'performance' => 'bg-orange-100 text-orange-800 border-orange-200',
                                                        'lifecycle' => 'bg-gray-100 text-gray-800 border-gray-200',
                                                        'session' => 'bg-gray-100 text-gray-800 border-gray-200',
                                                        'other' => 'bg-gray-100 text-gray-800 border-gray-200',
                                                    ];
                                                    $categoryColor = $categoryColors[$event['category'] ?? 'other'] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                                @endphp
                                                <div x-show="isVisible('{{ $event['category'] ?? 'other' }}')" class="flex items-start gap-3 group">
                                                    <!-- Timeline dot -->
                                                    <div class="relative">
                                                        <div class="w-3 h-3 rounded-full bg-cyan-400 border-2 border-white shadow-sm -ml-[22px]"></div>
                                                    </div>

                                                    <!-- Event content -->
                                                    <div class="flex-1 min-w-0 pb-2">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="font-medium text-gray-800">{{ $event['name'] }}</span>
                                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $categoryColor }}">
                                                                    {{ $event['category'] ?? 'other' }}
                                                                </span>
                                                            </div>
                                                            <div class="flex items-center gap-2 text-sm whitespace-nowrap">
                                                                @if($event['time_delta'] && $eventIndex > 0)
                                                                    <span class="text-cyan-600 font-mono text-xs">
                                                                        {{ $event['time_delta'] }}
                                                                    </span>
                                                                @endif
                                                                <span class="text-gray-400" title="{{ $event['timestamp_full'] }}">
                                                                    {{ $event['timestamp'] }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        @if($event['element'])
                                                            <div class="text-sm text-gray-500 mt-1">
                                                                <i class="fas fa-hand-pointer text-gray-400 mr-1"></i>
                                                                {{ $event['element'] }}
                                                            </div>
                                                        @endif

                                                        @if($event['properties'])
                                                            <details class="mt-2">
                                                                <summary class="text-xs text-cyan-600 cursor-pointer hover:text-cyan-700">
                                                                    <i class="fas fa-code mr-1"></i>
                                                                    Properties
                                                                </summary>
                                                                <div class="mt-1 p-2 bg-gray-50 rounded text-xs">
                                                                    @foreach($event['properties'] as $key => $value)
                                                                        <div class="flex gap-2">
                                                                            <span class="text-gray-500">{{ $key }}:</span>
                                                                            <span class="text-gray-800">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </details>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-12 text-center">
                                <i class="fas fa-route text-gray-300 text-5xl mb-4"></i>
                                <p class="text-gray-500">No screen data available</p>
                                <p class="text-sm text-gray-400 mt-1">Events don't have screen information yet</p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-12 text-center">
                        <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                        <p class="text-gray-500">No events recorded in this session</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script>
        // Session events data for export
        window.sessionEventsData = @json($events);
        window.sessionFileName = 'session-{{ Str::limit($session->session_id, 8, '') }}-events.csv';

        function sessionExplorer() {
            return {
                viewMode: 'flow',
                filters: {
                    navigation: true,
                    interaction: true,
                    form: true,
                    feature: true,
                    error: true,
                    performance: true,
                    lifecycle: true,
                    session: true,
                    other: true
                },
                allExpanded: false,
                toggleAll() {
                    this.allExpanded = !this.allExpanded;
                    this.$dispatch('toggle-all-screens', this.allExpanded);
                },
                isVisible(category) {
                    return this.filters[category] ?? this.filters['other'] ?? true;
                },
                toggleFilter(category) {
                    this.filters[category] = !this.filters[category];
                },
                exportCSV() {
                    const events = window.sessionEventsData;
                    const rows = [['Timestamp', 'Screen', 'Event Type', 'Category', 'Element', 'Properties', 'Duration (ms)']];

                    events.forEach(e => {
                        if (this.isVisible(e.category || 'other')) {
                            rows.push([
                                e.timestamp_full || '',
                                e.screen || '',
                                e.name || '',
                                e.category || '',
                                e.element || '',
                                e.properties ? JSON.stringify(e.properties) : '',
                                e.duration_ms || ''
                            ]);
                        }
                    });

                    const csv = rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\n');
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = window.sessionFileName;
                    a.click();
                    URL.revokeObjectURL(url);
                }
            };
        }
    </script>
</x-app-layout>
