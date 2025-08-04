<table class="w-full table-auto text-left mb-4">
    <thead>
        <tr class="bg-gray-800 text-white">
            <th class="px-4 py-2">Name</th>
            <th class="px-4 py-2">STEAMID</th>
            <th class="px-4 py-2">UUID</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($players as $player)
            <tr>
                <td class="px-4 py-2">
                    <a href="{{ route('players.show', $player->uuid) }}" class="text-blue-400 hover:underline">
                        {{ $player->name }}
                    </a>
                </td>
                <td class="px-4 py-2">{{ $player->auth_id }}</td>
                <td class="px-4 py-2">{{ $player->uuid }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="px-4 py-2 text-gray-500">No players found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $players->links() }}
