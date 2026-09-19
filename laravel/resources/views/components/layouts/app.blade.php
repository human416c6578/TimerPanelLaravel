@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="flex min-h-svh flex-col font-sans antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-surface-2 focus:px-4 focus:py-2">
        Skip to content
    </a>

    <div class="site-frame mx-auto flex w-full max-w-[1120px] grow flex-col">
        <x-layouts.app.topbar />

        <div class="grid grow gap-2 p-2 lg:grid-cols-[13rem_minmax(0,1fr)]">
            <div class="hidden lg:block">
                <x-layouts.app.sidebar />
            </div>

            <main id="main" class="min-w-0">
                {{ $slot }}
            </main>
        </div>

        <footer class="border-t border-line bg-surface-0/60">
            <div class="flex flex-col gap-1 px-3 py-2.5 text-[11px] text-subtle sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }} &mdash; times read live from the game servers</p>

                <nav class="flex flex-wrap gap-2">
                    <a href="{{ route('home') }}" class="hover:text-accent">Home</a>
                    <span aria-hidden="true">|</span>
                    <a href="{{ route('leaderboard.index') }}" class="hover:text-accent">Rankings</a>
                    <span aria-hidden="true">|</span>
                    <a href="{{ route('maps.index') }}" class="hover:text-accent">Maps</a>
                    <span aria-hidden="true">|</span>
                    <a href="{{ route('players.index') }}" class="hover:text-accent">Players</a>
                    <span aria-hidden="true">|</span>
                    <a href="{{ route('replays.index') }}" class="hover:text-accent">Replays</a>
                </nav>
            </div>
        </footer>
    </div>

    @if (session('status') || session('error'))
        <script type="application/json" id="flash-message">@json([
            'message' => session('status') ?? session('error'),
            'type' => session('error') ? 'error' : 'success',
        ])</script>
    @endif

    @stack('scripts')
    @livewireScripts
    @fluxScripts
</body>
</html>
