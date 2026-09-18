<x-layouts.app title="Maps">
    @php($names = $maps->map(fn ($map) => strtolower($map->name))->values())

    <div class="space-y-3" x-data="{ query: '' }">
        <x-ui.page-header
            eyebrow="Course library"
            title="Maps"
            :description="number_format($maps->count()).' routes on the servers. Pick one and see who owns it.'"
        >
            <x-slot:actions>
                <x-ui.search id="map-search" label="Search maps" placeholder="filter maps…" x-model="query" class="w-full md:w-80" />
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid gap-px border border-line bg-line sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($maps as $map)
                @php($mode = str_contains(strtolower($map->name), 'deathrun') ? 'DR' : 'BH')

                <a
                    href="{{ route('maps.show', $map->uuid) }}"
                    class="group flex items-center gap-2.5 bg-surface-1 px-3 py-2 hover:bg-surface-2"
                    x-show="! query || {{ Js::from(strtolower($map->name)) }}.includes(query.trim().toLowerCase())"
                >
                    <span class="flex size-7 shrink-0 items-center justify-center border border-line bg-surface-2 text-[10px] font-bold text-muted group-hover:text-accent">
                        {{ $mode }}
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[12px] font-bold group-hover:text-accent">{{ $map->name }}</span>
                        <span class="mt-0.5 block truncate font-mono text-[11px] text-subtle">{{ $map->uuid }}</span>
                    </span>

                    <span class="font-mono text-xs text-subtle transition group-hover:text-accent">→</span>
                </a>
            @empty
                <p class="bg-surface-1 px-4 py-12 text-center text-sm text-subtle">No maps found.</p>
            @endforelse
        </div>

        <p
            x-show="query && ! {{ Js::from($names) }}.some((name) => name.includes(query.trim().toLowerCase()))"
            x-cloak
            class="panel px-5 py-12 text-center text-sm text-subtle"
        >
            No maps match that filter.
        </p>
    </div>
</x-layouts.app>
