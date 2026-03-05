<x-app-layout>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    <style>
        #map { height: calc(100vh - 320px); min-height: 500px; border-radius: 0.5rem; }
        .leaflet-popup-content { min-width: 200px; }
        .marker-cluster-small { background-color: rgba(6, 182, 212, 0.6); }
        .marker-cluster-small div { background-color: rgba(6, 182, 212, 0.8); }
        .marker-cluster-medium { background-color: rgba(6, 182, 212, 0.6); }
        .marker-cluster-medium div { background-color: rgba(6, 182, 212, 0.8); }
        .marker-cluster-large { background-color: rgba(6, 182, 212, 0.6); }
        .marker-cluster-large div { background-color: rgba(6, 182, 212, 0.8); }
        .marker-cluster { color: white; font-weight: bold; }
        /* Event marker clusters - purple */
        .event-cluster .marker-cluster-small { background-color: rgba(139, 92, 246, 0.6); }
        .event-cluster .marker-cluster-small div { background-color: rgba(139, 92, 246, 0.8); }
        .event-cluster .marker-cluster-medium { background-color: rgba(139, 92, 246, 0.6); }
        .event-cluster .marker-cluster-medium div { background-color: rgba(139, 92, 246, 0.8); }
        .event-cluster .marker-cluster-large { background-color: rgba(139, 92, 246, 0.6); }
        .event-cluster .marker-cluster-large div { background-color: rgba(139, 92, 246, 0.8); }
    </style>

    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('dashboard', ['mode' => 'analytics', 'hours' => $hours, 'product' => $product]) }}" class="text-cyan-600 hover:text-cyan-800 text-sm mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Analytics Dashboard
                </a>
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-map-marked-alt mr-2 text-cyan-600"></i> Geographic Distribution
                </h1>
            </div>
        </div>
    </div>

    <!-- View Toggle -->
    <div class="bg-white rounded-lg shadow mb-4 p-2 inline-flex">
        <a href="{{ request()->fullUrlWithQuery(['view' => 'sessions']) }}"
           class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'sessions' ? 'bg-cyan-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-user-clock mr-1"></i> Sessions
        </a>
        <a href="{{ request()->fullUrlWithQuery(['view' => 'events']) }}"
           class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'events' ? 'bg-violet-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-map-pin mr-1"></i> Event Locations
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <input type="hidden" name="view" value="{{ $viewMode }}">

            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Time Range:</label>
                <select name="hours" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="1" {{ $hours == 1 ? 'selected' : '' }}>Last Hour</option>
                    <option value="6" {{ $hours == 6 ? 'selected' : '' }}>Last 6 Hours</option>
                    <option value="24" {{ $hours == 24 ? 'selected' : '' }}>Last 24 Hours</option>
                    <option value="168" {{ $hours == 168 ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="720" {{ $hours == 720 ? 'selected' : '' }}>Last 30 Days</option>
                </select>
            </div>

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
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="show_test" value="1" {{ $showTest ? 'checked' : '' }}
                           onchange="this.form.submit()" class="rounded border-gray-300 text-cyan-600">
                    <span class="ml-2 text-sm text-gray-700">Show Test Data</span>
                </label>
            </div>
        </form>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 {{ $viewMode === 'events' ? 'bg-violet-100' : 'bg-cyan-100' }} rounded-lg flex items-center justify-center">
                    <i class="fas fa-map-marker-alt {{ $viewMode === 'events' ? 'text-violet-600' : 'text-cyan-600' }}"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalWithGeo) }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $viewMode === 'events' ? 'Events with Location' : 'Sessions with Location' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-globe text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $locationStats->count() }}</p>
                    <p class="text-sm text-gray-500">Countries</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm font-medium text-gray-700 mb-2">Top Countries</p>
            <div class="flex flex-wrap gap-2">
                @forelse($locationStats->take(5) as $stat)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        {{ $stat->country }} ({{ number_format($stat->count) }})
                    </span>
                @empty
                    <span class="text-sm text-gray-400">No location data yet</span>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="bg-white rounded-lg shadow p-4 relative">
        @if($viewMode === 'events')
            <div class="absolute top-6 right-6 z-[1000] bg-white/90 backdrop-blur-sm rounded-lg shadow-lg px-3 py-2 text-xs">
                <i class="fas fa-info-circle text-violet-500 mr-1"></i>
                <span class="text-gray-600">Events aggregated by location</span>
            </div>
        @endif
        <div id="map"></div>
        @if($markers->isEmpty())
        <div class="mt-4 p-6 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
            <i class="fas fa-info-circle text-yellow-500 text-2xl mb-2"></i>
            <p class="text-yellow-800 font-medium">No {{ $viewMode }} with location data yet</p>
            <p class="text-yellow-600 text-sm mt-1">Location data is captured for new {{ $viewMode }}. As users interact with your apps, their locations will appear on the map.</p>
        </div>
        @endif
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const map = L.map('map').setView([30, 0], 2);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18,
            }).addTo(map);

            const markers = @json($markers);
            const viewMode = '{{ $viewMode }}';

            if (markers.length === 0) {
                return;
            }

            const clusterGroup = L.markerClusterGroup({
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
            });

            // Different icons for sessions vs events
            const sessionIcon = L.divIcon({
                className: 'custom-marker',
                html: '<div style="background-color: #06b6d4; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6],
            });

            const eventIcon = L.divIcon({
                className: 'custom-marker',
                html: '<div style="background-color: #8b5cf6; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6],
            });

            const bounds = [];

            markers.forEach(item => {
                const icon = viewMode === 'events' ? eventIcon : sessionIcon;
                const marker = L.marker([item.lat, item.lng], { icon: icon });

                let popupContent;

                if (viewMode === 'events') {
                    // Event location popup
                    popupContent = `
                        <div class="text-sm">
                            <p class="font-semibold text-gray-900 mb-1">${item.location}</p>
                            <p class="text-gray-600 mb-2">${item.app_name}</p>
                            <div class="border-t pt-2 mt-2 space-y-1">
                                <p><i class="fas fa-mouse-pointer text-violet-400 w-4"></i> <strong>${item.event_count.toLocaleString()}</strong> events</p>
                                <p><i class="fas fa-user-clock text-violet-400 w-4"></i> ${item.session_count.toLocaleString()} sessions</p>
                                <p><i class="fas fa-mobile-alt text-violet-400 w-4"></i> ${item.device_count.toLocaleString()} devices</p>
                            </div>
                        </div>
                    `;
                } else {
                    // Session popup (original)
                    popupContent = `
                        <div class="text-sm">
                            <p class="font-semibold text-gray-900 mb-1">${item.location}</p>
                            <p class="text-gray-600 mb-2">${item.app_name}</p>
                            <div class="border-t pt-2 mt-2 space-y-1">
                                <p><i class="fas fa-calendar text-gray-400 w-4"></i> ${item.started_at}</p>
                                <p><i class="fas fa-mouse-pointer text-gray-400 w-4"></i> ${item.event_count} events</p>
                                <p><i class="fas fa-desktop text-gray-400 w-4"></i> ${item.screens_viewed} screens</p>
                                <p><i class="fas fa-mobile-alt text-gray-400 w-4"></i> ${item.platform}</p>
                            </div>
                            <div class="mt-2 pt-2 border-t">
                                <a href="{{ url('/analytics/session') }}/${item.session_id}?show_test={{ $showTest ? '1' : '0' }}"
                                   class="text-cyan-600 hover:text-cyan-800 text-xs">
                                    View Session <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    `;
                }

                marker.bindPopup(popupContent);
                clusterGroup.addLayer(marker);
                bounds.push([item.lat, item.lng]);
            });

            map.addLayer(clusterGroup);

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 12 });
            }
        });
    </script>
</x-app-layout>
