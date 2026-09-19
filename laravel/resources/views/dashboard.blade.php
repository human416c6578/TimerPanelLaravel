<x-layouts.app :title="__('Dashboard')">
    <div class="space-y-3">
        <x-ui.page-header
            eyebrow="Admin panel"
            title="Server overview"
            description="Monitor recent activity, refresh the leaderboards and jump into the moderation screens."
        >
            <x-slot:actions>
                <a href="{{ route('home') }}" class="btn btn-ghost">View site</a>
                <a href="{{ route('players.index') }}" class="btn btn-ghost">Players</a>
                <a href="{{ route('maps.index') }}" class="btn btn-primary">Maps</a>
            </x-slot:actions>
        </x-ui.page-header>

        <dl class="grid grid-cols-2 gap-px border border-line bg-line sm:grid-cols-4">
            @foreach ([
                'Players' => $stats['players'],
                'Maps' => $stats['maps'],
                'Total runs' => $stats['times'],
                'Ranked rows' => $stats['ranked_times'],
            ] as $label => $value)
                <div class="bg-surface-1 px-4 py-3">
                    <dt class="text-[11px] font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
                    <dd class="mt-0.5 font-mono text-lg font-bold leading-none tabular text-accent">{{ number_format($value) }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_15rem]">
            <x-ui.panel flush eyebrow="Latest runs">
                <x-ui.table min="580px">
                    <thead>
                        <tr>
                            <th class="w-20">Rank</th>
                            <th>Player</th>
                            <th>Map</th>
                            <th>Category</th>
                            <th class="text-right">Time</th>
                            <th class="hidden lg:table-cell">Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestRecords as $record)
                            <tr>
                                <td>
                                    @if ((int) $record->rank === 1)
                                        <a href="{{ route('replays.show', [$record->map_uuid, $record->category_id]) }}"
                                           class="rank rank-1 gap-1"
                                           title="Watch the replay">
                                            <svg class="size-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z" />
                                            </svg>
                                            1
                                        </a>
                                    @else
                                        <x-ui.rank :rank="$record->rank" />
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('players.show', $record->user_uuid) }}" class="link">{{ $record->user_name }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('maps.show', $record->map_uuid) }}" class="font-medium hover:text-accent">{{ $record->map_name }}</a>
                                </td>
                                <td><x-ui.pill>{{ $record->category_name }}</x-ui.pill></td>
                                <td @class(['time text-right', 'text-gold' => (int) $record->rank === 1])>@runtime($record->time)</td>
                                <td class="hidden text-muted lg:table-cell">{{ $record->record_date }}</td>
                            </tr>
                        @empty
                            <x-ui.empty :colspan="6" message="No runs recorded yet." />
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.panel>

            <aside class="space-y-3">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                    <x-ui.stat label="Runs today" :value="number_format($stats['recent_times'])" />
                    <x-ui.stat label="Active players" :value="number_format($stats['active_players'])" />
                    <x-ui.stat label="Categories" :value="number_format($stats['categories'])" />
                    <x-ui.stat label="Maps with no runs" :value="number_format($stats['empty_maps'] ?? 0)" hint="nobody has finished them yet" />
                </div>

                <x-ui.panel eyebrow="Maintenance" flush>
                    <div class="space-y-2 p-3">
                        <form method="POST" action="{{ route('dashboard.leaderboards.refresh') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full">Refresh leaderboards</button>
                        </form>

                        <form method="POST" action="{{ route('dashboard.cache.clear') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost w-full">Clear cache</button>
                        </form>
                    </div>
                </x-ui.panel>

                <x-ui.panel eyebrow="Moderation" flush>
                    <div class="grid gap-2 p-3 text-sm">
                        <a href="{{ route('players.index') }}" class="btn btn-ghost justify-start">Find player times</a>
                        <a href="{{ route('maps.index') }}" class="btn btn-ghost justify-start">Open map records</a>
                        <a href="{{ route('replays.index') }}" class="btn btn-ghost justify-start">Review replays</a>
                    </div>
                </x-ui.panel>
            </aside>
        </div>
    </div>
</x-layouts.app>
