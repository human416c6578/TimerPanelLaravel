@use('App\Support\TimeFormat')

<div class="space-y-2">
    <div class="panel flex items-center gap-2 p-2">
        <x-icon name="search" class="text-subtle" />
        <label for="record-search" class="sr-only">Filter records</label>
        <input
            id="record-search"
            type="search"
            wire:model.live.debounce.250ms="search"
            placeholder="Filter by map or category"
            autocomplete="off"
            class="input"
        >
    </div>

    <x-ui.panel flush>
        <x-ui.table min="640px">
            <thead>
                <tr>
                    <x-ui.sort-th column="rank" :sort="$sort" :direction="$direction" label="Rank" class="w-20" />
                    <x-ui.sort-th column="map" :sort="$sort" :direction="$direction" label="Map" />
                    <x-ui.sort-th column="category" :sort="$sort" :direction="$direction" label="Category" />
                    <x-ui.sort-th column="time" :sort="$sort" :direction="$direction" label="Time" align="right" />
                    <x-ui.sort-th column="delta" :sort="$sort" :direction="$direction" label="Gap to WR" align="right" />
                    <x-ui.sort-th column="sync" :sort="$sort" :direction="$direction" label="Sync" align="right" class="hidden sm:table-cell" />
                    <x-ui.sort-th column="date" :sort="$sort" :direction="$direction" label="Date" class="hidden md:table-cell" />
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    @php($isRecord = (int) $run->Rank === 1)

                    <tr wire:key="run-{{ $run->MapUUID }}-{{ $run->CategoryId }}" @class(['is-record' => $isRecord])>
                        <td>
                            @if ($isRecord)
                                <span class="rank rank-1"><x-icon name="crown" class="size-3" />1</span>
                            @else
                                <x-ui.rank :rank="$run->Rank" />
                                @if ($run->Runs)
                                    <span class="ms-1 text-[10px] text-subtle">/{{ $run->Runs }}</span>
                                @endif
                            @endif
                        </td>
                        <td><a class="link" href="{{ route('maps.show', $run->MapUUID) }}">{{ $run->MapName }}</a></td>
                        <td><x-ui.pill>{{ $run->CategoryName }}</x-ui.pill></td>
                        <td class="time time-lg text-right">
                            <a href="{{ route('runs.show', [$run->MapUUID, $run->CategoryId, $userUuid]) }}"
                               @class(['hover:text-accent', 'text-gold' => $isRecord])>{{ TimeFormat::runtime($run->Time) }}</a>
                        </td>
                        <td @class(['delta text-right', 'delta-record' => $isRecord])>{{ TimeFormat::delta($run->Delta) }}</td>
                        <td class="hidden text-right tabular sm:table-cell">
                            {{ $run->Sync === null ? '—' : number_format((float) $run->Sync, 1).'%' }}
                        </td>
                        <td class="hidden font-mono text-[11px] text-subtle md:table-cell">{{ $run->RecordDate }}</td>
                    </tr>
                @empty
                    <x-ui.empty :colspan="7" message="No records match that filter." />
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.panel>

    <div>{{ $runs->links('components.ui.livewire-pagination') }}</div>
</div>
