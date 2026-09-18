<x-ui.panel flush>
    <x-ui.table min="560px">
        <thead>
            <tr>
                <th>Map</th>
                <th>Category</th>
                <th>Player</th>
                <th class="text-right">Time</th>
                <th class="text-right">Replay</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($replays as $replay)
                <tr>
                    <td>
                        <a href="{{ route('maps.show', $replay->map_uuid) }}" class="font-medium hover:text-accent">
                            {{ $replay->map_name }}
                        </a>
                    </td>
                    <td><x-ui.pill>{{ $replay->category_name }}</x-ui.pill></td>
                    <td>
                        <a href="{{ route('players.show', $replay->user_uuid) }}" class="link">{{ $replay->user_name }}</a>
                    </td>
                    <td class="time text-right text-gold">@runtime($replay->time)</td>
                    <td class="text-right">
                        <a href="{{ route('replays.show', [$replay->map_uuid, $replay->category_id]) }}" class="btn btn-primary btn-sm">
                            Watch
                        </a>
                    </td>
                </tr>
            @empty
                <x-ui.empty :colspan="5" message="No replays match that search." />
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.panel>

<div class="mt-4">
    {{ $replays->links('components.ui.pagination') }}
</div>
