@extends('layouts.app')

@section('title', 'Maps')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="speed-eyebrow text-sm font-bold">Course library</p>
            <h1 class="text-3xl font-bold text-white">Maps</h1>
            <p class="speed-muted mt-2 text-sm">{{ $maps->count() }} routes available for record hunting.</p>
        </div>

        <div class="w-full md:max-w-md">
            <label for="mapSearch" class="sr-only">Search maps</label>
            <input
                type="search"
                id="mapSearch"
                placeholder="Search maps..."
                class="speed-input w-full rounded-lg px-4 py-3 text-sm"
            >
        </div>
    </div>

    <div id="mapList" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($maps as $map)
            @php
                $mode = str_contains(strtolower($map->name), 'deathrun') ? 'Deathrun' : 'Bhop';
            @endphp
            <a
                href="{{ route('maps.show', $map->uuid) }}"
                class="map-card speed-card group rounded-lg p-4 transition"
                data-map-name="{{ strtolower($map->name) }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-white group-hover:text-amber-200">{{ $map->name }}</h2>
                        <p class="speed-muted mt-2 truncate font-mono text-xs">{{ $map->uuid }}</p>
                    </div>
                    <span class="speed-pill shrink-0 rounded px-2 py-1 text-xs font-semibold">{{ $mode }}</span>
                </div>
            </a>
        @empty
            <p class="text-slate-500">No maps found.</p>
        @endforelse
    </div>

    <div id="emptyMaps" class="speed-panel hidden rounded-lg px-5 py-10 text-center text-slate-500">
        No maps match your search.
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('mapSearch');
        const cards = document.querySelectorAll('.map-card');
        const emptyState = document.getElementById('emptyMaps');

        input.addEventListener('input', () => {
            const search = input.value.trim().toLowerCase();
            let visible = 0;

            cards.forEach(card => {
                const isMatch = card.dataset.mapName.includes(search);
                card.classList.toggle('hidden', !isMatch);
                visible += isMatch ? 1 : 0;
            });

            emptyState.classList.toggle('hidden', visible !== 0);
        });
    });
</script>
@endsection
