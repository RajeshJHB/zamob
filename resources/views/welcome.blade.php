<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Template App</title>
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50">
        <div class="min-h-screen flex flex-col">
            @if (Route::has('login'))
                <nav class="flex justify-end p-6">
                    <div class="flex gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="bg-white hover:bg-gray-100 text-gray-900 font-semibold py-2 px-6 rounded-lg border border-gray-300 transition-colors">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </div>
                </nav>
            @endif
            <div class="flex-1 flex items-center justify-center">
                <h1 class="text-4xl font-bold text-gray-900">Template App</h1>
                </div>
        </div>
    </body>
</html>
