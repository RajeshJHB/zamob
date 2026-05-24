<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZAMOBILE HOME')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center text-xl font-semibold text-gray-900 hover:text-gray-700 focus:outline-none">
                        Home
                    </a>
                    <a href="{{ route('imeis.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                        IMEI
                    </a>
                    <a href="{{ route('contacts.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                        Contacts
                    </a>
                    <a href="{{ route('notes.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                        Notes
                    </a>
                    <div class="relative" id="settings-menu-container">
                        <button id="settings-menu-button" type="button" class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none">
                            Settings
                            <svg id="settings-menu-arrow" class="ml-1 h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="settings-menu-dropdown"
                             class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200 hidden">
                            <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                IMEI Settings
                            </a>
                            <a href="{{ route('settings.notes.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Note Settings
                            </a>
                            <a href="{{ route('settings.vat.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                VAT Settings
                            </a>
                            <a href="{{ route('settings.default.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Default Settings
                            </a>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <div class="relative" id="user-menu-container">
                            <button id="user-menu-button" class="flex items-center text-gray-700 hover:text-gray-900 focus:outline-none">
                                <span>{{ Auth::user()->name }}</span>
                                <svg id="user-menu-arrow" class="ml-1 h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <div id="user-menu-dropdown" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200 hidden">
                                @if(Auth::user()->isRoleManager())
                                    <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Manage Roles
                                    </a>
                                    <a href="{{ route('user-roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Assign Roles
                                    </a>
                                @endif
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Profile
                                </a>
                                <a href="{{ route('profile.password') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Password Reset
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900">Login</a>
                        <a href="{{ route('register') }}" class="text-gray-700 hover:text-gray-900">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="py-10">
        @php
            $mainContentFullWidth = trim($__env->yieldContent('contentWidth')) === 'full';
        @endphp
        <div @class([
            'mx-auto px-4 sm:px-6 lg:px-8',
            'max-w-7xl' => ! $mainContentFullWidth,
            'w-full max-w-none' => $mainContentFullWidth,
        ])>
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Settings Menu dropdown
            const settingsMenuButton = document.getElementById('settings-menu-button');
            const settingsMenuDropdown = document.getElementById('settings-menu-dropdown');
            const settingsMenuArrow = document.getElementById('settings-menu-arrow');

            if (settingsMenuButton && settingsMenuDropdown) {
                settingsMenuButton.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const isHidden = settingsMenuDropdown.classList.contains('hidden');

                    if (isHidden) {
                        settingsMenuDropdown.classList.remove('hidden');
                        if (settingsMenuArrow) {
                            settingsMenuArrow.style.transform = 'rotate(180deg)';
                        }
                    } else {
                        settingsMenuDropdown.classList.add('hidden');
                        if (settingsMenuArrow) {
                            settingsMenuArrow.style.transform = 'rotate(0deg)';
                        }
                    }
                });

                document.addEventListener('click', function (e) {
                    const container = document.getElementById('settings-menu-container');
                    if (container && ! container.contains(e.target)) {
                        settingsMenuDropdown.classList.add('hidden');
                        if (settingsMenuArrow) {
                            settingsMenuArrow.style.transform = 'rotate(0deg)';
                        }
                    }
                });
            }

            // User Menu dropdown
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            const userMenuArrow = document.getElementById('user-menu-arrow');
            
            if (userMenuButton && userMenuDropdown) {
                // Toggle dropdown on button click
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isHidden = userMenuDropdown.classList.contains('hidden');
                    
                    if (isHidden) {
                        userMenuDropdown.classList.remove('hidden');
                        userMenuArrow.style.transform = 'rotate(180deg)';
                    } else {
                        userMenuDropdown.classList.add('hidden');
                        userMenuArrow.style.transform = 'rotate(0deg)';
                    }
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    const container = document.getElementById('user-menu-container');
                    if (container && !container.contains(e.target)) {
                        userMenuDropdown.classList.add('hidden');
                        userMenuArrow.style.transform = 'rotate(0deg)';
                    }
                });
            }
        });
    </script>
</body>
</html>
