<div class="space-y-2">
    <div class="panel flex items-center gap-2 p-2">
        <x-icon name="search" class="text-subtle" />
        <label for="replay-search" class="sr-only">Search replays</label>
        <input
            id="replay-search"
            type="search"
            wire:model.live.debounce.250ms="search"
            placeholder="Map, category or player"
            autocomplete="off"
            class="input"
        >
        <span wire:loading class="shrink-0 text-[11px] text-subtle">searching…</span>
    </div>

    <x-ui.panel flush>
        <x-ui.table min="560px">
            <thead>
                <tr>
                    <x-ui.sort-th column="map" :sort="$sort" :direction="$direction" label="Map" />
                    <x-ui.sort-th column="category" :sort="$sort" :direction="$direction" label="Category" />
                    <x-ui.sort-th column="player" :sort="$sort" :direction="$direction" label="Record holder" />
                    <x-ui.sort-th column="time" :sort="$sort" :direction="$direction" label="Time" align="right" />
                    <th class="text-right">Replay</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($replays as $replay)
                    <tr wire:key="replay-{{ $replay->map_uuid }}-{{ $replay->category_id }}">
                        <td><a href="{{ route('maps.show', $replay->map_uuid) }}" class="text-ink hover:text-accent">{{ $replay->map_name }}</a></td>
                        <td><x-ui.pill>{{ $replay->category_name }}</x-ui.pill></td>
                        <td><a href="{{ route('players.show', $replay->user_uuid) }}" class="link">{{ $replay->user_name }}</a></td>
                        <td class="time text-right text-gold">@runtime($replay->time)</td>
                        <td class="text-right">
                            <x-ui.play-button :href="route('runs.show', [$replay->map_uuid, $replay->category_id, $replay->user_uuid]).'?tab=replay'" />
                        </td>
                    </tr>
                @empty
                    <x-ui.empty :colspan="5" message="No replays match that search." />
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.panel>

    <div>{{ $replays->links('components.ui.livewire-pagination') }}</div>
</div>
