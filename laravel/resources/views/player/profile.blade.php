@use('App\Support\TimeFormat')

@php
    $total = max(1, $rankSummary['total']);
    $tiles = [
        ['icon' => 'crown', 'label' => 'World records', 'count' => $rankSummary['first'], 'tone' => 'text-gold'],
        ['icon' => 'medal', 'label' => 'Second places', 'count' => $rankSummary['second'], 'tone' => 'text-silver'],
        ['icon' => 'medal', 'label' => 'Third places', 'count' => $rankSummary['third'], 'tone' => 'text-bronze'],
        ['icon' => 'target', 'label' => 'Top 10 finishes', 'count' => $rankSummary['top10'], 'tone' => 'text-accent'],
    ];
@endphp

<x-layouts.app :title="$user->name ?? 'Player'">
    <div class="space-y-2">
        <x-ui.breadcrumb :trail="['Players' => route('players.index'), ($user->name ?? 'Unknown') => null]" />

        {{-- Identity --}}
        <section class="panel bracket">
            <div class="flex flex-col gap-4 p-4 md:flex-row md:items-center">
                @if ($steamData['avatar'] ?? null)
                    <a href="https://steamcommunity.com/profiles/{{ $steamData['steamid64'] ?? $user->auth_id }}" target="_blank" rel="noopener" class="shrink-0">
                        <img src="{{ $steamData['avatar'] }}" alt="{{ $user->name }}" class="size-24 rounded-sm border-2 border-accent/60 object-cover" />
                    </a>
                @else
                    <div class="flex size-24 shrink-0 items-center justify-center rounded-sm border-2 border-line-strong bg-black/25 text-3xl text-subtle">
                        {{ mb_substr($user->name ?? '?', 0, 1) }}
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="hud-label"><x-icon name="users" class="size-3.5" /> Runner profile</p>

                    <div class="mt-1 flex flex-wrap items-center gap-2.5">
                        <x-flag :code="$user->nationality" class="!h-4 !w-6" />
                        <h1 class="truncate text-2xl">{{ $user->name ?? 'Unknown' }}</h1>
                    </div>

                    <p class="mt-1 font-mono text-[11px] text-subtle">
                        {{ $user->auth_id }}
                        @if ($steamData['steamid64'] ?? null)
                            · <a href="https://steamcommunity.com/profiles/{{ $steamData['steamid64'] }}" target="_blank" rel="noopener" class="link">Steam profile</a>
                        @endif
                    </p>

                    {{-- Medals the timer awarded, summed over every map --}}
                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        <span class="inline-flex items-center gap-1.5 text-gold" title="Gold medals">
                            <x-icon name="medal" class="size-5" /><b class="font-mono text-base">{{ number_format($medals['gold']) }}</b>
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-silver" title="Silver medals">
                            <x-icon name="medal" class="size-5" /><b class="font-mono text-base">{{ number_format($medals['silver']) }}</b>
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-bronze" title="Bronze medals">
                            <x-icon name="medal" class="size-5" /><b class="font-mono text-base">{{ number_format($medals['bronze']) }}</b>
                        </span>
                    </div>
                </div>

                <dl class="grid shrink-0 grid-cols-2 gap-2 md:w-64">
                    <div class="rounded-sm bg-black/25 px-3 py-2">
                        <dt class="flex items-center gap-1.5 text-[11px] uppercase tracking-widest text-subtle"><x-icon name="clock" class="size-3.5" /> Played</dt>
                        <dd class="mt-0.5 font-mono text-lg tabular">{{ number_format($totalTimePlayed / 3600, 1) }}h</dd>
                    </div>
                    <div class="rounded-sm bg-black/25 px-3 py-2">
                        <dt class="flex items-center gap-1.5 text-[11px] uppercase tracking-widest text-subtle"><x-icon name="flame" class="size-3.5" /> Runs</dt>
                        <dd class="mt-0.5 font-mono text-lg tabular text-accent">{{ number_format($totalTimes) }}</dd>
                    </div>
                </dl>
            </div>

            @auth
                <div class="flex items-center justify-end gap-2 border-t border-line px-4 py-2" x-data="{ confirming: false }">
                    <p class="me-auto text-[11px] text-subtle">Admin actions</p>

                    <button type="button" class="btn btn-danger btn-sm" x-on:click="confirming = true">Wipe all times</button>

                    <div x-cloak x-show="confirming" x-on:keydown.escape.window="confirming = false"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
                        <div class="panel bracket w-full max-w-md p-5" x-on:click.outside="confirming = false">
                            <p class="hud-label">Destructive</p>
                            <h2 class="mt-1 text-lg">Wipe every time for {{ $user->name }}?</h2>
                            <p class="mt-2 text-muted">
                                All {{ number_format($totalTimes) }} runs go, and the replay files of their
                                first-place records are deleted from disk. This cannot be undone.
                            </p>

                            <div class="mt-5 flex justify-end gap-2">
                                <button type="button" class="btn btn-ghost" x-on:click="confirming = false">Cancel</button>

                                <form method="POST" action="{{ route('players.delete.times', $user->uuid) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Wipe times</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endauth
        </section>

        {{-- Where they stand --}}
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($tiles as $tile)
                <div class="panel px-3 py-2.5">
                    <p class="flex items-center gap-1.5 text-[11px] uppercase tracking-widest text-subtle">
                        <x-icon :name="$tile['icon']" class="size-3.5 {{ $tile['tone'] }}" /> {{ $tile['label'] }}
                    </p>
                    <p class="mt-1 font-mono text-2xl tabular {{ $tile['tone'] }}">{{ number_format($tile['count']) }}</p>
                    <span class="meter mt-2"><span class="meter-fill" style="width: {{ min(100, round($tile['count'] / $total * 100)) }}%"></span></span>
                    <p class="mt-1 text-[10px] text-subtle">of {{ number_format($rankSummary['total']) }} ranked runs</p>
                </div>
            @endforeach
        </div>

        <div x-data="{ tab: 'records' }" class="space-y-2">
            <div class="tabs panel !bg-transparent">
                <button type="button" class="tab" :class="tab === 'records' && 'is-active'" x-on:click="tab = 'records'">
                    <x-icon name="trophy" class="size-3.5" /> Records
                </button>
                <button type="button" class="tab" :class="tab === 'reach' && 'is-active'" x-on:click="tab = 'reach'">
                    <x-icon name="target" class="size-3.5" /> Within reach
                </button>
                <button type="button" class="tab" :class="tab === 'time' && 'is-active'" x-on:click="tab = 'time'">
                    <x-icon name="clock" class="size-3.5" /> Played time
                </button>

                <div x-show="tab === 'time'" x-cloak class="ms-auto flex items-center px-2">
                    <label for="chart-range" class="sr-only">Range</label>
                    <select id="chart-range" class="input w-auto">
                        <option value="7">last 7 days</option>
                        <option value="30" selected>last 30 days</option>
                    </select>
                </div>
            </div>

            <div x-show="tab === 'records'">
                <livewire:player-records :user-uuid="$user->uuid" />
            </div>

            <div x-show="tab === 'reach'" x-cloak>
                <x-ui.panel flush>
                    <header class="panel-header">
                        <p class="hud-label"><x-icon name="target" class="size-3.5" /> Closest to a record</p>
                        <p class="text-[11px] text-subtle">records you do not hold, smallest gap first</p>
                    </header>

                    <x-ui.table min="480px">
                        <thead>
                            <tr>
                                <th>Map</th>
                                <th>Category</th>
                                <th class="text-right">Your time</th>
                                <th class="text-right">Gap to WR</th>
                                <th class="text-right">Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($withinReach as $run)
                                <tr>
                                    <td><a class="link" href="{{ route('maps.show', $run->MapUUID) }}?category={{ $run->CategoryId }}">{{ $run->MapName }}</a></td>
                                    <td><x-ui.pill>{{ $run->CategoryName }}</x-ui.pill></td>
                                    <td class="time text-right">
                                        <a href="{{ route('runs.show', [$run->MapUUID, $run->CategoryId, $user->uuid]) }}" class="hover:text-accent">{{ TimeFormat::runtime($run->Time) }}</a>
                                    </td>
                                    <td class="delta text-right !text-accent">{{ TimeFormat::delta($run->Delta) }}</td>
                                    <td class="text-right"><x-ui.rank :rank="$run->Rank" /></td>
                                </tr>
                            @empty
                                <x-ui.empty :colspan="5" message="Nothing left to chase: every run is already a record." />
                            @endforelse
                        </tbody>
                    </x-ui.table>
                </x-ui.panel>
            </div>

            <div x-show="tab === 'time'" x-cloak>
                @include('player.partials.time-chart')
            </div>
        </div>
    </div>
</x-layouts.app>
