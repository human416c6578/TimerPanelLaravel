@php
    $links = [
        ['label' => 'Home', 'route' => 'home', 'active' => 'home', 'icon' => 'home'],
        ['label' => 'Ranks', 'route' => 'leaderboard.index', 'active' => 'leaderboard.*', 'icon' => 'trophy'],
        ['label' => 'Maps', 'route' => 'maps.index', 'active' => 'maps.*', 'icon' => 'map'],
        ['label' => 'Players', 'route' => 'players.index', 'active' => 'players.*', 'icon' => 'users'],
        ['label' => 'Replays', 'route' => 'replays.index', 'active' => 'replays.*', 'icon' => 'play'],
    ];
@endphp

<header x-data="{ open: false }" class="bg-surface-0">
    {{-- Masthead: the near-black Steam chrome --}}
    <div class="border-b border-black bg-[#171a21]">
        <div class="flex items-center gap-3 px-3 py-2">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-app-logo-icon class="size-8 text-accent" />
                <span class="text-base uppercase tracking-[0.2em] text-white">
                    Timer<span class="text-accent">Panel</span>
                </span>
            </a>

            <span class="ms-1 hidden text-[11px] uppercase tracking-widest text-subtle sm:inline">bhop &amp; deathrun timer</span>

            <div class="ms-auto flex items-center gap-1.5">
                <button
                    type="button"
                    x-data
                    x-on:click="$flux.appearance = $flux.dark ? 'light' : 'dark'"
                    class="btn btn-ghost btn-sm"
                    title="Toggle theme"
                >
                    Theme
                </button>

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
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Admin login</a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Nav strip --}}
    <div class="border-b border-black bg-surface-2">
        <div class="flex items-center px-3">
            <nav class="hidden border-s border-line lg:flex">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}"
                       @class(['nav-link', 'is-active' => request()->routeIs($link['active'])])>
                        <x-icon :name="$link['icon']" class="size-3.5" />
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <button type="button" x-on:click="open = ! open" class="nav-link border-s border-line lg:hidden" :aria-expanded="open">
                Menu
            </button>

            @auth
                <a href="{{ route('dashboard') }}"
                   @class(['nav-link ms-auto border-s', 'is-active' => request()->routeIs('dashboard')])>
                    <x-icon name="shield" class="size-3.5" /> Admin
                </a>
            @endauth
        </div>
    </div>

    <nav x-show="open" x-cloak x-collapse class="border-t border-line bg-surface-2 lg:hidden">
        <div class="flex flex-col px-3">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   @class(['nav-link border-b border-line', 'is-active' => request()->routeIs($link['active'])])>
                    <x-icon :name="$link['icon']" class="size-3.5" />
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</header>
