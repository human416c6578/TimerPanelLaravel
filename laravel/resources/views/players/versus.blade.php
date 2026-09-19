@use('App\Support\TimeFormat')

@php
    $total = max(1, $versus['common']);
    $stat = fn ($value, $decimals = 1, $suffix = '%') => $value === null ? '—' : number_format((float) $value, $decimals).$suffix;
    $leader = $versus['aWins'] === $versus['bWins'] ? null : ($versus['aWins'] > $versus['bWins'] ? 'a' : 'b');
@endphp

<x-layouts.app :title="$left->name.' vs '.$right->name">
    <div class="space-y-3">
        <x-ui.breadcrumb :trail="['Players' => route('players.index'), $left->name => route('players.show', $left->uuid), 'vs '.$right->name => null]" />

        {{-- Score --}}
        <section class="panel panel-flush bracket">
            <div class="grid items-center gap-4 px-5 py-7 text-center md:grid-cols-[1fr_auto_1fr]">
                <div class="min-w-0">
                    <a href="{{ route('players.show', $left->uuid) }}" class="inline-flex max-w-full items-center gap-2 text-lg font-bold hover:text-accent">
                        <x-flag :code="$left->nationality" class="!h-4 !w-6 rounded-sm" /><span class="truncate">{{ $left->name }}</span>
                    </a>
                    <p class="mt-1 text-[12px] text-muted">{{ $stat($versus['aSync']) }} average sync</p>
                </div>

                <div>
                    <p class="hero-figure tabular-nums">
                        <span @class(['text-subtle' => $leader === 'b'])>{{ $versus['aWins'] }}</span>
                        <span class="mx-1 text-subtle">:</span>
                        <span @class(['text-subtle' => $leader === 'a'])>{{ $versus['bWins'] }}</span>
                    </p>
                    <p class="mt-2 text-[11px] font-bold uppercase tracking-widest text-subtle">faster on {{ $versus['common'] }} shared {{ Str::plural('run', $versus['common']) }}</p>
                </div>

                <div class="min-w-0">
                    <a href="{{ route('players.show', $right->uuid) }}" class="inline-flex max-w-full items-center gap-2 text-lg font-bold hover:text-accent">
                        <x-flag :code="$right->nationality" class="!h-4 !w-6 rounded-sm" /><span class="truncate">{{ $right->name }}</span>
                    </a>
                    <p class="mt-1 text-[12px] text-muted">{{ $stat($versus['bSync']) }} average sync</p>
                </div>
            </div>

            @if ($versus['common'] > 0)
                <div class="border-t border-line px-5 py-3">
                    <div class="duel-track" role="img" aria-label="{{ $versus['aWins'] }} runs for {{ $left->name }}, {{ $versus['bWins'] }} for {{ $right->name }}">
                        <span><i @class(['is-lead' => $leader === 'a']) style="width: {{ round($versus['aWins'] / $total * 100) }}%"></i></span>
                        <span><i @class(['is-lead' => $leader === 'b']) style="width: {{ round($versus['bWins'] / $total * 100) }}%"></i></span>
                    </div>

                    <p class="mt-2 text-center text-[12px] text-muted">
                        Average gap on shared runs:
                        <span class="font-mono font-semibold text-ink">{{ $versus['avgGap'] === null ? '—' : number_format($versus['avgGap'] / 1000, 3).'s' }}</span>
                        @if ($versus['ties'])
                            · {{ $versus['ties'] }} {{ Str::plural('tie', $versus['ties']) }}
                        @endif
                    </p>
                </div>
            @endif
        </section>

        {{-- Every shared run --}}
        <section class="panel panel-flush">
            <header class="panel-header">
                <p class="hud-label"><x-icon name="compare" class="size-3.5" /> Maps you both ran</p>
                <p class="text-[11px] text-subtle">the faster time is boxed</p>
            </header>

            <x-ui.table min="560px">
                <thead>
                    <tr>
                        <th>Map</th>
                        <th>Category</th>
                        <th class="text-right">{{ $left->name }}</th>
                        <th class="text-right">{{ $right->name }}</th>
                        <th class="text-right">Gap</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($versus['rows'] as $row)
                        <tr>
                            <td><a href="{{ route('maps.show', $row->mapUuid) }}?category={{ $row->categoryId }}" class="link">{{ $row->map }}</a></td>
                            <td><x-ui.pill>{{ $row->category }}</x-ui.pill></td>
                            @foreach (['a' => $left, 'b' => $right] as $side => $player)
                                <td class="time text-right">
                                    <a href="{{ route('runs.show', [$row->mapUuid, $row->categoryId, $player->uuid]) }}" @class(['best' => $row->winner === $side, 'hover:text-accent' => $row->winner !== $side])>
                                        {{ TimeFormat::runtime($row->{$side}->Time) }}
                                    </a>
                                    <span class="ms-1 text-[10px] text-subtle">#{{ $row->{$side}->Rank }}</span>
                                </td>
                            @endforeach
                            <td class="delta text-right">{{ $row->winner === 'tie' ? 'tie' : number_format(abs($row->gap) / 1000, 3) }}</td>
                        </tr>
                    @empty
                        <x-ui.empty :colspan="5" message="These two have not run any of the same maps and categories yet." />
                    @endforelse
                </tbody>
            </x-ui.table>
        </section>
    </div>
</x-layouts.app>
