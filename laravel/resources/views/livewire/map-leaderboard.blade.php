@use('App\Support\TimeFormat')

@php
    $record = $this->record;
    $bests = $this->bests;
    $mode = str_contains(strtolower($mapName), 'deathrun') ? 'Deathrun' : 'Bhop';
    $compared = $this->compared;
    $count = $this->boards->get($this->currentCategory, collect())->count();
    $stat = fn ($value, $decimals = 0, $suffix = '') => $value === null ? '—' : number_format((float) $value, $decimals).$suffix;
@endphp

<div class="space-y-4" x-data="{ pending: null }">
    {{-- Map banner + category tabs --}}
    <section class="panel panel-flush">
        <x-cover :name="$mapName">
            <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-end sm:justify-between sm:p-7">
                <div class="min-w-0">
                    <p class="hud-label !text-white/70"><x-icon name="map" class="size-3.5" /> Map leaderboard</p>
                    <h1 class="display-xl mt-2 break-all !text-white">{{ $mapName }}</h1>

                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-[12px] font-semibold text-white">{{ $mode }}</span>
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-[12px] font-semibold text-white">{{ $this->categories->count() }} {{ Str::plural('category', $this->categories->count()) }}</span>
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-[12px] font-semibold text-white">{{ number_format($count) }} runs</span>
                    </div>
                </div>

                @if ($record)
                    <a href="{{ route('runs.show', [$mapUuid, $record->CategoryId, $record->UserUUID]) }}" class="shrink-0 rounded-lg bg-black/35 px-5 py-3 backdrop-blur hover:bg-black/50 sm:text-right">
                        <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-widest text-gold sm:justify-end">
                            <x-icon name="crown" class="size-3.5" /> World record
                        </p>
                        <p class="hero-figure mt-1.5 !text-white">{{ TimeFormat::runtime($record->time) }}</p>
                        <p class="mt-1.5 text-[13px] text-white/70">by <span class="font-bold text-white">{{ $record->UserName }}</span></p>
                    </a>
                @endif
            </div>
        </x-cover>

        <div class="tabs px-2">
            @foreach ($this->categories as $category)
                <button type="button" wire:click="selectCategory('{{ $category['id'] }}')" @class(['tab', 'is-active' => $category['id'] === $this->currentCategory])>
                    {{ $category['name'] }} <span class="text-[10px] font-semibold opacity-60">{{ $category['runs'] }}</span>
                </button>
            @endforeach
        </div>

        @if (count($this->rules))
            <div class="flex flex-wrap items-center gap-1.5 px-4 py-2.5">
                <span class="me-1 text-[11px] font-bold uppercase tracking-widest text-subtle">Ruleset</span>
                @foreach ($this->rules as $rule)
                    <dl class="rule"><dt>{{ $rule['label'] }}</dt><dd>{{ $rule['value'] }}</dd></dl>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Head to head --}}
    @if ($compared->isNotEmpty())
        <section class="panel panel-flush bracket" wire:key="duel">
            <header class="panel-header">
                <p class="hud-label"><x-icon name="compare" class="size-3.5" /> Head to head</p>
                <button type="button" class="btn btn-ghost btn-sm" wire:click="clearCompare"><x-icon name="x" class="size-3" /> Clear</button>
            </header>

            @if ($compared->count() < 2)
                <p class="px-4 py-8 text-center text-[13px] text-muted">
                    <span class="font-bold text-ink">{{ $compared->first()->UserName }}</span> is picked. Choose a second run in the table.
                </p>
            @else
                @php
                    [$left, $right] = [$compared[0], $compared[1]];
                    $gap = (int) $left->time - (int) $right->time;

                    // [label, property, better, format]
                    $metrics = [
                        ['Time', 'time', 'low', fn ($v) => TimeFormat::runtime($v)],
                        ['Sync', 'sync', 'high', fn ($v) => $stat($v, 1, '%')],
                        ['Start speed', 'start_speed', 'high', fn ($v) => $v === null ? '—' : number_format((float) $v).' ups'],
                        ['Strafes', 'strafes', 'none', fn ($v) => $stat($v)],
                        ['Jumps', 'jumps', 'none', fn ($v) => $stat($v)],
                        ['Overlaps', 'overlaps', 'low', fn ($v) => $stat($v)],
                    ];
                @endphp

                <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-4 border-b border-line px-4 py-5 text-center">
                    <div class="min-w-0">
                        <p class="truncate text-[15px] font-bold">{{ $left->UserName }}</p>
                        <p class="big-figure mt-1 {{ $gap < 0 ? 'text-ink' : 'text-muted' }}">{{ TimeFormat::runtime($left->time) }}</p>
                    </div>

                    <div class="px-2">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-subtle">vs</p>
                        <p class="delta mt-1 !text-ink">{{ $gap === 0 ? 'tie' : ($gap < 0 ? '−' : '+').number_format(abs($gap) / 1000, 3) }}</p>
                    </div>

                    <div class="min-w-0">
                        <p class="truncate text-[15px] font-bold">{{ $right->UserName }}</p>
                        <p class="big-figure mt-1 {{ $gap > 0 ? 'text-ink' : 'text-muted' }}">{{ TimeFormat::runtime($right->time) }}</p>
                    </div>
                </div>

                <div class="space-y-3 px-4 py-4">
                    @foreach ($metrics as [$label, $property, $better, $format])
                        @php
                            $a = $left->{$property};
                            $b = $right->{$property};
                            $comparable = $a !== null && $b !== null;
                            $top = $comparable ? max((float) $a, (float) $b, 1) : 1;
                            $lead = null;

                            if ($comparable && $better !== 'none' && (float) $a !== (float) $b) {
                                $lead = (($better === 'low') === ((float) $a < (float) $b)) ? 'left' : 'right';
                            }
                        @endphp

                        <div class="grid grid-cols-[5.5rem_1fr_5.5rem] items-center gap-3 sm:grid-cols-[6.5rem_1fr_6.5rem]">
                            <span class="text-right text-[13px] font-semibold tabular {{ $lead === 'left' ? 'text-ink' : 'text-muted' }}">{{ $format($a) }}</span>

                            <div>
                                <p class="mb-1 text-center text-[10px] font-bold uppercase tracking-widest text-subtle">{{ $label }}</p>
                                <div class="duel-track">
                                    <span><i @class(['is-lead' => $lead === 'left']) style="width: {{ $comparable ? round((float) $a / $top * 100) : 0 }}%"></i></span>
                                    <span><i @class(['is-lead' => $lead === 'right']) style="width: {{ $comparable ? round((float) $b / $top * 100) : 0 }}%"></i></span>
                                </div>
                            </div>

                            <span class="text-[13px] font-semibold tabular {{ $lead === 'right' ? 'text-ink' : 'text-muted' }}">{{ $format($b) }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="border-t border-line px-4 py-2 text-[11px] text-subtle">
                    Highlighted bars lead. Strafes and jumps are shown but not scored: neither is better with more.
                    <span class="text-muted">The address of this page shares the comparison.</span>
                </p>
            @endif
        </section>
    @endif

    {{-- Scoreboard --}}
    <section class="panel panel-flush">
        <header class="panel-header">
            <div class="seg">
                <button type="button" wire:click="setView('summary')" @class(['is-active' => $view === 'summary'])>Summary</button>
                <button type="button" wire:click="setView('movement')" @class(['is-active' => $view === 'movement'])>Movement</button>
            </div>

            <p class="hidden text-[11px] text-subtle sm:block">Boxed cells lead the column · pick two runs with <x-icon name="compare" class="inline size-3" /> to compare</p>
        </header>

        <x-ui.table min="640px">
            <thead>
                <tr>
                    <x-ui.sort-th column="rank" :sort="$sort" :direction="$direction" label="#" class="w-16" />
                    <th>Player</th>
                    <x-ui.sort-th column="time" :sort="$sort" :direction="$direction" label="Time" align="right" />

                    @if ($view === 'summary')
                        <th class="text-right">Gap</th>
                        <x-ui.sort-th column="sync" :sort="$sort" :direction="$direction" label="Sync" align="right" />
                        <x-ui.sort-th column="speed" :sort="$sort" :direction="$direction" label="Start (ups)" align="right" class="hidden md:table-cell" />
                        <x-ui.sort-th column="date" :sort="$sort" :direction="$direction" label="Date" class="hidden lg:table-cell" />
                    @else
                        <x-ui.sort-th column="sync" :sort="$sort" :direction="$direction" label="Sync" align="right" />
                        <x-ui.sort-th column="strafes" :sort="$sort" :direction="$direction" label="Strafes" align="right" />
                        <x-ui.sort-th column="jumps" :sort="$sort" :direction="$direction" label="Jumps" align="right" />
                        <x-ui.sort-th column="overlaps" :sort="$sort" :direction="$direction" label="Overlaps" align="right" />
                        <th class="hidden text-right lg:table-cell">Overlap σ</th>
                    @endif

                    <th class="w-24"></th>
                    @auth
                        <th class="w-16"></th>
                    @endauth
                </tr>
            </thead>
            <tbody>
                @forelse ($this->records as $run)
                    @php($picked = in_array($run->UserUUID, $compare, true))

                    <tr wire:key="run-{{ $run->CategoryId }}-{{ $run->UserUUID }}" @class(['is-record' => (int) $run->Rank === 1, 'is-mine' => $picked])>
                        <td>
                            @if ((int) $run->Rank === 1)
                                <span class="rank rank-1"><x-icon name="crown" class="size-3" />1</span>
                            @else
                                <x-ui.rank :rank="$run->Rank" />
                            @endif
                        </td>
                        <td>
                            <x-ui.player :name="$run->UserName" :uuid="$run->UserUUID" :nationality="$run->nationality" :avatar="$this->avatars[$run->auth_id] ?? null" />
                        </td>
                        <td class="time time-lg text-right">
                            <a href="{{ route('runs.show', [$mapUuid, $run->CategoryId, $run->UserUUID]) }}" class="hover:text-accent">{{ TimeFormat::runtime($run->time) }}</a>
                        </td>

                        @if ($view === 'summary')
                            <td @class(['delta text-right', 'delta-record' => (int) $run->Delta <= 0])>{{ TimeFormat::delta($run->Delta) }}</td>
                            <td class="text-right"><x-ui.stat-cell :value="$run->sync" :best="$bests['sync'] ?? null" :decimals="1" suffix="%" /></td>
                            <td class="hidden text-right md:table-cell"><x-ui.stat-cell :value="$run->start_speed" :best="$bests['start_speed'] ?? null" /></td>
                            <td class="hidden font-mono text-[11px] text-subtle lg:table-cell">{{ $run->record_date }}</td>
                        @else
                            <td class="text-right"><x-ui.stat-cell :value="$run->sync" :best="$bests['sync'] ?? null" :decimals="1" suffix="%" /></td>
                            <td class="text-right"><x-ui.stat-cell :value="$run->strafes" /></td>
                            <td class="text-right"><x-ui.stat-cell :value="$run->jumps" /></td>
                            <td class="text-right"><x-ui.stat-cell :value="$run->overlaps" :best="$bests['overlaps'] ?? null" /></td>
                            <td class="hidden text-right lg:table-cell"><x-ui.stat-cell :value="$run->overlaps_sd" :best="$bests['overlaps_sd'] ?? null" :decimals="2" /></td>
                        @endif

                        <td class="text-right">
                            <span class="inline-flex items-center gap-1">
                                @if ((int) $run->Rank !== 1)
                                    <button type="button" wire:click="compareWithRecord('{{ $run->UserUUID }}')" class="btn btn-ghost btn-sm" title="Compare with the world record">vs WR</button>
                                @endif
                                <button type="button" wire:click="toggleCompare('{{ $run->UserUUID }}')" @class(['btn btn-sm', 'btn-primary' => $picked, 'btn-ghost' => ! $picked]) title="Pick for head to head">
                                    <x-icon name="compare" class="size-3.5" />
                                </button>
                            </span>
                        </td>

                        @auth
                            <td class="text-right">
                                <button type="button" class="btn btn-danger btn-sm"
                                        x-on:click="pending = {{ Js::from([
                                            'name' => $run->UserName,
                                            'uuid' => $run->UserUUID,
                                            'categoryId' => $run->CategoryId,
                                            'category' => $run->CategoryName,
                                            'rank' => $run->Rank,
                                            'time' => TimeFormat::runtime($run->time),
                                        ]) }}">Delete</button>
                            </td>
                        @endauth
                    </tr>
                @empty
                    <x-ui.empty :colspan="10" message="No runs on this category yet." />
                @endforelse
            </tbody>
        </x-ui.table>

        @if ($sort === 'rank' && $count > 15)
            <div class="border-t border-line px-4 py-2.5 text-center">
                <button type="button" class="link text-[12px]" wire:click="$toggle('all')">
                    {{ $all ? 'Show the top 15' : 'Show all '.$count.' runs' }}
                </button>
            </div>
        @endif
    </section>

    @auth
        <div x-cloak x-show="pending" x-on:keydown.escape.window="pending = null"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
            <div class="panel w-full max-w-md p-5" x-on:click.outside="pending = null">
                <p class="hud-label !text-danger">Destructive</p>
                <h2 class="mt-1 text-lg">Delete this record?</h2>
                <p class="mt-2 text-muted">
                    <span class="font-semibold text-ink" x-text="pending?.name"></span> ·
                    <span class="time" x-text="pending?.time"></span> ·
                    <span x-text="pending?.category"></span>.
                    The replay file goes with it, and this cannot be undone.
                </p>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" x-on:click="pending = null">Cancel</button>

                    <form method="POST" action="{{ route('maps.delete.time', $mapUuid) }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="user_uuid" :value="pending?.uuid">
                        <input type="hidden" name="category_id" :value="pending?.categoryId">
                        <input type="hidden" name="category_name" :value="pending?.category">
                        <input type="hidden" name="rank" :value="pending?.rank">
                        <button type="submit" class="btn btn-danger">Delete record</button>
                    </form>
                </div>
            </div>
        </div>
    @endauth
</div>
