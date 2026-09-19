<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="flex min-h-svh flex-col items-center justify-center p-4 font-sans antialiased">
        <div class="w-full max-w-sm">
            <a href="{{ route('home') }}" class="mb-2 flex items-center justify-center gap-2">
                <x-app-logo-icon class="size-7 text-accent" />
                <span class="text-base font-bold uppercase tracking-[0.12em]">
                    Timer<span class="text-accent">Panel</span>
                </span>
            </a>

            <div class="panel panel-flush">
                <header class="panel-header">
                    <p class="hud-label">Administrator login</p>
                </header>

                <div class="flex flex-col gap-4 p-4">
                    {{ $slot }}
                </div>
            </div>

            <p class="mt-2 text-center text-[11px] text-subtle">
                <a href="{{ route('home') }}" class="hover:text-accent">&laquo; back to the site</a>
            </p>
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>
