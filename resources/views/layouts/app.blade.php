<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Timer Panel')</title>
    @vite('resources/css/app.css')
    @yield('styles')
</head>
<body class="bg-gray-950 text-gray-100 font-sans min-h-screen flex flex-col">

    <header class="bg-gray-900 shadow p-4">
        <div class="container mx-auto flex items-center justify-between">
            <!-- Logo / Title -->
            <a href="{{ url('/') }}" class="text-2xl font-bold text-indigo-400">Timer Panel</a>

            <!-- Navigation -->
            <nav class="space-x-6 text-gray-300 font-medium">
                <a href="{{ url('/') }}" class="hover:text-white">Home</a>
                <a href="{{ url('/leaderboard') }}" class="hover:text-white">Leaderboard</a>
                <a href="{{ url('/maps') }}" class="hover:text-white">Maps</a>
                <a href="{{ url('/players') }}" class="hover:text-white">Players</a>
                <a href="{{ url('/replays') }}" class="hover:text-white">Replays</a>
            </nav>

            <!-- Auth buttons -->
            @if (Route::has('login'))
                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                        class="px-4 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition">
                            Dashboard
                        </a>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-1.5 rounded border border-gray-700 text-gray-300 hover:bg-red-600 hover:text-white transition cursor-pointer">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                        class="px-4 py-1.5 rounded border border-gray-700 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                            class="px-4 py-1.5 rounded border border-gray-700 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main class="container mx-auto px-6 py-6 flex-grow">
        @yield('content')
    </main>

    <footer class="bg-gray-900 py-4 text-center text-sm text-gray-500 border-t border-gray-700">
        &copy; {{ date('Y') }} Timer Panel. All rights reserved.
    </footer>

    @yield('scripts')
    @stack('scripts')
</body>
</html>


