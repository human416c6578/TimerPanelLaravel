<div class="speed-panel overflow-hidden rounded-lg transition-opacity">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="speed-table-head text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3">Map</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Player</th>
                    <th class="px-5 py-3">Time</th>
                    <th class="px-5 py-3 text-right">Replay</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse ($replays as $replay)
                    <tr class="speed-row transition">
                        <td class="px-5 py-4">
                            <a href="{{ route('maps.show', $replay->map_uuid) }}" class="font-medium text-white hover:text-amber-200">
                                {{ $replay->map_name }}
                            </a>
                        </td>
                        <td class="px-5 py-4">
                            <span class="speed-pill rounded px-2.5 py-1 text-xs font-semibold">
                                {{ $replay->category_name }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('players.show', $replay->user_uuid) }}" class="speed-link">
                                {{ $replay->user_name }}
                            </a>
                        </td>
                        <td class="px-5 py-4 font-mono text-amber-200">{{ $replay->time }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('replays.show', [$replay->map_uuid, $replay->category_id]) }}" class="speed-btn-danger inline-flex items-center px-3 py-2 text-xs">
                                Watch
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">No replays found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $replays->links() }}
</div>
