<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZAMOBILE HOME')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    @php
        $navPill = 'inline-flex items-center justify-center h-9 px-4 rounded-md text-sm font-medium border transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1';
        $navPillIdle = $navPill.' border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:text-gray-900';
        $navPillOn = $navPill.' border-blue-600 bg-blue-600 text-white hover:bg-blue-700';
    @endphp
    <nav class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-1.5">
                    <a
                        href="{{ route('dashboard') }}"
                        @class([$navPillIdle => ! request()->routeIs('dashboard'), $navPillOn => request()->routeIs('dashboard')])
                    >
                        Home
                    </a>
                    <a
                        href="{{ route('imeis.index') }}"
                        @class([$navPillIdle => ! request()->routeIs('imeis.*'), $navPillOn => request()->routeIs('imeis.*')])
                    >
                        IMEI
                    </a>
                    <a
                        href="{{ route('contacts.index') }}"
                        @class([$navPillIdle => ! request()->routeIs('contacts.*'), $navPillOn => request()->routeIs('contacts.*')])
                    >
                        Contacts
                    </a>
                    <a
                        href="{{ route('notes.index') }}"
                        @class([$navPillIdle => ! request()->routeIs('notes.*'), $navPillOn => request()->routeIs('notes.*')])
                    >
                        Notes
                    </a>
                    <div class="relative" id="settings-menu-container">
                        <button
                            id="settings-menu-button"
                            type="button"
                            data-nav-pill-idle="{{ $navPillIdle }}"
                            data-nav-pill-on="{{ $navPillOn }}"
                            data-nav-route-active="{{ request()->routeIs('settings.*') ? '1' : '0' }}"
                            @class([$navPillIdle => ! request()->routeIs('settings.*'), $navPillOn => request()->routeIs('settings.*')])
                        >
                            Settings
                        </button>
                        <div id="settings-menu-dropdown"
                             class="absolute left-0 mt-2 w-52 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200 hidden">
                            <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mx-1">
                                IMEI Settings
                            </a>
                            <a href="{{ route('settings.notes.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mx-1">
                                Note Settings
                            </a>
                            <a href="{{ route('settings.vat.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mx-1">
                                VAT Settings
                            </a>
                            <a href="{{ route('settings.default.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mx-1">
                                Default Settings
                            </a>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    @auth
                        <div class="relative" id="user-menu-container">
                            <button
                                id="user-menu-button"
                                type="button"
                                data-nav-pill-idle="{{ $navPillIdle }}"
                                data-nav-pill-on="{{ $navPillOn }}"
                                data-nav-route-active="{{ request()->routeIs('profile.*') || request()->routeIs('roles.*') || request()->routeIs('user-roles.*') ? '1' : '0' }}"
                                @class([
                                    $navPillIdle => ! request()->routeIs('profile.*') && ! request()->routeIs('roles.*') && ! request()->routeIs('user-roles.*'),
                                    $navPillOn => request()->routeIs('profile.*') || request()->routeIs('roles.*') || request()->routeIs('user-roles.*'),
                                ])
                            >
                                <span class="max-w-[12rem] truncate">{{ Auth::user()->name }}</span>
                            </button>

                            <div id="user-menu-dropdown"
                                 class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200 hidden">
                                @if(Auth::user()->isRoleManager())
                                    <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mx-1">
                                        Manage Roles
                                    </a>
                                    <a href="{{ route('user-roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mx-1">
                                        Assign Roles
                                    </a>
                                @endif
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mx-1">
                                    Profile
                                </a>
                                <a href="{{ route('profile.password') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mx-1">
                                    Password Reset
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 rounded-md mx-1">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="{{ $navPillIdle }}">Login</a>
                        <a href="{{ route('register') }}" class="{{ $navPillIdle }}">Register</a>
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
        document.addEventListener('DOMContentLoaded', function () {
            function setNavPillState(button, active) {
                const idleClasses = (button.dataset.navPillIdle || '').split(' ').filter(Boolean);
                const onClasses = (button.dataset.navPillOn || '').split(' ').filter(Boolean);

                button.classList.remove(...idleClasses, ...onClasses);
                button.classList.add(...(active ? onClasses : idleClasses));
            }

            function bindNavDropdown(buttonId, dropdownId, containerId) {
                const button = document.getElementById(buttonId);
                const dropdown = document.getElementById(dropdownId);
                const container = document.getElementById(containerId);

                if (! button || ! dropdown || ! container) {
                    return;
                }

                const routeActive = button.dataset.navRouteActive === '1';

                button.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const isHidden = dropdown.classList.contains('hidden');

                    if (isHidden) {
                        dropdown.classList.remove('hidden');
                        setNavPillState(button, true);
                    } else {
                        dropdown.classList.add('hidden');
                        setNavPillState(button, routeActive);
                    }
                });

                document.addEventListener('click', function (event) {
                    if (! container.contains(event.target)) {
                        dropdown.classList.add('hidden');
                        setNavPillState(button, routeActive);
                    }
                });
            }

            bindNavDropdown('settings-menu-button', 'settings-menu-dropdown', 'settings-menu-container');
            bindNavDropdown('user-menu-button', 'user-menu-dropdown', 'user-menu-container');
        });
    </script>
</body>
</html>
