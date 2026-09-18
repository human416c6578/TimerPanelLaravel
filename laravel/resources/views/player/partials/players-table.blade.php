<x-ui.panel flush>
    <x-ui.table min="520px">
        <thead>
            <tr>
                <th>Player</th>
                <th>Steam ID</th>
                <th class="hidden md:table-cell">UUID</th>
                <th class="text-right">Profile</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($players as $player)
                <tr>
                    <td>
                        <a href="{{ route('players.show', $player->uuid) }}" class="link">{{ $player->name }}</a>
                    </td>
                    <td class="font-mono text-xs text-muted">{{ $player->auth_id }}</td>
                    <td class="hidden font-mono text-xs text-subtle md:table-cell">{{ $player->uuid }}</td>
                    <td class="text-right">
                        <a href="{{ route('players.show', $player->uuid) }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
            @empty
                <x-ui.empty :colspan="4" message="No players match that search." />
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.panel>

<div class="mt-4">
    {{ $players->appends(request()->only('search'))->links('components.ui.pagination') }}
</div>
