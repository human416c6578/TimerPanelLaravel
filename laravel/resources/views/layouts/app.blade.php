<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Timer Panel')</title>
    @vite('resources/css/app.css')
    @yield('styles')
</head>
<body class="speed-shell font-sans min-h-screen flex flex-col">

    <header class="speed-header sticky top-0 z-40 px-4 py-3">
        <div class="container mx-auto flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <a href="{{ url('/') }}" class="speed-brand flex items-center gap-3 text-xl font-black uppercase">
                <span class="speed-brand-mark block h-9 w-9 rounded-md"></span>
                <span>Timer Panel</span>
            </a>

            <nav class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                <a href="{{ url('/') }}" class="speed-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Live Feed</a>
                <a href="{{ url('/leaderboard') }}" class="speed-nav-link {{ request()->routeIs('leaderboard.index') ? 'is-active' : '' }}">Ranks</a>
                <a href="{{ url('/maps') }}" class="speed-nav-link {{ request()->routeIs('maps.*') ? 'is-active' : '' }}">Maps</a>
                <a href="{{ url('/players') }}" class="speed-nav-link {{ request()->routeIs('players.*') ? 'is-active' : '' }}">Players</a>
                <a href="{{ url('/replays') }}" class="speed-nav-link {{ request()->routeIs('replays.*') ? 'is-active' : '' }}">Replays</a>
            </nav>

            @if (Route::has('login'))
                <nav class="flex flex-wrap items-center gap-2 text-sm">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                        class="speed-btn-primary px-4 py-2">
                            Dashboard
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="speed-btn-danger cursor-pointer px-4 py-2">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                        class="speed-btn-secondary px-4 py-2">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                            class="speed-btn-primary px-4 py-2">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main class="container mx-auto flex-grow px-4 py-6 sm:px-6">
        @yield('content')
    </main>

    <footer class="border-t border-cyan-400/10 bg-black/30 py-4 text-center text-sm text-slate-500">
        &copy; {{ date('Y') }} Timer Panel. Split faster.
    </footer>

    @yield('scripts')
    @stack('scripts')
</body>
</html>

