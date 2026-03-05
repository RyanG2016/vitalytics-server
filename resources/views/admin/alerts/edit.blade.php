<x-app-layout>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.alerts.index') }}" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Alerts
                </a>
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas {{ $product->icon ?? 'fa-cube' }} mr-2"></i> {{ $product->name }} - Alert Settings
                </h1>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Alert Settings --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-sliders-h mr-2"></i> Alert Settings
            </h2>

            <form action="{{ route('admin.alerts.update', $product) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Teams Configuration --}}
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fab fa-microsoft text-blue-500 mr-1"></i> Microsoft Teams
                    </h3>
                    
                    <div class="mb-3">
                        <label class="block text-sm text-gray-600 mb-1">Webhook URL</label>
                        <input type="url" name="teams_webhook_url" 
                               value="{{ old('teams_webhook_url', $settings->teams_webhook_url) }}"
                               placeholder="https://outlook.office.com/webhook/..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('teams_webhook_url')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="hidden" name="teams_enabled" value="0">
                            <input type="checkbox" name="teams_enabled" value="1" 
                                   {{ old('teams_enabled', $settings->teams_enabled) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600">Enable Teams alerts</span>
                        </label>
                        
                        @if($settings->teams_webhook_url)
                            <button type="button" 
                                    onclick="document.getElementById('test-webhook-form').submit()"
                                    class="text-sm text-blue-600 hover:text-blue-800">
                                <i class="fas fa-paper-plane mr-1"></i> Test Webhook
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Email Configuration --}}
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-envelope text-gray-500 mr-1"></i> Email Alerts
                    </h3>

                    <label class="flex items-center">
                        <input type="hidden" name="email_enabled" value="0">
                        <input type="checkbox" name="email_enabled" value="1"
                               {{ old('email_enabled', $settings->email_enabled) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Enable email alerts</span>
                    </label>
                </div>

                {{-- Push Notification Configuration --}}
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-mobile-alt text-green-500 mr-1"></i> Push Notifications
                    </h3>

                    <label class="flex items-center">
                        <input type="hidden" name="push_enabled" value="0">
                        <input type="checkbox" name="push_enabled" value="1"
                               {{ old('push_enabled', $settings->push_enabled) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-600">Enable push notifications (iOS/Android)</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-2 ml-6">Sends to subscribers with registered mobile devices</p>
                </div>

                {{-- General Settings --}}
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-cog text-gray-500 mr-1"></i> General Settings
                    </h3>

                    <label class="flex items-center">
                        <input type="hidden" name="alert_on_test_data" value="0">
                        <input type="checkbox" name="alert_on_test_data" value="1"
                               {{ old('alert_on_test_data', $settings->alert_on_test_data) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Include test data in alerts</span>
                    </label>
                </div>

                {{-- Heartbeat Monitoring --}}
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-heartbeat text-pink-500 mr-1"></i> Heartbeat Monitoring
                    </h3>
                    
                    <label class="flex items-center mb-3">
                        <input type="hidden" name="heartbeat_enabled" value="0">
                        <input type="checkbox" name="heartbeat_enabled" value="1" 
                               {{ old('heartbeat_enabled', $settings->heartbeat_enabled) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        <span class="ml-2 text-sm text-gray-600">Enable heartbeat monitoring</span>
                    </label>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Timeout (minutes)</label>
                        <input type="number" name="heartbeat_timeout_minutes" 
                               value="{{ old('heartbeat_timeout_minutes', $settings->heartbeat_timeout_minutes) }}"
                               min="5" max="1440"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <p class="text-xs text-gray-500 mt-1">Alert if no heartbeat received for this duration per device</p>
                    </div>
                </div>

                {{-- AI Analysis (Experimental) --}}
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-robot text-purple-500 mr-1"></i> AI Daily Analysis
                        <span class="ml-2 px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">Experimental</span>
                    </h3>
                    
                    <label class="flex items-center mb-3">
                        <input type="hidden" name="ai_analysis_enabled" value="0">
                        <input type="checkbox" name="ai_analysis_enabled" value="1" 
                               {{ old('ai_analysis_enabled', $settings->ai_analysis_enabled) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-600">Enable daily AI-powered health analysis</span>
                    </label>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Analysis Hour (24h format)</label>
                        <select name="ai_analysis_hour" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            @for($h = 0; $h < 24; $h++)
                                <option value="{{ $h }}" {{ old('ai_analysis_hour', $settings->ai_analysis_hour) == $h ? 'selected' : '' }}>
                                    {{ sprintf('%02d:00', $h) }} {{ $h < 12 ? 'AM' : 'PM' }}
                                </option>
                            @endfor
                        </select>
                        <p class="text-xs text-gray-500 mt-1">When to generate and email the daily report (in {{ $settings->timezone }})</p>
                    </div>

                {{-- Critical Alert Settings --}}
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-1"></i> Critical Alerts (Crashes)
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Cooldown (minutes)</label>
                            <input type="number" name="critical_cooldown_minutes" 
                                   value="{{ old('critical_cooldown_minutes', $settings->critical_cooldown_minutes) }}"
                                   min="5" max="1440"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <p class="text-xs text-gray-500 mt-1">Same error suppressed for this duration</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Reminder (hours)</label>
                            <input type="number" name="critical_reminder_hours" 
                                   value="{{ old('critical_reminder_hours', $settings->critical_reminder_hours) }}"
                                   min="1" max="24"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <p class="text-xs text-gray-500 mt-1">Re-alert if not cleared</p>
                        </div>
                    </div>
                </div>

                {{-- Non-Critical Alert Settings --}}
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-exclamation-circle text-yellow-500 mr-1"></i> Non-Critical Alerts (Errors, Warnings)
                    </h3>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Threshold</label>
                            <input type="number" name="noncritical_threshold" 
                                   value="{{ old('noncritical_threshold', $settings->noncritical_threshold) }}"
                                   min="1" max="100"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <p class="text-xs text-gray-500 mt-1">Occurrences before alert</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Window (minutes)</label>
                            <input type="number" name="noncritical_window_minutes" 
                                   value="{{ old('noncritical_window_minutes', $settings->noncritical_window_minutes) }}"
                                   min="5" max="1440"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <p class="text-xs text-gray-500 mt-1">Time window for threshold</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Cooldown (hours)</label>
                            <input type="number" name="noncritical_cooldown_hours" 
                                   value="{{ old('noncritical_cooldown_hours', $settings->noncritical_cooldown_hours) }}"
                                   min="1" max="48"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <p class="text-xs text-gray-500 mt-1">Suppress after alerting</p>
                        </div>
                    </div>
                </div>

                {{-- Business Hours (Future) --}}
                <div class="mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-clock text-gray-500 mr-1"></i> Business Hours (Optional)
                    </h3>
                    
                    <label class="flex items-center mb-3">
                        <input type="hidden" name="business_hours_only" value="0">
                        <input type="checkbox" name="business_hours_only" value="1"
                               {{ old('business_hours_only', $settings->business_hours_only) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Only send alerts during business hours</span>
                    </label>

                    <label class="flex items-center mb-3">
                        <input type="hidden" name="exclude_weekends" value="0">
                        <input type="checkbox" name="exclude_weekends" value="1"
                               {{ old('exclude_weekends', $settings->exclude_weekends) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Exclude weekends (Saturday & Sunday)</span>
                    </label>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                            <input type="time" name="business_hours_start" 
                                   value="{{ old('business_hours_start', $settings->business_hours_start?->format('H:i')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">End Time</label>
                            <input type="time" name="business_hours_end" 
                                   value="{{ old('business_hours_end', $settings->business_hours_end?->format('H:i')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Timezone</label>
                            <select name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="America/Winnipeg" {{ $settings->timezone === 'America/Winnipeg' ? 'selected' : '' }}>America/Winnipeg</option>
                                <option value="America/Toronto" {{ $settings->timezone === 'America/Toronto' ? 'selected' : '' }}>America/Toronto</option>
                                <option value="America/Vancouver" {{ $settings->timezone === 'America/Vancouver' ? 'selected' : '' }}>America/Vancouver</option>
                                <option value="America/New_York" {{ $settings->timezone === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                                <option value="America/Los_Angeles" {{ $settings->timezone === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles</option>
                                <option value="UTC" {{ $settings->timezone === 'UTC' ? 'selected' : '' }}>UTC</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-2"></i> Save Settings
                </button>
            </form>

            {{-- Hidden test webhook form --}}
            <form id="test-webhook-form" action="{{ route('admin.alerts.test-webhook', $product) }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>

        {{-- Subscribers --}}
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-users mr-2"></i> Subscribers
                </h2>

                {{-- Add Subscriber Form --}}
                <form action="{{ route('admin.alerts.add-subscriber', $product) }}" method="POST" class="mb-4 p-4 bg-gray-50 rounded-lg">
                    @csrf
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Add Subscriber</h3>
                    
                    <div x-data="{ type: 'user' }" class="space-y-3">
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="subscriber_type" value="user" x-model="type" class="text-blue-600">
                                <span class="ml-2 text-sm">System User</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="subscriber_type" value="external" x-model="type" class="text-blue-600">
                                <span class="ml-2 text-sm">External Email</span>
                            </label>
                        </div>

                        <div x-show="type === 'user'">
                            <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="">Select a user...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="type === 'external'" class="space-y-2">
                            <input type="email" name="email" placeholder="email@example.com"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <input type="text" name="name" placeholder="Name (optional)"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>

                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="receive_critical" value="1" checked class="rounded text-blue-600">
                                <span class="ml-2 text-sm">Critical</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="receive_noncritical" value="1" class="rounded text-blue-600">
                                <span class="ml-2 text-sm">Non-Critical</span>
                            </label>
                        </div>

                        <button type="submit" class="bg-green-600 text-white py-2 px-4 rounded-md text-sm hover:bg-green-700">
                            <i class="fas fa-plus mr-1"></i> Add Subscriber
                        </button>
                    </div>
                </form>

                {{-- Subscriber List --}}
                @if($subscribers->count() > 0)
                    <div class="space-y-2">
                        @foreach($subscribers as $subscriber)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    @if($subscriber->user_id)
                                        <i class="fas fa-user text-blue-500 mr-2"></i>
                                    @else
                                        <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                    @endif
                                    <div>
                                        <div class="font-medium text-sm">{{ $subscriber->display_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $subscriber->email_address }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($subscriber->receive_critical)
                                        <span class="px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded">Critical</span>
                                    @endif
                                    @if($subscriber->receive_noncritical)
                                        <span class="px-2 py-0.5 text-xs bg-yellow-100 text-yellow-700 rounded">Non-Critical</span>
                                    @endif
                                    @if(!$subscriber->is_enabled)
                                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">Disabled</span>
                                    @endif
                                    <form action="{{ route('admin.alerts.remove-subscriber', $subscriber) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Remove this subscriber?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm text-center py-4">No subscribers yet</p>
                @endif
            </div>

            {{-- Active Alerts --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-exclamation-circle mr-2 text-red-500"></i> Active Alerts
                    </h2>
                    @if($activeAlerts->count() > 0)
                        <form action="{{ route('admin.alerts.clear-all', $product) }}" method="POST"
                              onsubmit="return confirm('Clear all active alerts?')">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                <i class="fas fa-times-circle mr-1"></i> Clear All
                            </button>
                        </form>
                    @endif
                </div>

                @if($activeAlerts->count() > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($activeAlerts as $alert)
                            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded
                                            {{ $alert->level === 'crash' ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800' }}">
                                            {{ $alert->level }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $alert->occurrence_count }}x</span>
                                    </div>
                                    <div class="text-sm text-gray-700 truncate mt-1">{{ $alert->app_identifier }}</div>
                                    <div class="text-xs text-gray-500">
                                        First: {{ $alert->first_occurrence_at->diffForHumans() }}
                                        @if($alert->last_alerted_at)
                                            | Last alert: {{ $alert->last_alerted_at->diffForHumans() }}
                                        @endif
                                    </div>
                                </div>
                                <form action="{{ route('admin.alerts.clear', $alert) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm ml-2">
                                        <i class="fas fa-check"></i> Clear
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm text-center py-4">No active alerts</p>
                @endif
            </div>

            {{-- Device Heartbeats --}}
            @if($settings->heartbeat_enabled)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-heartbeat mr-2 text-pink-500"></i> Monitored Devices
                </h2>

                @if($deviceHeartbeats->count() > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($deviceHeartbeats as $heartbeat)
                            @php
                                $isMissing = $heartbeat->last_heartbeat_at->lt(now()->subMinutes($settings->heartbeat_timeout_minutes));
                            @endphp
                            <div class="flex items-center justify-between p-3 rounded-lg {{ $isMissing ? 'bg-red-50' : 'bg-green-50' }}">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded {{ $isMissing ? 'bg-red-200 text-red-800' : 'bg-green-200 text-green-800' }}">
                                            {{ $isMissing ? 'Missing' : 'OK' }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">{{ $heartbeat->display_name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $heartbeat->app_identifier }} | Last: {{ $heartbeat->last_heartbeat_at->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($heartbeat->is_monitoring)
                                        <span class="text-xs text-green-600"><i class="fas fa-eye"></i></span>
                                    @else
                                        <span class="text-xs text-gray-400"><i class="fas fa-eye-slash"></i></span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm text-center py-4">No devices have sent heartbeats yet</p>
                @endif
            </div>
            @endif
            </div>
        </div>
    </div>
</x-app-layout>
