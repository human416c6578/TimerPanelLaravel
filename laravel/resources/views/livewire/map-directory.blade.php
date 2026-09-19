<div class="space-y-3">
    <div class="panel flex flex-wrap items-center gap-3 p-2.5">
        <div class="search-box max-w-xs flex-1">
            <x-icon name="search" class="text-subtle" />
            <label for="map-search" class="sr-only">Search maps</label>
            <input id="map-search" type="search" wire:model.live.debounce.250ms="search" placeholder="Filter maps" autocomplete="off">
        </div>

        <div class="seg ms-auto">
            @foreach (['all' => 'All', 'bhop' => 'Bhop', 'deathrun' => 'Deathrun'] as $key => $label)
                <button type="button" wire:click="setMode('{{ $key }}')" @class(['is-active' => $mode === $key])>{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
        @forelse ($maps as $map)
            @php($deathrun = str_contains(strtolower($map->name), 'deathrun'))

            <a wire:key="map-{{ $map->uuid }}"
               href="{{ route('maps.show', $map->uuid) }}"
               class="group fx-lift fx-shine block overflow-hidden rounded-[10px] border border-line">
                <x-cover :name="$map->name" class="flex aspect-[16/10] flex-col justify-between p-3">
                    <span class="flex items-center justify-between">
                        <span class="rounded-full bg-black/35 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white/80">
                            {{ $deathrun ? 'Deathrun' : 'Bhop' }}
                        </span>
                        <x-icon :name="$deathrun ? 'flame' : 'zap'" class="size-4 text-white/60" />
                    </span>

                    <span class="block truncate text-[15px] font-bold text-white group-hover:underline">{{ $map->name }}</span>
                </x-cover>
            </a>
        @empty
            <p class="panel col-span-full px-4 py-12 text-center text-subtle">No maps match that filter.</p>
        @endforelse
    </div>

    <div>{{ $maps->links('components.ui.livewire-pagination') }}</div>
</div>
