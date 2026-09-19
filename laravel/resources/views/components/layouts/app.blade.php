@props(['title' => null, 'wide' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="flex min-h-svh flex-col font-sans antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-surface-2 focus:px-4 focus:py-2">
        Skip to content
    </a>

    <x-layouts.app.header />

    <main id="main" class="mx-auto w-full max-w-[1200px] grow px-4 py-5">
        {{ $slot }}
    </main>

    <x-layouts.app.footer />

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
