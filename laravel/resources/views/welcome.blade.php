@use('App\Support\TimeFormat')

@php
    $records = collect($latestTimes)->where('rank', 1)->take(8);
    $hotMax = max(1, $hotMaps->max('runs') ?? 1);
@endphp

<x-layouts.app>
    <div class="space-y-4">
        {{-- Record of the day --}}
        @if ($spotlight)
            @php($spotDelta = $spotlight->best_time !== null ? (int) $spotlight->time - (int) $spotlight->best_time : null)

            <x-cover :name="$spotlight->map_name" class="rounded-[10px] border border-line">
                <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div class="min-w-0">
                        <p class="hud-label !text-white/70">
                            <x-icon name="crown" class="size-3.5 text-gold" />
                            {{ (int) $spotlight->rank === 1 ? 'World record' : 'Latest run' }}
                        </p>

                        <p class="hero-figure mt-3 !text-white">{{ TimeFormat::runtime($spotlight->time) }}</p>

                        <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-[15px] text-white/80">
                            <a href="{{ route('players.show', $spotlight->user_uuid) }}" class="font-bold text-white hover:underline">{{ $spotlight->user_name }}</a>
                            <span>on</span>
                            <a href="{{ route('maps.show', $spotlight->map_uuid) }}" class="font-bold text-white hover:underline">{{ $spotlight->map_name }}</a>
                            <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-[12px] font-semibold text-white">{{ $spotlight->category_name }}</span>
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <a href="{{ route('runs.show', [$spotlight->map_uuid, $spotlight->category_id, $spotlight->user_uuid]) }}" class="btn btn-primary">
                                <x-icon name="play" class="size-3.5" /> Open the run
                            </a>
                            <a href="{{ route('maps.show', $spotlight->map_uuid) }}" class="btn !bg-white/15 !text-white hover:!bg-white/25">Map leaderboard</a>
                        </div>
                    </div>

                    <dl class="grid grid-cols-3 gap-2 text-center lg:w-[21rem]">
                        @foreach ([['Players', $homeStats['players']], ['Maps', $homeStats['maps']], ['Runs', $homeStats['records']]] as [$label, $value])
                            <div class="rounded-lg bg-black/35 px-3 py-3 backdrop-blur">
                                <dd class="big-figure text-white">{{ number_format($value) }}</dd>
                                <dt class="mt-1 text-[11px] font-semibold uppercase tracking-wider text-white/60">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </x-cover>
        @endif

        {{-- New records, as cards --}}
        @if ($records->isNotEmpty())
            <section>
                <div class="mb-2 flex items-center justify-between">
                    <p class="hud-label"><x-icon name="crown" class="size-3.5 text-gold" /> New world records</p>
                    <a href="{{ route('replays.index') }}" class="text-[12px] font-semibold text-muted hover:text-accent">All replays</a>
                </div>

                <div class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-2">
                    @foreach ($records as $record)
                        <a href="{{ route('runs.show', [$record->map_uuid, $record->category_id, $record->user_uuid]) }}"
                           class="group w-56 shrink-0 overflow-hidden rounded-[10px] border border-line bg-surface-1 hover:border-line-strong">
                            <x-cover :name="$record->map_name" class="h-16 px-3 py-2">
                                <p class="truncate text-[13px] font-bold text-white">{{ $record->map_name }}</p>
                                <p class="text-[11px] text-white/70">{{ $record->category_name }}</p>
                            </x-cover>
                            <div class="flex items-end justify-between gap-2 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-[17px] font-bold leading-none tracking-tight group-hover:text-accent">{{ TimeFormat::runtime($record->time) }}</p>
                                    <p class="mt-1 truncate text-[12px] text-muted">{{ $record->user_name }}</p>
                                </div>
                                <x-icon name="crown" class="size-4 text-gold" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_19rem]">
            {{-- Feed --}}
            <x-ui.panel flush>
                <header class="panel-header">
                    <p class="hud-label"><x-icon name="flame" class="size-3.5" /> Latest runs <span class="pill ms-1">24h</span></p>
                    <p class="text-[11px] text-subtle">{{ number_format($homeStats['recent_records']) }} runs · {{ number_format($homeStats['active_players']) }} players</p>
                </header>

                <x-ui.table min="600px">
                    <thead>
                        <tr>
                            <th class="w-20">Rank</th>
                            <th>Player</th>
                            <th>Map</th>
                            <th class="text-right">Time</th>
                            <th class="text-right">Gap</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestTimes as $record)
                            @php($isRecord = (int) $record->rank === 1)

                            <tr @class(['is-record' => $isRecord])>
                                <td>
                                    @if ($isRecord)
                                        <span class="rank rank-1"><x-icon name="crown" class="size-3" /> WR</span>
                                    @else
                                        <x-ui.rank :rank="$record->rank" />
                                    @endif
                                </td>
                                <td><a href="{{ route('players.show', $record->user_uuid) }}" class="link">{{ $record->user_name }}</a></td>
                                <td>
                                    <a href="{{ route('maps.show', $record->map_uuid) }}" class="text-ink hover:text-accent">{{ $record->map_name }}</a>
                                    <span class="ms-1 text-[11px] text-subtle">{{ $record->category_name }}</span>
                                </td>
                                <td class="time time-lg text-right">
                                    <a href="{{ route('runs.show', [$record->map_uuid, $record->category_id, $record->user_uuid]) }}" class="hover:text-accent">{{ TimeFormat::runtime($record->time) }}</a>
                                </td>
                                <td @class(['delta text-right', 'delta-record' => $isRecord])>
                                    {{ TimeFormat::delta($record->best_time !== null ? (int) $record->time - (int) $record->best_time : null) }}
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty :colspan="5" message="No runs in the last 24 hours." />
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.panel>

            <aside class="space-y-4">
                {{-- Close calls --}}
                <x-ui.panel>
                    <header class="panel-header"><p class="hud-label"><x-icon name="target" class="size-3.5" /> Close calls</p></header>

                    <div class="divide-y divide-line">
                        @forelse ($closeCalls as $run)
                            <a href="{{ route('runs.show', [$run->map_uuid, $run->category_id, $run->user_uuid]) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-accent-soft">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold">{{ $run->user_name }}</span>
                                    <span class="block truncate text-[11px] text-subtle">{{ $run->map_name }} · {{ $run->category_name }}</span>
                                </span>
                                <span class="delta !text-ink">{{ TimeFormat::delta((int) $run->time - (int) $run->best_time) }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-5 text-center text-[12px] text-subtle">Nobody within 2% of a record today.</p>
                        @endforelse
                    </div>
                    <p class="border-t border-line px-4 py-2 text-[11px] text-subtle">runs within 2% of the world record</p>
                </x-ui.panel>

                {{-- Hot maps --}}
                <x-ui.panel>
                    <header class="panel-header"><p class="hud-label"><x-icon name="flame" class="size-3.5" /> Hot maps</p></header>

                    <div class="space-y-3 p-4">
                        @forelse ($hotMaps as $map)
                            <a href="{{ route('maps.show', $map->uuid) }}" class="block">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="truncate font-semibold hover:text-accent">{{ $map->name }}</span>
                                    <span class="text-[12px] tabular text-muted">{{ $map->runs }}</span>
                                </span>
                                <span class="meter mt-1.5"><span class="meter-fill" style="width: {{ round($map->runs / $hotMax * 100) }}%"></span></span>
                            </a>
                        @empty
                            <p class="py-2 text-center text-[12px] text-subtle">No activity yet.</p>
                        @endforelse
                    </div>
                </x-ui.panel>

                {{-- Record holders --}}
                <x-ui.panel>
                    <header class="panel-header"><p class="hud-label"><x-icon name="trophy" class="size-3.5" /> Record holders today</p></header>

                    <div class="divide-y divide-line">
                        @forelse ($recordHolders as $index => $holder)
                            <a href="{{ route('players.show', $holder->uuid) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-accent-soft">
                                <span class="rank {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : 'rank-3') }}">{{ $index + 1 }}</span>
                                <span class="min-w-0 flex-1 truncate font-semibold">{{ $holder->name }}</span>
                                <span class="text-[12px] text-muted">{{ $holder->records }} WR</span>
                            </a>
                        @empty
                            <p class="px-4 py-5 text-center text-[12px] text-subtle">No new records today.</p>
                        @endforelse
                    </div>
                </x-ui.panel>
            </aside>
        </div>
    </div>
</x-layouts.app>
