@php
    $links = [
        ['label' => 'Home', 'route' => 'home', 'active' => 'home', 'icon' => 'home'],
        ['label' => 'Rankings', 'route' => 'leaderboard.index', 'active' => 'leaderboard.*', 'icon' => 'trophy'],
        ['label' => 'Maps', 'route' => 'maps.index', 'active' => 'maps.*', 'icon' => 'map'],
        ['label' => 'Players', 'route' => 'players.index', 'active' => 'players.*', 'icon' => 'users'],
        ['label' => 'Replays', 'route' => 'replays.index', 'active' => 'replays.*', 'icon' => 'play'],
    ];

    $online = collect($liveServers ?? [])->where('online', true);
    $playersOnline = $online->sum(fn ($server) => (int) $server['players']);
@endphp

<aside class="space-y-2">
    {{-- Section nav --}}
    <section class="panel panel-flush">
        <header class="panel-header"><p class="hud-label">Browse</p></header>

        <nav class="flex flex-col">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   @class(['side-link', 'is-active' => request()->routeIs($link['active'])])>
                    <x-icon :name="$link['icon']" class="size-3.5" />
                    {{ $link['label'] }}
                </a>
            @endforeach

            @auth
                <a href="{{ route('dashboard') }}" @class(['side-link', 'is-active' => request()->routeIs('dashboard')])>
                    <x-icon name="shield" class="size-3.5" />
                    Admin panel
                </a>
            @endauth
        </nav>
    </section>

    {{-- Live servers, GameTracker style: slot bar, current map, connect --}}
    <section class="panel panel-flush">
        <header class="panel-header">
            <p class="hud-label"><x-icon name="server" class="size-3.5" /> Servers</p>
            <span class="text-[11px] font-bold tabular {{ $online->isEmpty() ? 'text-subtle' : 'text-live' }}">
                {{ $playersOnline }} online
            </span>
        </header>

        <div class="flex flex-col">
            @forelse ($liveServers ?? [] as $server)
                <div class="border-b border-line px-2 py-1.5 last:border-b-0">
                    <div class="flex items-center gap-1.5">
                        <span @class(['dot', 'dot-live' => $server['online']])></span>
                        <span class="truncate text-[11px] font-bold uppercase">{{ $server['name'] }}</span>

                        <span class="ms-auto shrink-0 font-mono text-[11px] tabular {{ $server['online'] ? 'text-ink' : 'text-subtle' }}">
                            {{ $server['online'] ? $server['players'].'/'.$server['max_players'] : 'down' }}
                        </span>
                    </div>

                    @php
                        $max = max(1, (int) ($server['max_players'] ?? 1));
                        $fill = $server['online'] ? min(100, round(((int) $server['players'] / $max) * 100)) : 0;
                    @endphp

                    <div class="slots mt-1" role="img" aria-label="{{ $fill }}% full">
                        <span class="slots-fill" style="width: {{ $fill }}%"></span>
                    </div>

                    <p class="mt-1 truncate font-mono text-[11px] {{ $server['online'] ? 'text-accent' : 'text-subtle' }}">
                        {{ $server['map'] ?? 'offline' }}
                    </p>

                    @if ($server['online'])
                        <a href="steam://connect/{{ $server['host'] }}:{{ $server['port'] }}"
                           class="mt-1 block text-[11px] font-bold uppercase text-muted hover:text-accent"
                           title="Join {{ $server['name'] }}">
                            Connect &raquo;
                        </a>
                    @endif
                </div>
            @empty
                <p class="px-2 py-3 text-center text-[11px] text-subtle">No servers configured.</p>
            @endforelse
        </div>
    </section>

    {{-- Search --}}
    <section class="panel panel-flush">
        <header class="panel-header"><p class="hud-label"><x-icon name="search" class="size-3.5" /> Find a player</p></header>

        <form method="GET" action="{{ route('players.index') }}" class="flex gap-1 p-2">
            <input type="search" name="search" placeholder="name or steam id" class="input" autocomplete="off">
            <button type="submit" class="btn btn-primary btn-sm">Go</button>
        </form>
    </section>
</aside>
