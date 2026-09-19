@php
    $links = [
        ['label' => 'Home', 'route' => 'home', 'active' => 'home'],
        ['label' => 'Leaderboards', 'route' => 'leaderboard.index', 'active' => 'leaderboard.*'],
        ['label' => 'Maps', 'route' => 'maps.index', 'active' => 'maps.*'],
        ['label' => 'Players', 'route' => 'players.index', 'active' => 'players.*'],
        ['label' => 'Replays', 'route' => 'replays.index', 'active' => 'replays.*'],
    ];
@endphp

<header class="site-header" x-data="{ open: false }">
    <div class="mx-auto flex max-w-[1200px] flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
            <x-app-logo-icon class="size-7 text-accent" />
            <span class="text-[15px] font-extrabold uppercase tracking-tight">Timer<span class="text-accent">Panel</span></span>
        </a>

        <div class="order-last w-full md:order-none md:max-w-xl md:flex-1">
            <livewire:global-search />
        </div>

        <div class="ms-auto flex items-center gap-2">
            <button type="button" x-data x-on:click="$flux.appearance = $flux.dark ? 'light' : 'dark'" class="btn btn-ghost btn-sm" title="Toggle theme">Theme</button>

            @auth
                <flux:dropdown position="bottom" align="end">
                    <flux:profile :initials="auth()->user()->initials()" icon:trailing="chevrons-up-down" />

                    <flux:menu class="w-56">
                        <div class="px-2 py-1.5">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-muted">{{ auth()->user()->email }}</p>
                        </div>

                        <flux:menu.separator />

                        <flux:menu.item :href="route('dashboard')" icon="home" wire:navigate>{{ __('Dashboard') }}</flux:menu.item>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">{{ __('Log out') }}</flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <a href="{{ route('login') }}" class="btn btn-sm">Admin login</a>
            @endauth

            <button type="button" x-on:click="open = ! open" class="btn btn-ghost btn-sm md:hidden" :aria-expanded="open">Menu</button>
        </div>
    </div>

    {{-- One navigation, once. --}}
    <nav class="border-t border-line" :class="{ 'max-md:hidden': ! open }">
        <div class="mx-auto flex max-w-[1200px] flex-col px-4 md:flex-row md:items-center">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" @class(['nav-link', 'is-active' => request()->routeIs($link['active'])])>{{ $link['label'] }}</a>
            @endforeach

            @auth
                <a href="{{ route('dashboard') }}" @class(['nav-link md:ms-auto', 'is-active' => request()->routeIs('dashboard')])>
                    <x-icon name="shield" class="size-3.5" /> Admin
                </a>
            @endauth
        </div>
    </nav>
</header>
