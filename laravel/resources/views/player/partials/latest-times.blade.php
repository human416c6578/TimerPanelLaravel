<x-ui.panel flush>
    <x-ui.table min="520px">
        <thead>
            <tr>
                <th class="sortable w-20" data-sort="Rank">Rank <span data-caret></span></th>
                <th class="sortable" data-sort="MapName">Map <span data-caret></span></th>
                <th class="sortable" data-sort="CategoryName">Category <span data-caret></span></th>
                <th class="text-right">Time</th>
                <th class="sortable hidden text-right sm:table-cell" data-sort="RecordDate">Date <span data-caret></span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($latestTimes as $record)
                <tr>
                    <td>
                        @if ((int) $record->Rank === 1)
                            <a href="{{ route('replays.show', [$record->MapUUID, $record->CategoryId]) }}"
                               class="rank rank-1 gap-1"
                               title="Watch the replay">
                                <svg class="size-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z" />
                                </svg>
                                1
                            </a>
                        @else
                            <x-ui.rank :rank="$record->Rank" />
                        @endif
                    </td>
                    <td>
                        <a class="link" href="{{ route('maps.show', $record->MapUUID) }}">{{ $record->MapName }}</a>
                    </td>
                    <td><x-ui.pill>{{ $record->CategoryName }}</x-ui.pill></td>
                    <td @class(['time text-right', 'text-gold' => (int) $record->Rank === 1])>@runtime($record->Time)</td>
                    <td class="hidden text-right text-muted sm:table-cell">{{ $record->RecordDate }}</td>
                </tr>
            @empty
                <x-ui.empty :colspan="5" message="No records yet." />
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.panel>

<div class="mt-4">
    {{ $latestTimes->links('components.ui.pagination') }}
</div>
