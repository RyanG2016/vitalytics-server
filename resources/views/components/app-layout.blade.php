<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Vitalytics') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            750: '#2d3748',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gray-100" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', userMenu: false }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <img src="/favicon.svg" alt="Vitalytics" class="h-8 w-8 mr-2">
                        <span class="font-bold text-xl text-gray-900">Vitalytics</span>
                    </a>
                    <!-- Navigation Links -->
                    <div class="hidden sm:flex items-center gap-6">
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' }}">
                            Dashboard
                        </a>
                        @if(Auth::user()->isAdmin())
                        <!-- Admin Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="text-sm font-medium flex items-center gap-1 {{ request()->routeIs('admin.*') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' }}">
                                <i class="fas fa-cog"></i>
                                Admin
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('admin.products.index') }}" 
                                   class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.products.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                    <i class="fas fa-cubes w-4"></i> Products
                                </a>
                                <a href="{{ route('admin.users.index') }}" 
                                   class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.users.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                    <i class="fas fa-users w-4"></i> Users
                                </a>
                                <a href="{{ route('admin.secrets.index') }}" 
                                   class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.secrets.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                    <i class="fas fa-key w-4"></i> Secrets
                                </a>
                                <a href="{{ route('admin.icons.index') }}" 
                                   class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.icons.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                    <i class="fas fa-icons w-4"></i> Icons
                                </a>
                                <a href="{{ route('admin.alerts.index') }}"
                                   class="flex items-center gap-2 px-4 py-2 text-sm {{ request()->routeIs('admin.alerts.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-100' }}">
                                    <i class="fas fa-bell w-4"></i> Alerts
                                </a>
                            </div>
                        </div>
                        @endif
                        <div class="relative" x-data="{ helpOpen: false }">
                            <button @click="helpOpen = !helpOpen" class="text-sm font-medium flex items-center gap-1 {{ request()->routeIs('help.*') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' }}">
                                Help <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div x-show="helpOpen" @click.away="helpOpen = false" x-cloak
                                 class="absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('help.integrations') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-heartbeat mr-2 text-red-500"></i> Health Monitoring
                                </a>
                                <a href="{{ route('help.analytics') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-chart-line mr-2 text-blue-500"></i> Analytics Tracking
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="hidden md:block text-sm text-gray-500">
                        {{ now()->format('M d, Y g:i A') }}
                    </span>
                    <!-- Dark mode toggle -->
                    <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100">
                        <i x-show="!darkMode" class="fas fa-moon"></i>
                        <i x-show="darkMode" class="fas fa-sun" x-cloak></i>
                    </button>
                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                            <span class="text-sm font-medium">{{ Auth::user()->name }}</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
                Vitalytics - App Health Monitoring
            </p>
        </div>
    </footer>
</body>
</html>
