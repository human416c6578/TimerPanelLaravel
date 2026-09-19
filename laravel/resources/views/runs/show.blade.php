@use('App\Support\TimeFormat')

@php
    $rank = (int) $run->rank;
    $delta = $run->best_time !== null ? (int) $run->time - (int) $run->best_time : null;
    $isRecord = $rank === 1;
    $stat = fn ($value, $decimals = 0, $suffix = '') => $value === null ? '—' : number_format((float) $value, $decimals).$suffix;

    $tiles = [
        ['icon' => 'gauge', 'label' => 'Sync', 'value' => $stat($run->sync, 1, '%')],
        ['icon' => 'zap', 'label' => 'Start speed', 'value' => $stat($run->start_speed)],
        ['icon' => 'flame', 'label' => 'Jumps', 'value' => $stat($run->jumps)],
        ['icon' => 'target', 'label' => 'Strafes', 'value' => $stat($run->strafes)],
        ['icon' => 'compare', 'label' => 'Overlaps', 'value' => $stat($run->overlaps).($run->overlaps_sd !== null ? ' ±'.number_format((float) $run->overlaps_sd, 2) : '')],
    ];
@endphp

<x-layouts.app :title="$map->name.' · '.$categoryName">
    <div class="space-y-2">
        <x-ui.breadcrumb :trail="[
            'Maps' => route('maps.index'),
            $map->name => route('maps.show', $map->uuid),
            $categoryName => null,
        ]" />

        {{-- The run --}}
        <section class="panel bracket">
            <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div class="min-w-0">
                    <p class="hud-label"><x-icon name="timer" class="size-3.5" /> Run</p>

                    <p class="time-hero mt-2 {{ $isRecord ? 'text-gold' : '' }}">{{ TimeFormat::runtime($run->time) }}</p>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if ($isRecord)
                            <span class="rank rank-1"><x-icon name="crown" class="size-3" /> World record</span>
                        @else
                            <x-ui.rank :rank="$rank" />
                            <span class="text-[12px] text-subtle">of {{ number_format($totalRuns) }}</span>
                        @endif

                        @if ($delta !== null && $delta > 0)
                            <span class="delta">{{ TimeFormat::delta($delta) }} from the record</span>
                        @endif
                    </div>

                    <p class="mt-3 flex flex-wrap items-center gap-2 text-[13px]">
                        <x-flag :code="$user->nationality" />
                        <a href="{{ route('players.show', $user->uuid) }}" class="link">{{ $user->name }}</a>
                        <span class="text-subtle">on</span>
                        <a href="{{ route('maps.show', $map->uuid) }}" class="text-ink hover:text-accent">{{ $map->name }}</a>
                        <x-ui.pill accent>{{ $categoryName }}</x-ui.pill>
                    </p>

                    <p class="mt-1 font-mono text-[11px] text-subtle">{{ $run->record_date }}</p>
                </div>

                <div class="flex flex-wrap gap-2 lg:flex-col">
                    @if ($hasReplay)
                        <button type="button" id="downloadBtn" class="btn btn-primary"><x-icon name="download" /> Download replay</button>
                    @elseif ($run->best_user_uuid)
                        <a href="{{ route('runs.show', [$map->uuid, $categoryId, $run->best_user_uuid]) }}" class="btn">
                            <x-icon name="play" /> Watch the record
                        </a>
                    @endif
                </div>
            </div>

            @if (count($chips))
                <div class="flex flex-wrap items-center gap-1.5 border-t border-line px-3 py-2">
                    <span class="me-1 text-[11px] uppercase tracking-widest text-subtle">Ruleset</span>
                    @foreach ($chips as $chip)
                        <dl class="rule"><dt>{{ $chip['label'] }}</dt><dd>{{ $chip['value'] }}</dd></dl>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- What the timer recorded --}}
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($tiles as $tile)
                <div class="panel px-3 py-2.5">
                    <p class="flex items-center gap-1.5 text-[11px] uppercase tracking-widest text-subtle">
                        <x-icon :name="$tile['icon']" class="size-3.5" /> {{ $tile['label'] }}
                    </p>
                    <p @class(['mt-1 font-mono text-lg tabular', 'text-ink' => $tile['value'] !== '—', 'text-subtle' => $tile['value'] === '—'])>{{ $tile['value'] }}</p>
                </div>
            @endforeach
        </div>

        @if ($run->sync === null && $run->strafes === null && $run->jumps === null)
            <p class="px-1 text-[11px] text-subtle">The timer did not record run statistics for this run — it predates them.</p>
        @endif

        <div class="grid gap-2 lg:grid-cols-[minmax(0,1fr)_16rem]">
            <div class="space-y-2">
                {{-- The replay --}}
                @if ($hasReplay)
                    <x-ui.panel flush>
                        <header class="panel-header">
                            <p class="hud-label"><x-icon name="play" class="size-3.5" /> Replay</p>
                            <p class="font-mono text-[11px] text-subtle">{{ $map->name }} · {{ $categoryName }}</p>
                        </header>

                        <div class="bg-black p-2">
                            <div id="hlv-target" class="h-[380px] overflow-hidden rounded-sm sm:h-[480px]"></div>
                        </div>
                    </x-ui.panel>
                @endif

                {{-- Where this run sits --}}
                <x-ui.panel flush>
                    <header class="panel-header">
                        <p class="hud-label"><x-icon name="bar-chart" class="size-3.5" /> Standing</p>
                        <a href="{{ route('maps.show', $map->uuid) }}?category={{ $categoryId }}" class="text-[11px] text-muted hover:text-accent">Full board &raquo;</a>
                    </header>

                    <x-ui.table min="420px">
                        <thead>
                            <tr>
                                <th class="w-16">#</th>
                                <th>Player</th>
                                <th class="text-right">Time</th>
                                <th class="text-right">Gap</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($neighbours as $near)
                                @php($mine = $near->user_uuid === $user->uuid)

                                <tr @class(['is-record' => (int) $near->rank === 1, 'bg-accent-soft' => $mine])>
                                    <td><x-ui.rank :rank="$near->rank" /></td>
                                    <td>
                                        <a href="{{ route('runs.show', [$map->uuid, $categoryId, $near->user_uuid]) }}"
                                           @class(['link', 'font-bold' => $mine])>{{ $near->user_name }}</a>
                                    </td>
                                    <td class="time text-right">{{ TimeFormat::runtime($near->time) }}</td>
                                    <td @class(['delta text-right', 'delta-record' => (int) $near->rank === 1])>
                                        {{ TimeFormat::delta($run->best_time !== null ? (int) $near->time - (int) $run->best_time : null) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                </x-ui.panel>
            </div>

            {{-- The rest of the map --}}
            <x-ui.panel flush>
                <header class="panel-header"><p class="hud-label"><x-icon name="trophy" class="size-3.5" /> Records here</p></header>

                <div class="flex flex-col">
                    @forelse ($records as $record)
                        <a href="{{ route('runs.show', [$map->uuid, $record->CategoryId, $record->UserUUID]) }}"
                           @class(['side-link !items-start !justify-between', 'is-active' => (int) $record->CategoryId === $categoryId])>
                            <span class="min-w-0">
                                <span class="block truncate">{{ $record->CategoryName }}</span>
                                <span class="block truncate text-[11px] text-subtle">{{ $record->UserName }}</span>
                            </span>
                            <span class="time shrink-0 text-[11px] text-gold">{{ TimeFormat::runtime($record->time) }}</span>
                        </a>
                    @empty
                        <p class="px-3 py-4 text-center text-[11px] text-subtle">No records on this map yet.</p>
                    @endforelse
                </div>
            </x-ui.panel>
        </div>
    </div>

    @if ($hasReplay)
        @push('scripts')
            <script src="{{ asset('js/hlviewer.min.js') }}"></script>
            <script>
                window.addEventListener('load', async () => {
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
                });
            </script>
        @endpush
    @endif
</x-layouts.app>
