@use('App\Support\TimeFormat')

@php
    $rank = (int) $run->rank;
    $isRecord = $rank === 1;
    $delta = $run->best_time !== null ? (int) $run->time - (int) $run->best_time : null;
    $topPercent = $totalRuns >= 10 ? max(1, (int) ceil($rank / $totalRuns * 100)) : null;

    // Who this run is measured against: the record, or for the record itself,
    // the run that is chasing it.
    $rival = $isRecord
        ? $window->first(fn ($row) => (int) $row->Rank === 2)
        : $window->first(fn ($row) => (int) $row->Rank === 1) ?? $records->firstWhere('CategoryId', $categoryId);

    $rivalGap = $rival ? (int) $rival->time - (int) $run->time : null;

    $best = fn (string $property, string $direction) => $window
        ->pluck($property)->filter(fn ($value) => $value !== null)
        ->pipe(fn ($values) => $values->isEmpty() ? null : ($direction === 'max' ? $values->max() : $values->min()));
    $bests = ['sync' => $best('sync', 'max'), 'start_speed' => $best('start_speed', 'max'), 'overlaps' => $best('overlaps', 'min')];

    $stat = fn ($value, $decimals = 0, $suffix = '') => $value === null ? '—' : number_format((float) $value, $decimals).$suffix;
    $tiles = [
        ['gauge', 'Sync', $stat($run->sync, 1, '%')],
        ['zap', 'Start speed', $stat($run->start_speed)],
        ['flame', 'Jumps', $stat($run->jumps)],
        ['target', 'Strafes', $stat($run->strafes)],
        ['compare', 'Overlaps', $stat($run->overlaps).($run->overlaps_sd !== null ? ' ±'.number_format((float) $run->overlaps_sd, 2) : '')],
    ];
@endphp

<x-layouts.app :title="$map->name.' · '.$categoryName">
    <div class="space-y-3" x-data="{ tab: 'scoreboard' }">
        <x-ui.breadcrumb :trail="[
            'Maps' => route('maps.index'),
            $map->name => route('maps.show', $map->uuid).'?category='.$categoryId,
            $categoryName => null,
        ]" />

        {{-- The duel --}}
        <section class="panel panel-flush">
            <x-cover :name="$map->name">
                <div class="px-5 pt-4 sm:px-7">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('maps.show', $map->uuid) }}?category={{ $categoryId }}" class="text-[13px] font-bold text-white hover:underline">{{ $map->name }}</a>
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-[12px] font-semibold text-white">{{ $categoryName }}</span>
                        <span class="text-[12px] text-white/60">{{ $run->record_date }}</span>
                    </div>
                </div>

                <div class="grid items-center gap-5 px-5 pb-6 pt-5 sm:px-7 md:grid-cols-[1fr_auto_1fr]">
                    {{-- This run --}}
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 text-[15px] font-bold text-white">
                            <x-flag :code="$user->nationality" />
                            <a href="{{ route('players.show', $user->uuid) }}" class="truncate hover:underline">{{ $user->name }}</a>
                        </p>
                        <p class="hero-figure mt-2 !text-white">{{ TimeFormat::runtime($run->time) }}</p>
                        <p class="mt-3 flex flex-wrap items-center gap-2">
                            @if ($isRecord)
                                <span class="rank rank-1"><x-icon name="crown" class="size-3" /> World record</span>
                            @else
                                <span class="rank !bg-white/15 !text-white">#{{ $rank }}</span>
                            @endif

                            @if ($topPercent !== null)
                                <span class="text-[12px] text-white/70">top {{ $topPercent }}% of {{ number_format($totalRuns) }}</span>
                            @endif
                        </p>
                    </div>

                    {{-- The gap --}}
                    <div class="text-center">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/60">{{ $isRecord ? 'lead' : 'vs WR' }}</p>
                        <p class="mt-1 font-mono text-2xl font-bold text-white">
                            @if ($isRecord)
                                {{ $rivalGap !== null ? '+'.number_format($rivalGap / 1000, 3) : '—' }}
                            @else
                                {{ $delta !== null ? TimeFormat::delta($delta) : '—' }}
                            @endif
                        </p>
                    </div>

                    {{-- The rival --}}
                    <div class="min-w-0 md:text-right">
                        @if ($rival)
                            @php($rivalName = $rival->UserName)
                            @php($rivalUuid = $rival->UserUUID)

                            <p class="truncate text-[15px] font-bold text-white md:justify-end">
                                <a href="{{ route('players.show', $rivalUuid) }}" class="hover:underline">{{ $rivalName }}</a>
                            </p>
                            <p class="big-figure mt-2 !text-white/85">{{ TimeFormat::runtime($rival->time) }}</p>
                            <p class="mt-3 text-[12px] text-white/70">
                                {{ $isRecord ? 'runner-up' : 'world record' }} ·
                                <a href="{{ route('runs.show', [$map->uuid, $categoryId, $rivalUuid]) }}" class="font-semibold text-white hover:underline">open run</a>
                            </p>
                        @else
                            <p class="text-[13px] text-white/60">Nobody else has run this yet.</p>
                        @endif
                    </div>
                </div>
            </x-cover>

            @if (count($chips))
                <div class="flex flex-wrap items-center gap-1.5 px-4 py-2.5">
                    <span class="me-1 text-[11px] font-bold uppercase tracking-widest text-subtle">Ruleset</span>
                    @foreach ($chips as $chip)
                        <dl class="rule"><dt>{{ $chip['label'] }}</dt><dd>{{ $chip['value'] }}</dd></dl>
                    @endforeach
                </div>
            @endif

            <div class="tabs px-2">
                <button type="button" class="tab" :class="tab === 'scoreboard' && 'is-active'" x-on:click="tab = 'scoreboard'">Scoreboard</button>
                <button type="button" class="tab" :class="tab === 'stats' && 'is-active'" x-on:click="tab = 'stats'">Run stats</button>
                @if ($hasReplay)
                    <button type="button" class="tab" :class="tab === 'replay' && 'is-active'" x-on:click="tab = 'replay'"><x-icon name="play" class="size-3.5" /> Replay</button>
                @endif
                <button type="button" class="tab" :class="tab === 'records' && 'is-active'" x-on:click="tab = 'records'">Other categories</button>
            </div>
        </section>

        {{-- Scoreboard: the run and the ones around it --}}
        <section x-show="tab === 'scoreboard'" class="panel panel-flush">
            <x-ui.table min="560px">
                <thead>
                    <tr>
                        <th class="w-16">#</th>
                        <th>Player</th>
                        <th class="text-right">Time</th>
                        <th class="text-right">Gap</th>
                        <th class="text-right">Sync</th>
                        <th class="hidden text-right sm:table-cell">Strafes</th>
                        <th class="hidden text-right sm:table-cell">Jumps</th>
                        <th class="hidden text-right md:table-cell">Start</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($window as $near)
                        @php($mine = $near->UserUUID === $user->uuid)

                        <tr @class(['is-record' => (int) $near->Rank === 1, 'is-mine' => $mine])>
                            <td><x-ui.rank :rank="$near->Rank" /></td>
                            <td>
                                <a href="{{ route('runs.show', [$map->uuid, $categoryId, $near->UserUUID]) }}" @class(['link', 'text-accent' => $mine])>{{ $near->UserName }}</a>
                                @if ($mine)<span class="ms-1 text-[10px] font-bold uppercase tracking-wider text-accent">this run</span>@endif
                            </td>
                            <td class="time time-lg text-right">{{ TimeFormat::runtime($near->time) }}</td>
                            <td @class(['delta text-right', 'delta-record' => (int) $near->Rank === 1])>{{ TimeFormat::delta($near->Delta) }}</td>
                            <td class="text-right"><x-ui.stat-cell :value="$near->sync" :best="$bests['sync']" :decimals="1" suffix="%" /></td>
                            <td class="hidden text-right sm:table-cell"><x-ui.stat-cell :value="$near->strafes" /></td>
                            <td class="hidden text-right sm:table-cell"><x-ui.stat-cell :value="$near->jumps" /></td>
                            <td class="hidden text-right md:table-cell"><x-ui.stat-cell :value="$near->start_speed" :best="$bests['start_speed']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="flex items-center justify-between border-t border-line px-4 py-2.5 text-[12px]">
                <span class="text-subtle">Boxed cells lead among these runs.</span>
                <a href="{{ route('maps.show', $map->uuid) }}?category={{ $categoryId }}&vs[0]={{ $rival->UserUUID ?? '' }}&vs[1]={{ $user->uuid }}"
                   @class(['btn btn-sm', 'hidden' => ! $rival])>
                    <x-icon name="compare" class="size-3.5" /> Compare head to head
                </a>
            </div>
        </section>

        {{-- Run stats --}}
        <section x-show="tab === 'stats'" x-cloak class="space-y-3">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($tiles as [$icon, $label, $value])
                    <div class="panel p-4">
                        <p class="hud-label"><x-icon :name="$icon" class="size-3.5" /> {{ $label }}</p>
                        <p @class(['big-figure mt-2', 'text-subtle' => $value === '—'])>{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            @if ($run->sync === null && $run->strafes === null && $run->jumps === null)
                <p class="text-[12px] text-subtle">The timer did not record statistics for this run; it predates them.</p>
            @endif
        </section>

        @if ($hasReplay)
            <section x-show="tab === 'replay'" x-cloak class="panel panel-flush">
                <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-2.5">
                    <p class="hud-label"><x-icon name="play" class="size-3.5" /> Replay · {{ $map->name }} · {{ $categoryName }}</p>
                    <button type="button" id="downloadBtn" class="btn btn-sm"><x-icon name="download" class="size-3.5" /> Download .rec</button>
                </div>

                <div class="bg-black p-2">
                    <div id="hlv-target" class="h-[380px] overflow-hidden rounded-md sm:h-[500px]"></div>
                </div>
            </section>
        @endif

        {{-- The record on every category of this map --}}
        <section x-show="tab === 'records'" x-cloak class="panel panel-flush">
            <div class="divide-y divide-line">
                @forelse ($records as $record)
                    <a href="{{ route('runs.show', [$map->uuid, $record->CategoryId, $record->UserUUID]) }}"
                       @class(['flex items-center gap-3 px-4 py-3 hover:bg-accent-soft', 'bg-accent-soft' => (int) $record->CategoryId === $categoryId])>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ $record->CategoryName }}</span>
                            <span class="block text-[12px] text-muted">{{ $record->UserName }}</span>
                        </span>
                        <span class="time time-lg">{{ TimeFormat::runtime($record->time) }}</span>
                        <x-icon name="crown" class="size-4 text-gold" />
                    </a>
                @empty
                    <p class="px-4 py-8 text-center text-[12px] text-subtle">No records on this map yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if ($hasReplay)
        @push('scripts')
            <script src="{{ asset('js/hlviewer.min.js') }}"></script>
            <script>
                // The viewer needs a visible container, so it starts the first time the Replay tab opens.
                let replayStarted = false;

                const startReplay = async () => {
                    if (replayStarted) {
                        return;
                    }

                    replayStarted = true;

                    const urlBhop = @json(config('replays.fastdl.bhop'));
                    const urlDr = @json(config('replays.fastdl.deathrun'));
                    const downloadUrl = @json(rtrim(config('replays.download_url'), '/'));
                    const mapName = @json($map->name);
                    const categoryName = @json($categoryName);
                    const resourceUrl = mapName.includes('deathrun') ? urlDr : urlBhop;

                    const paths = {
                        base: `/proxy?url=${resourceUrl}`,
                        replays: `/proxy?url=${downloadUrl}`,
                        maps: `/proxy?url=${resourceUrl}maps`,
                        wads: `/proxy?url=${resourceUrl}`,
                        skies: `/proxy?url=${resourceUrl}gfx/env`,
                        sounds: `/proxy?url=${resourceUrl}sound`,
                    };

                    const viewer = HLViewer.init('#hlv-target', { paths });
                    await viewer.load(`${mapName}.bsp`);
                    await viewer.load(`${mapName}/[${categoryName}].rec`);

                    document.getElementById('downloadBtn').addEventListener('click', () => {
                        const link = document.createElement('a');
                        link.href = `${downloadUrl}/${mapName}/[${categoryName}].rec`;
                        link.download = `${mapName} - [${categoryName}].rec`;
                        link.click();
                        link.remove();
                    });
                };

                window.addEventListener('load', () => {
                    const target = document.getElementById('hlv-target');

                    new IntersectionObserver((entries, observer) => {
                        if (entries.some((entry) => entry.isIntersecting)) {
                            observer.disconnect();
                            startReplay();
                        }
                    }).observe(target);
                });
            </script>
        @endpush
    @endif
</x-layouts.app>
