<x-app-layout>

<div>
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="javascript:history.back()" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-bug mr-2"></i> Event Details
            </h1>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard', ['show_test' => $showTest ?? false]) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-semibold">
                <i class="fas fa-chart-line mr-2"></i> Dashboard
            </a>
        </div>
    </div>

    @php
    $levelColors = [
        'crash' => 'bg-red-100 text-red-800 border-red-500',
        'error' => 'bg-orange-100 text-orange-800 border-orange-500',
        'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-500',
        'networkError' => 'bg-purple-100 text-purple-800 border-purple-500',
        'info' => 'bg-blue-100 text-blue-800 border-blue-500',
        'heartbeat' => 'bg-green-100 text-green-800 border-green-500',
    ];
    $levelIcons = [
        'crash' => 'fa-bomb',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'networkError' => 'fa-wifi',
        'info' => 'fa-info-circle',
        'heartbeat' => 'fa-heartbeat',
    ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Event Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Message Card --}}
            <div class="bg-white rounded-lg shadow overflow-hidden border-l-4 {{ $levelColors[$event->level] ?? 'border-gray-500' }}">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $levelColors[$event->level] ?? 'bg-gray-100 text-gray-800' }}">
                            <i class="fas {{ $levelIcons[$event->level] ?? 'fa-circle' }} mr-2"></i>
                            {{ ucfirst($event->level) }}
                        </span>
                        <span class="text-sm text-gray-500">
                            {{ $apps[$event->app_identifier]['name'] ?? $event->app_identifier }}
                        </span>
                        @if($event->dismissed_at)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                            <i class="fas fa-check-circle mr-2"></i> Dismissed
                        </span>
                        @endif
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Message</h2>
                    <p class="text-gray-700 whitespace-pre-wrap break-words">{{ $event->message }}</p>
                </div>
            </div>

            {{-- Dismissed Info --}}
            @if($event->dismissed_at)
            <div class="bg-gray-50 rounded-lg shadow border border-gray-200">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-600"></i> Dismissed
                    </h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-500">Dismissed At</dt>
                            <dd class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($event->dismissed_at, 'UTC')->setTimezone(config('app.timezone'))->format('M d, Y g:i:s A') }}</dd>
                        </div>
                        @if($event->dismissed_note)
                        <div>
                            <dt class="text-sm text-gray-500">Resolution Note</dt>
                            <dd class="text-sm text-gray-900 whitespace-pre-wrap">{{ $event->dismissed_note }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
            @endif

            {{-- Stack Trace --}}
            @if($event->stack_trace)
            <div class="bg-white rounded-lg shadow" x-data="{ copied: false }">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-layer-group mr-2"></i> Stack Trace
                        </h2>
                        <button @click="navigator.clipboard.writeText($refs.stackTrace.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition"
                                :class="copied ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            <i class="fas mr-2" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                    <pre x-ref="stackTrace" class="bg-gray-900 text-gray-100 p-4 rounded-lg text-xs font-mono whitespace-pre-wrap break-all overflow-x-auto max-h-96 overflow-y-auto">{{ is_string($event->stack_trace) ? json_encode(json_decode($event->stack_trace), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : json_encode($event->stack_trace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
            @endif

            {{-- Metadata --}}
            @if($event->metadata)
            <div class="bg-white rounded-lg shadow" x-data="{ copied: false }">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-tags mr-2"></i> Metadata
                        </h2>
                        <button @click="navigator.clipboard.writeText($refs.metadata.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition"
                                :class="copied ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            <i class="fas mr-2" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                    <pre x-ref="metadata" class="bg-gray-100 text-gray-800 p-4 rounded-lg text-xs font-mono whitespace-pre-wrap break-all overflow-x-auto">{{ is_string($event->metadata) ? json_encode(json_decode($event->metadata), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Event Details --}}
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle mr-2"></i> Event Info
                    </h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-500">Event ID</dt>
                            <dd class="text-sm font-mono text-gray-900">{{ $event->event_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Batch ID</dt>
                            <dd class="text-sm font-mono text-gray-900">{{ $event->batch_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Environment</dt>
                            <dd class="text-sm text-gray-900">{{ ucfirst($event->environment) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Event Timestamp</dt>
                            <dd class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($event->event_timestamp, 'UTC')->setTimezone(config('app.timezone'))->format('M d, Y g:i:s A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Received At</dt>
                            <dd class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($event->received_at, 'UTC')->setTimezone(config('app.timezone'))->format('M d, Y g:i:s A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">User ID</dt>
                            <dd class="text-sm font-mono text-gray-900">{{ $event->user_id ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Device Details --}}
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-mobile-alt mr-2"></i> Device Info
                    </h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-500">Device ID</dt>
                            <dd class="text-sm font-mono text-gray-900 break-all">{{ $event->device_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Device Model</dt>
                            <dd class="text-sm text-gray-900">{{ $event->device_model ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Platform</dt>
                            <dd class="text-sm text-gray-900">{{ $event->platform }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">OS Version</dt>
                            <dd class="text-sm text-gray-900">{{ $event->os_version ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">App Version</dt>
                            <dd class="text-sm text-gray-900">{{ $event->app_version ?? '-' }} @if($event->build_number)({{ $event->build_number }})@endif</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Location --}}
            @if($event->city || $event->region || $event->country)
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-map-marker-alt mr-2"></i> Location
                    </h2>
                    <dl class="space-y-3">
                        @if($event->city)
                        <div>
                            <dt class="text-sm text-gray-500">City</dt>
                            <dd class="text-sm text-gray-900">{{ $event->city }}</dd>
                        </div>
                        @endif
                        @if($event->region)
                        <div>
                            <dt class="text-sm text-gray-500">Region</dt>
                            <dd class="text-sm text-gray-900">{{ $event->region }}</dd>
                        </div>
                        @endif
                        @if($event->country)
                        <div>
                            <dt class="text-sm text-gray-500">Country</dt>
                            <dd class="text-sm text-gray-900">{{ $event->country }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

</x-app-layout>
