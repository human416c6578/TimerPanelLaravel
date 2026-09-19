<div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
    <label class="search-box">
        <x-icon name="search" class="text-subtle" />
        <span class="sr-only">Search</span>
        <input
            type="search"
            wire:model.live.debounce.200ms="q"
            x-on:focus="open = true"
            x-on:input="open = true"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
        >
        <span wire:loading class="shrink-0 text-[11px] text-subtle">…</span>
    </label>

    @if (mb_strlen(trim($q)) >= 2)
        <div class="search-results" x-show="open" x-cloak>
            @forelse ($this->players as $player)
                <a href="{{ $compareWith ? route('players.versus', [$compareWith, $player->uuid]) : route('players.show', $player->uuid) }}" class="search-hit" wire:key="hit-p-{{ $player->uuid }}">
                    <x-flag :code="$player->nationality" />
                    <span class="min-w-0 flex-1 truncate font-semibold">{{ $player->name }}</span>
                    <span class="font-mono text-[11px] text-subtle">{{ $compareWith ? 'compare' : $player->auth_id }}</span>
                </a>
            @empty
            @endforelse

            @foreach ($this->maps as $map)
                <a href="{{ route('maps.show', $map->uuid) }}" class="search-hit" wire:key="hit-m-{{ $map->uuid }}">
                    <x-icon name="map" class="text-accent" />
                    <span class="min-w-0 flex-1 truncate font-semibold">{{ $map->name }}</span>
                    <span class="text-[11px] text-subtle">map</span>
                </a>
            @endforeach

            @if ($this->players->isEmpty() && $this->maps->isEmpty())
                <p class="px-3 py-4 text-center text-[12px] text-subtle">Nothing found for “{{ $q }}”.</p>
            @endif
        </div>
    @endif
</div>
