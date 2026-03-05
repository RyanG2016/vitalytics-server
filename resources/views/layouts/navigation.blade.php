<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <img src="/favicon.svg" alt="Vitalytics" class="h-8 w-8 mr-2">
                        <span class="font-bold text-xl text-gray-900">Vitalytics</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                    <!-- Mobile App Settings (only visible in iOS app) -->
                    <a href="#"
                       id="mobile-app-settings-desktop"
                       onclick="window.VitalyticsNative && window.VitalyticsNative.openSettings(); return false;"
                       class="hidden items-center px-3 py-1.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:from-indigo-600 hover:to-purple-700 transition-all">
                        <i class="fas fa-mobile-alt mr-2"></i>
                        App Settings
                    </a>

                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if(Auth::user()->isAdmin())
                    <!-- Admin Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none {{ request()->routeIs('admin.*') ? 'text-gray-900 border-b-2 border-indigo-400' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent' }}">
                            <i class="fas fa-cog mr-1"></i>
                            Admin
                            <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <div x-show="open" 
                             @click.away="open = false" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                            <a href="{{ route('admin.products.index') }}" 
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.products.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-cubes w-4"></i> Products
                            </a>
                            <a href="{{ route('admin.users.index') }}" 
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.users.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-users w-4"></i> Users
                            </a>
                            <a href="{{ route('admin.secrets.index') }}" 
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.secrets.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-key w-4"></i> Secrets
                            </a>
                            <a href="{{ route('admin.icons.index') }}" 
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.icons.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-icons w-4"></i> Icons
                            </a>
                            <a href="{{ route('admin.alerts.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.alerts.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-bell w-4"></i> Alerts
                            </a>
                            <a href="{{ route('admin.devices.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.devices.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-heartbeat w-4"></i> Devices
                            </a>
                            <a href="{{ route('admin.configs.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.configs.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-file-code w-4"></i> Configs
                            </a>
                            <a href="{{ route('admin.registration-tokens.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.registration-tokens.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-ticket-alt w-4"></i> Registration Tokens
                            </a>
                            <a href="{{ route('admin.registered-devices.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.registered-devices.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-laptop-code w-4"></i> Registered Devices
                            </a>
                            <a href="{{ route('admin.label-mappings.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.label-mappings.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-tags w-4"></i> Label Mappings
                            </a>
                            <a href="{{ route('admin.maintenance.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.maintenance.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-tools w-4"></i> Maintenance
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Help Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none {{ request()->routeIs('help.*') ? 'text-gray-900 border-b-2 border-indigo-400' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent' }}">
                            <i class="fas fa-question-circle mr-1"></i>
                            Help
                            <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <div x-show="open"
                             @click.away="open = false"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                            <a href="{{ route('help.integrations') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('help.integrations') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-heartbeat w-4 text-red-500"></i> Health Monitoring
                            </a>
                            <a href="{{ route('help.analytics') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('help.analytics') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                <i class="fas fa-chart-line w-4 text-blue-500"></i> Analytics Tracking
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden gap-2">
                <!-- Mobile App Settings (visible in header on mobile, only in iOS app) -->
                <a href="#"
                   id="mobile-app-settings-header"
                   onclick="window.VitalyticsNative && window.VitalyticsNative.openSettings(); return false;"
                   class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:from-indigo-600 hover:to-purple-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </a>
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <!-- Mobile App Settings (only visible in iOS app) -->
            <a href="#"
               id="mobile-app-settings-responsive"
               onclick="window.VitalyticsNative && window.VitalyticsNative.openSettings(); return false;"
               class="hidden mx-4 mb-3 px-4 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:from-indigo-600 hover:to-purple-700 transition-all items-center justify-center">
                <i class="fas fa-mobile-alt mr-2"></i>
                Mobile App Settings
            </a>

            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if(Auth::user()->isAdmin())
                <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</div>
                <x-responsive-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                    <i class="fas fa-cubes mr-2"></i> {{ __('Products') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    <i class="fas fa-users mr-2"></i> {{ __('Users') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.secrets.index')" :active="request()->routeIs('admin.secrets.*')">
                    <i class="fas fa-key mr-2"></i> {{ __('Secrets') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.icons.index')" :active="request()->routeIs('admin.icons.*')">
                    <i class="fas fa-icons mr-2"></i> {{ __('Icons') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.alerts.index')" :active="request()->routeIs('admin.alerts.*')">
                    <i class="fas fa-bell mr-2"></i> {{ __('Alerts') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.devices.index')" :active="request()->routeIs('admin.devices.*')">
                    <i class="fas fa-heartbeat mr-2"></i> {{ __('Devices') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.configs.index')" :active="request()->routeIs('admin.configs.*')">
                    <i class="fas fa-file-code mr-2"></i> {{ __('Configs') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.registration-tokens.index')" :active="request()->routeIs('admin.registration-tokens.*')">
                    <i class="fas fa-ticket-alt mr-2"></i> {{ __('Registration Tokens') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.registered-devices.index')" :active="request()->routeIs('admin.registered-devices.*')">
                    <i class="fas fa-laptop-code mr-2"></i> {{ __('Registered Devices') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.label-mappings.index')" :active="request()->routeIs('admin.label-mappings.*')">
                    <i class="fas fa-tags mr-2"></i> {{ __('Label Mappings') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.maintenance.index')" :active="request()->routeIs('admin.maintenance.*')">
                    <i class="fas fa-tools mr-2"></i> {{ __('Maintenance') }}
                </x-responsive-nav-link>
            @endif
            <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Help</div>
            <x-responsive-nav-link :href="route('help.integrations')" :active="request()->routeIs('help.integrations')">
                <i class="fas fa-heartbeat mr-2 text-red-500"></i> {{ __('Health Monitoring') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('help.analytics')" :active="request()->routeIs('help.analytics')">
                <i class="fas fa-chart-line mr-2 text-blue-500"></i> {{ __('Analytics Tracking') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<style>
    [x-cloak] { display: none !important; }
</style>

<script>
    // Show Mobile App Settings button when running in Vitalytics iOS app
    function showMobileAppSettings() {
        if (window.VitalyticsNative) {
            var desktopBtn = document.getElementById('mobile-app-settings-desktop');
            var responsiveBtn = document.getElementById('mobile-app-settings-responsive');
            var headerBtn = document.getElementById('mobile-app-settings-header');

            if (desktopBtn) {
                desktopBtn.classList.remove('hidden');
                desktopBtn.classList.add('inline-flex');
            }
            if (responsiveBtn) {
                responsiveBtn.classList.remove('hidden');
                responsiveBtn.classList.add('flex');
            }
            if (headerBtn) {
                headerBtn.classList.remove('hidden');
                headerBtn.classList.add('inline-flex');
            }
            console.log('[Vitalytics] Mobile app settings buttons shown:', {
                desktop: !!desktopBtn,
                responsive: !!responsiveBtn,
                header: !!headerBtn
            });
            return true;
        }
        return false;
    }

    // Check immediately (bridge is injected at document start)
    showMobileAppSettings();

    // Also check on DOMContentLoaded in case elements weren't ready
    document.addEventListener('DOMContentLoaded', showMobileAppSettings);

    // Listen for the native ready event (dispatched by iOS app)
    window.addEventListener('vitalyticsNativeReady', showMobileAppSettings);
</script>
