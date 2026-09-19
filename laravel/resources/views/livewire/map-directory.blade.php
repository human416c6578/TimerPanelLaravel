<div class="space-y-2">
    <div class="panel flex flex-wrap items-center gap-2 p-2">
        <x-icon name="search" class="text-subtle" />
        <label for="map-search" class="sr-only">Search maps</label>
        <input
            id="map-search"
            type="search"
            wire:model.live.debounce.250ms="search"
            placeholder="Filter maps"
            autocomplete="off"
            class="input max-w-xs"
        >

        <div class="tabs ms-auto !border-b-0 bg-transparent">
            @foreach (['all' => 'All', 'bhop' => 'Bhop', 'deathrun' => 'Deathrun'] as $key => $label)
                <button type="button" wire:click="setMode('{{ $key }}')" @class(['tab', 'is-active' => $mode === $key])>{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($maps as $map)
            @php($deathrun = str_contains(strtolower($map->name), 'deathrun'))

            <a
                wire:key="map-{{ $map->uuid }}"
                href="{{ route('maps.show', $map->uuid) }}"
                class="panel group flex items-center gap-3 px-3 py-2.5 hover:brightness-125"
            >
                <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-black/25 text-accent">
                    <x-icon :name="$deathrun ? 'flame' : 'zap'" class="size-4" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13px] text-ink group-hover:text-accent">{{ $map->name }}</span>
                    <span class="block text-[11px] text-subtle">{{ $deathrun ? 'Deathrun' : 'Bhop' }}</span>
                </span>

                <x-icon name="play" class="size-3 text-subtle group-hover:text-accent" />
            </a>
        @empty
            <p class="panel col-span-full px-4 py-10 text-center text-subtle">No maps match that filter.</p>
        @endforelse
    </div>

    <div>{{ $maps->links('components.ui.livewire-pagination') }}</div>
</div>
