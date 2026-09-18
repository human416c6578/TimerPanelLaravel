<x-layouts.app>
    <div class="space-y-3">
        {{-- Hero --}}
        <section class="panel bracket overflow-hidden">
            <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-center">
                <div>
                    <p class="hud-label">CS-GFX speedrun network</p>

                    <h1 class="display-xl mt-1.5">
                        Track runs. Break <span class="text-accent">records</span>.
                    </h1>

                    <p class="mt-2 max-w-xl text-muted">
                        Every finished run from the bhop and deathrun servers, ranked per map and
                        category, with the replay of every record line attached.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <a href="{{ route('maps.index') }}" class="btn btn-primary">Browse maps</a>
                        <a href="{{ route('leaderboard.index') }}" class="btn btn-ghost">Leaderboards</a>
                        <a href="{{ route('replays.index') }}" class="btn btn-ghost">Replays</a>
                    </div>
                </div>

                {{-- Scoreboard strip --}}
                <dl class="grid grid-cols-2 gap-px border border-line bg-line">
                    @foreach ([
                        'Players' => $homeStats['players'],
                        'Maps' => $homeStats['maps'],
                        'Categories' => $homeStats['categories'],
                        'Runs' => $homeStats['records'],
                    ] as $label => $value)
                        <div class="bg-surface-2 px-3 py-2">
                            <dt class="text-[11px] font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
                            <dd class="mt-0.5 font-mono text-lg font-bold leading-none tabular text-accent">{{ number_format($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- Live feed --}}
        <x-ui.panel flush>
            <header class="panel-header">
                <div class="flex items-center gap-2">
                    <p class="hud-label">Latest runs</p>
                    <span class="pill">last 24h</span>
                </div>

                <a href="{{ route('leaderboard.index') }}" class="text-[11px] font-bold uppercase text-muted hover:text-accent">
                    Leaderboards &raquo;
                </a>
            </header>

            <x-ui.table min="640px">
                <thead>
                    <tr>
                        <th class="w-24">Rank</th>
                        <th class="w-32 text-right">Time</th>
                        <th>Player</th>
                        <th>Map</th>
                        <th>Category</th>
                        <th class="hidden text-right lg:table-cell">Start</th>
                        <th class="hidden text-right lg:table-cell">Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestTimes as $record)
                        <tr>
                            <td>
                                @if ((int) $record->rank === 1)
                                    <a href="{{ route('replays.show', [$record->map_uuid, $record->category_id]) }}"
                                       class="rank rank-1"
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
                            <td @class(['time time-lg text-right', 'text-gold' => (int) $record->rank === 1])>@runtime($record->time)</td>
                            <td><a href="{{ route('players.show', $record->user_uuid) }}" class="link">{{ $record->user_name }}</a></td>
                            <td><a href="{{ route('maps.show', $record->map_uuid) }}" class="link">{{ $record->map_name }}</a></td>
                            <td><x-ui.pill>{{ $record->category_name }}</x-ui.pill></td>
                            <td class="hidden text-right tabular text-muted lg:table-cell">{{ $record->start_speed ?? '—' }}</td>
                            <td class="hidden text-right font-mono text-xs text-subtle lg:table-cell">{{ $record->record_date }}</td>
                        </tr>
                    @empty
                        <x-ui.empty :colspan="7" message="No runs in the last 24 hours." />
                    @endforelse
                </tbody>
            </x-ui.table>

            <footer class="flex items-center justify-between gap-4 border-t border-line bg-surface-2 px-3 py-2 text-[11px] text-subtle">
                <span>{{ number_format($homeStats['recent_records']) }} runs today</span>
                <span>{{ number_format($homeStats['active_players']) }} players active</span>
            </footer>
        </x-ui.panel>
    </div>
</x-layouts.app>
