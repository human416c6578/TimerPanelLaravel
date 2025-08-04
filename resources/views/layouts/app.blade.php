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
            <a href="{{ url('/') }}" class="text-2xl font-bold text-indigo-400">Timer Panel</a>
            <nav class="space-x-6 text-gray-300 font-medium">
                <a href="{{ url('/') }}" class="hover:text-white">Home</a>
                <a href="{{ url('/leaderboard') }}" class="hover:text-white">Leaderboard</a>
                <a href="{{ url('/maps') }}" class="hover:text-white">Maps</a>
                <a href="{{ url('/players') }}" class="hover:text-white">Players</a>
                <a href="{{ url('/replays') }}" class="hover:text-white">Replays</a>
            </nav>
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
