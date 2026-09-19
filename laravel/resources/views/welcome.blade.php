@use('App\Support\TimeFormat')

<x-layouts.app>
    <div class="space-y-2">
        {{-- Hero --}}
        <section class="panel bracket">
            <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-center">
                <div>
                    <p class="hud-label"><x-icon name="zap" class="size-3.5" /> CS-GFX speedrun network</p>

                    <h1 class="display-xl mt-1.5">Track runs. Break <span class="text-accent">records</span>.</h1>

                    <p class="mt-2 max-w-xl text-muted">
                        Every finished run from the bhop and deathrun servers, ranked per map and
                        category, with sync, strafes and the replay of every record line.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <a href="{{ route('maps.index') }}" class="btn btn-primary"><x-icon name="map" /> Browse maps</a>
                        <a href="{{ route('leaderboard.index') }}" class="btn"><x-icon name="trophy" /> Rankings</a>
                        <a href="{{ route('replays.index') }}" class="btn"><x-icon name="play" /> Replays</a>
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-2">
                    @foreach ([
                        ['Players', $homeStats['players'], 'users'],
                        ['Maps', $homeStats['maps'], 'map'],
                        ['Categories', $homeStats['categories'], 'flag'],
                        ['Runs', $homeStats['records'], 'timer'],
                    ] as [$label, $value, $icon])
                        <div class="rounded-sm bg-black/25 px-3 py-2">
                            <dt class="flex items-center gap-1.5 text-[11px] uppercase tracking-widest text-subtle">
                                <x-icon :name="$icon" class="size-3.5" /> {{ $label }}
                            </dt>
                            <dd class="mt-0.5 font-mono text-lg tabular text-accent">{{ number_format($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <div class="grid gap-2 xl:grid-cols-[minmax(0,1fr)_14rem]">
            {{-- Live feed --}}
            <x-ui.panel flush>
                <header class="panel-header">
                    <div class="flex items-center gap-2">
                        <p class="hud-label"><x-icon name="flame" class="size-3.5" /> Latest runs</p>
                        <span class="pill">last 24h</span>
                    </div>

                    <a href="{{ route('leaderboard.index') }}" class="text-[11px] text-muted hover:text-accent">Rankings &raquo;</a>
                </header>

                <x-ui.table min="620px">
                    <thead>
                        <tr>
                            <th class="w-24">Rank</th>
                            <th class="text-right">Time</th>
                            <th class="text-right">Gap</th>
                            <th>Player</th>
                            <th>Map</th>
                            <th>Category</th>
                            <th class="hidden text-right lg:table-cell">Start</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestTimes as $record)
                            @php($isRecord = (int) $record->rank === 1)

                            <tr @class(['is-record' => $isRecord])>
                                <td>
                                    @if ($isRecord)
                                        <span class="rank rank-1" title="A new world record"><x-icon name="crown" class="size-3" /> WR</span>
                                    @else
                                        <x-ui.rank :rank="$record->rank" />
                                    @endif
                                </td>
                                <td class="time time-lg text-right">
                                    <a href="{{ route('runs.show', [$record->map_uuid, $record->category_id, $record->user_uuid]) }}"
                                       @class(['hover:text-accent', 'text-gold' => $isRecord])>{{ TimeFormat::runtime($record->time) }}</a>
                                </td>
                                <td @class(['delta text-right', 'delta-record' => $isRecord])>
                                    {{ TimeFormat::delta($record->best_time !== null ? (int) $record->time - (int) $record->best_time : null) }}
                                </td>
                                <td><a href="{{ route('players.show', $record->user_uuid) }}" class="link">{{ $record->user_name }}</a></td>
                                <td><a href="{{ route('maps.show', $record->map_uuid) }}" class="text-ink hover:text-accent">{{ $record->map_name }}</a></td>
                                <td><x-ui.pill>{{ $record->category_name }}</x-ui.pill></td>
                                <td class="hidden text-right tabular text-muted lg:table-cell">{{ $record->start_speed ?? '—' }}</td>
                            </tr>
                        @empty
                            <x-ui.empty :colspan="7" message="No runs in the last 24 hours." />
                        @endforelse
                    </tbody>
                </x-ui.table>

                <footer class="flex items-center justify-between gap-4 border-t border-line px-3 py-2 text-[11px] text-subtle">
                    <span>{{ number_format($homeStats['recent_records']) }} runs today</span>
                    <span>{{ number_format($homeStats['active_players']) }} players active</span>
                </footer>
            </x-ui.panel>

            {{-- Record holders --}}
            <x-ui.panel flush class="h-max">
                <header class="panel-header">
                    <p class="hud-label"><x-icon name="trophy" class="size-3.5" /> Record holders</p>
                </header>

                <div class="flex flex-col">
                    @forelse ($recordHolders as $index => $holder)
                        <a href="{{ route('players.show', $holder->uuid) }}" class="side-link !py-2.5">
                            <x-icon name="crown" @class(['size-4', 'text-gold' => $index === 0, 'text-silver' => $index === 1, 'text-bronze' => $index === 2]) />
                            <span class="min-w-0 flex-1 truncate text-ink">{{ $holder->name }}</span>
                            <span class="font-mono text-[11px] text-muted">{{ $holder->records }} WR</span>
                        </a>
                    @empty
                        <p class="px-3 py-4 text-center text-[11px] text-subtle">No new records today.</p>
                    @endforelse
                </div>

                <p class="border-t border-line px-3 py-2 text-[10px] text-subtle">most records set in the last 24 hours</p>
            </x-ui.panel>
        </div>
    </div>
</x-layouts.app>
