<div class="speed-panel overflow-hidden rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="speed-table-head text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3">Player</th>
                    <th class="px-5 py-3">Steam ID</th>
                    <th class="px-5 py-3">UUID</th>
                    <th class="px-5 py-3 text-right">Profile</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse ($players as $player)
                    <tr class="speed-row transition">
                        <td class="px-5 py-4">
                            <a href="{{ route('players.show', $player->uuid) }}" class="speed-link font-medium">
                                {{ $player->name }}
                            </a>
                        </td>
                        <td class="px-5 py-4 font-mono text-slate-300">{{ $player->auth_id }}</td>
                        <td class="speed-muted px-5 py-4 font-mono text-xs">{{ $player->uuid }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('players.show', $player->uuid) }}" class="speed-btn-secondary inline-flex items-center px-3 py-2 text-xs">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-slate-500">No players found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $players->links() }}
</div>
