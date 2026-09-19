@use('App\Support\TimeFormat')

@php
    $record = $this->record;
    $mode = str_contains(strtolower($mapName), 'deathrun') ? 'Deathrun' : 'Bhop';
    $compared = $this->compared;
    $stat = fn ($value, $decimals = 0, $suffix = '') => $value === null ? '—' : number_format((float) $value, $decimals).$suffix;
@endphp

<div class="space-y-2" x-data="{ pending: null }">
    {{-- Hero --}}
    <section class="panel bracket">
        <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="hud-label"><x-icon name="map" class="size-3.5" /> Map leaderboard</p>
                <h1 class="display-xl mt-1 break-all">{{ $mapName }}</h1>

                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <x-ui.pill accent>{{ $mode }}</x-ui.pill>
                    <x-ui.pill>{{ $this->categories->count() }} {{ Str::plural('category', $this->categories->count()) }}</x-ui.pill>
                </div>
            </div>

            @if ($record)
                <a href="{{ route('runs.show', [$mapUuid, $record->CategoryId, $record->UserUUID]) }}"
                   class="shrink-0 rounded-sm bg-black/25 px-4 py-2.5 text-right hover:bg-black/40">
                    <p class="flex items-center justify-end gap-1.5 text-[11px] uppercase tracking-widest text-gold">
                        <x-icon name="crown" class="size-3.5" /> World record
                    </p>
                    <p class="time-hero mt-1 text-gold">{{ TimeFormat::runtime($record->time) }}</p>
                    <p class="mt-1 text-[12px] text-muted">by <span class="text-ink">{{ $record->UserName }}</span></p>
                </a>
            @endif
        </div>

        {{-- Category tabs --}}
        <div class="tabs">
            @foreach ($this->categories as $category)
                <button type="button"
                        wire:click="selectCategory('{{ $category['id'] }}')"
                        @class(['tab', 'is-active' => $category['id'] === $this->currentCategory])>
                    {{ $category['name'] }}
                    <span class="font-mono text-[10px] opacity-60">{{ $category['runs'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- The ruleset behind the category --}}
        @if (count($this->rules))
            <div class="flex flex-wrap items-center gap-1.5 border-t border-line px-3 py-2">
                <span class="me-1 text-[11px] uppercase tracking-widest text-subtle">Ruleset</span>

                @foreach ($this->rules as $rule)
                    <dl class="rule"><dt>{{ $rule['label'] }}</dt><dd>{{ $rule['value'] }}</dd></dl>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Head to head --}}
    @if ($compared->isNotEmpty())
        <section class="panel panel-flush" wire:key="compare">
            <header class="panel-header">
                <p class="hud-label"><x-icon name="compare" class="size-3.5" /> Head to head</p>
                <button type="button" class="btn btn-ghost btn-sm" wire:click="clearCompare"><x-icon name="x" class="size-3" /> Clear</button>
            </header>

            @if ($compared->count() < 2)
                <p class="px-4 py-5 text-center text-[12px] text-subtle">
                    {{ $compared->first()->UserName }} picked. Choose a second run in the table to compare.
                </p>
            @else
                @php
                    [$left, $right] = [$compared[0], $compared[1]];
                    // [label, property, which side is better, formatter]
                    $rows = [
                        ['Time', 'time', 'low', fn ($v) => TimeFormat::runtime($v)],
                        ['Gap to WR', 'Delta', 'low', fn ($v) => TimeFormat::delta($v)],
                        ['Sync', 'sync', 'high', fn ($v) => $stat($v, 1, '%')],
                        ['Strafes', 'strafes', 'none', fn ($v) => $stat($v)],
                        ['Jumps', 'jumps', 'none', fn ($v) => $stat($v)],
                        ['Overlaps', 'overlaps', 'low', fn ($v) => $stat($v)],
                        ['Start speed', 'start_speed', 'high', fn ($v) => $stat($v)],
                    ];
                @endphp

                <table class="data-table text-center">
                    <thead>
                        <tr>
                            <th class="text-right">{{ $left->UserName }}</th>
                            <th class="w-32">&nbsp;</th>
                            <th class="text-left">{{ $right->UserName }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as [$label, $property, $better, $format])
                            @php
                                $a = $left->{$property};
                                $b = $right->{$property};
                                $winner = null;

                                if ($better !== 'none' && $a !== null && $b !== null && (float) $a !== (float) $b) {
                                    $winner = (($better === 'low') === ((float) $a < (float) $b)) ? 'left' : 'right';
                                }
                            @endphp

                            <tr>
                                <td @class(['time text-right', 'text-live' => $winner === 'left'])>{{ $format($a) }}</td>
                                <td class="text-[11px] uppercase tracking-widest text-subtle">{{ $label }}</td>
                                <td @class(['time text-left', 'text-live' => $winner === 'right'])>{{ $format($b) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif

    {{-- The board --}}
    <x-ui.panel flush>
        <x-ui.table min="760px">
            <thead>
                <tr>
                    <x-ui.sort-th column="rank" :sort="$sort" :direction="$direction" label="#" class="w-16" />
                    <th>Player</th>
                    <x-ui.sort-th column="time" :sort="$sort" :direction="$direction" label="Time" align="right" />
                    <th class="text-right">Gap</th>
                    <x-ui.sort-th column="sync" :sort="$sort" :direction="$direction" label="Sync" align="right" class="hidden sm:table-cell" />
                    <x-ui.sort-th column="strafes" :sort="$sort" :direction="$direction" label="Strafes" align="right" class="hidden md:table-cell" />
                    <x-ui.sort-th column="jumps" :sort="$sort" :direction="$direction" label="Jumps" align="right" class="hidden lg:table-cell" />
                    <x-ui.sort-th column="speed" :sort="$sort" :direction="$direction" label="Start" align="right" class="hidden lg:table-cell" />
                    <x-ui.sort-th column="date" :sort="$sort" :direction="$direction" label="Date" class="hidden xl:table-cell" />
                    <th class="w-8"></th>
                    @auth
                        <th class="text-right"></th>
                    @endauth
                </tr>
            </thead>
            <tbody>
                @forelse ($this->records as $run)
                    <tr wire:key="run-{{ $run->CategoryId }}-{{ $run->UserUUID }}" @class(['is-record' => (int) $run->Rank === 1])>
                        <td>
                            @if ((int) $run->Rank === 1)
                                <span class="rank rank-1"><x-icon name="crown" class="size-3" />1</span>
                            @else
                                <x-ui.rank :rank="$run->Rank" />
                            @endif
                        </td>
                        <td>
                            <span class="inline-flex items-center gap-2">
                                <x-flag :code="$run->nationality" />
                                <a href="{{ route('players.show', $run->UserUUID) }}" class="link">{{ $run->UserName }}</a>
                            </span>
                        </td>
                        <td class="time time-lg text-right">
                            <a href="{{ route('runs.show', [$mapUuid, $run->CategoryId, $run->UserUUID]) }}"
                               @class(['hover:text-accent', 'text-gold' => (int) $run->Rank === 1])>{{ TimeFormat::runtime($run->time) }}</a>
                        </td>
                        <td @class(['delta text-right', 'delta-record' => (int) $run->Delta <= 0])>{{ TimeFormat::delta($run->Delta) }}</td>
                        <td class="hidden text-right tabular sm:table-cell">{{ $stat($run->sync, 1, '%') }}</td>
                        <td class="hidden text-right tabular text-muted md:table-cell">{{ $stat($run->strafes) }}</td>
                        <td class="hidden text-right tabular text-muted lg:table-cell">{{ $stat($run->jumps) }}</td>
                        <td class="hidden text-right tabular text-muted lg:table-cell">{{ $stat($run->start_speed) }}</td>
                        <td class="hidden font-mono text-[11px] text-subtle xl:table-cell">{{ $run->record_date }}</td>
                        <td>
                            <button type="button"
                                    wire:click="toggleCompare('{{ $run->UserUUID }}')"
                                    @class(['inline-flex items-center', 'text-accent' => in_array($run->UserUUID, $compare, true), 'text-subtle hover:text-accent' => ! in_array($run->UserUUID, $compare, true)])
                                    title="Compare this run">
                                <x-icon name="compare" />
                            </button>
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
                    <x-ui.empty :colspan="11" message="No runs on this category yet." />
                @endforelse
            </tbody>
        </x-ui.table>

        @if ($sort === 'rank' && $this->boards->get($this->currentCategory, collect())->count() > 15)
            <div class="border-t border-line px-3 py-2 text-center">
                <button type="button" class="link text-[12px]" wire:click="$toggle('all')">
                    {{ $all ? 'Show the top 15' : 'Show all '.$this->boards->get($this->currentCategory)->count().' runs' }}
                </button>
            </div>
        @endif
    </x-ui.panel>

    @auth
        <div x-cloak x-show="pending" x-on:keydown.escape.window="pending = null"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
            <div class="panel bracket w-full max-w-md p-5" x-on:click.outside="pending = null">
                <p class="hud-label">Destructive</p>
                <h2 class="mt-1 text-lg">Delete this record?</h2>
                <p class="mt-2 text-muted">
                    <span class="text-ink" x-text="pending?.name"></span> ·
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
