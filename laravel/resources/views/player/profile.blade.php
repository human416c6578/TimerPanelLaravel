@use('App\Support\TimeFormat')

@php
    $total = max(1, $insights['total']);
    $share = fn (int $count) => min(100, round($count / $total * 100));
    $catMax = max(1, $insights['categories']->max('runs') ?? 1);
    $typical = $insights['typicalPosition'];
@endphp

<x-layouts.app :title="$user->name ?? 'Player'">
    <div class="space-y-3">
        <x-ui.breadcrumb :trail="['Players' => route('players.index'), ($user->name ?? 'Unknown') => null]" />

        <div class="grid gap-4 lg:grid-cols-[17rem_minmax(0,1fr)]">
            {{-- Identity rail --}}
            <aside class="space-y-3">
                <section class="panel">
                    <div class="flex flex-col items-center px-4 pt-6 text-center">
                        @if ($steamData['avatar'] ?? null)
                            <a href="https://steamcommunity.com/profiles/{{ $steamData['steamid64'] ?? $user->auth_id }}" target="_blank" rel="noopener">
                                <img src="{{ $steamData['avatar'] }}" alt="{{ $user->name }}" class="size-24 rounded-xl object-cover" />
                            </a>
                        @else
                            <div class="flex size-24 items-center justify-center rounded-xl bg-surface-3 text-4xl font-bold text-subtle">{{ mb_substr($user->name ?? '?', 0, 1) }}</div>
                        @endif

                        <h1 class="mt-3 flex max-w-full items-center gap-2 text-xl">
                            <x-flag :code="$user->nationality" class="!h-4 !w-6 rounded-sm" />
                            <span class="truncate">{{ $user->name ?? 'Unknown' }}</span>
                        </h1>

                        <p class="mt-1 font-mono text-[11px] text-subtle">{{ $user->auth_id }}</p>

                        @if ($steamData['steamid64'] ?? null)
                            <a href="https://steamcommunity.com/profiles/{{ $steamData['steamid64'] }}" target="_blank" rel="noopener" class="mt-1 text-[12px] font-semibold text-accent hover:underline">Steam profile</a>
                        @endif
                    </div>

                    {{-- Medals --}}
                    <div class="mt-4 grid grid-cols-3 border-y border-line text-center">
                        @foreach ([['gold', 'text-gold'], ['silver', 'text-silver'], ['bronze', 'text-bronze']] as [$tier, $tone])
                            <div class="py-3" title="{{ ucfirst($tier) }} medals">
                                <x-icon name="medal" class="mx-auto size-5 {{ $tone }}" />
                                <p class="mt-1 text-[15px] font-bold tabular">{{ number_format($medals[$tier]) }}</p>
                            </div>
                        @endforeach
                    </div>

                    <dl class="divide-y divide-line text-[13px]">
                        <div class="flex items-center justify-between px-4 py-2.5"><dt class="text-muted">Time played</dt><dd class="font-semibold tabular">{{ number_format($totalTimePlayed / 3600, 1) }} h</dd></div>
                        <div class="flex items-center justify-between px-4 py-2.5"><dt class="text-muted">Ranked runs</dt><dd class="font-semibold tabular">{{ number_format($totalTimes) }}</dd></div>
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <dt class="text-muted">Average sync</dt>
                            <dd class="font-semibold tabular">{{ $insights['avgSync'] === null ? '—' : number_format($insights['avgSync'], 1).'%' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="panel p-3">
                    <p class="hud-label mb-2"><x-icon name="compare" class="size-3.5" /> Compare with</p>
                    <livewire:global-search :compare-with="$user->uuid" placeholder="Find a player" />
                </section>

                @auth
                    <section class="panel p-3" x-data="{ confirming: false }">
                        <button type="button" class="btn btn-danger btn-sm w-full" x-on:click="confirming = true">Wipe all times</button>

                        <div x-cloak x-show="confirming" x-on:keydown.escape.window="confirming = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                            <div class="panel w-full max-w-md p-5" x-on:click.outside="confirming = false">
                                <p class="hud-label !text-danger">Destructive</p>
                                <h2 class="mt-1 text-lg">Wipe every time for {{ $user->name }}?</h2>
                                <p class="mt-2 text-muted">All {{ number_format($totalTimes) }} runs go, and the replay files of their first-place records are deleted from disk. This cannot be undone.</p>

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
                    </section>
                @endauth
            </aside>

            {{-- Dashboard --}}
            <div x-data="{ tab: 'overview' }" class="min-w-0 space-y-3">
                <div class="panel">
                    <div class="tabs px-2">
                        <button type="button" class="tab" :class="tab === 'overview' && 'is-active'" x-on:click="tab = 'overview'">Overview</button>
                        <button type="button" class="tab" :class="tab === 'records' && 'is-active'" x-on:click="tab = 'records'">Records</button>
                        <button type="button" class="tab" :class="tab === 'time' && 'is-active'" x-on:click="tab = 'time'">Played time</button>

                        <div x-show="tab === 'time'" x-cloak class="ms-auto flex items-center px-2">
                            <label for="chart-range" class="sr-only">Range</label>
                            <select id="chart-range" class="input w-auto">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- OVERVIEW --}}
                <div x-show="tab === 'overview'" class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="panel p-4 meter-gold sm:col-span-2 xl:col-span-1">
                            <p class="hud-label"><x-icon name="crown" class="size-3.5 text-gold" /> World records</p>
                            <p class="hero-figure mt-3">{{ number_format($insights['records']) }}</p>
                            <span class="meter mt-4"><span class="meter-fill" style="width: {{ $share($insights['records']) }}%"></span></span>
                            <p class="mt-1.5 text-[11px] text-subtle">{{ $share($insights['records']) }}% of {{ number_format($insights['total']) }} runs</p>
                        </div>

                        @foreach ([
                            ['Podiums', 'medal', $insights['podiums'], 'Top three'],
                            ['Top 10', 'target', $insights['top10'], 'Top ten'],
                        ] as [$label, $icon, $count, $hint])
                            <div class="panel p-4">
                                <p class="hud-label"><x-icon :name="$icon" class="size-3.5" /> {{ $label }}</p>
                                <p class="big-figure mt-3">{{ number_format($count) }}</p>
                                <span class="meter mt-4"><span class="meter-fill" style="width: {{ $share($count) }}%"></span></span>
                                <p class="mt-1.5 text-[11px] text-subtle">{{ $share($count) }}% finish in the {{ strtolower($hint) }}</p>
                            </div>
                        @endforeach

                        <div class="panel p-4">
                            <p class="hud-label"><x-icon name="gauge" class="size-3.5" /> Typical position</p>
                            <p class="big-figure mt-3">{{ $typical === null ? '—' : 'Top '.max(1, round($typical * 100)).'%' }}</p>
                            <span class="meter mt-4"><span class="meter-fill" style="width: {{ $typical === null ? 0 : round((1 - $typical) * 100) }}%"></span></span>
                            <p class="mt-1.5 text-[11px] text-subtle">on maps with five or more runners</p>
                        </div>
                    </div>

                    {{-- Form --}}
                    <section class="panel p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="hud-label"><x-icon name="flame" class="size-3.5" /> Recent form</p>

                            <ul class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted">
                                @foreach ([['wr', 'World record'], ['podium', 'Podium'], ['top10', 'Top 10'], ['other', 'Other']] as [$tier, $label])
                                    <li class="flex items-center gap-1.5"><span class="form-dot !size-2.5" data-tier="{{ $tier }}"></span>{{ $label }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2.5">
                            @forelse ($insights['form'] as $run)
                                <x-ui.form-dot :run="$run" :user-uuid="$user->uuid" />
                            @empty
                                <p class="text-[12px] text-subtle">No runs yet.</p>
                            @endforelse

                            @if ($insights['form']->isNotEmpty())
                                <span class="ms-2 text-[11px] text-subtle">newest first</span>
                            @endif
                        </div>
                    </section>

                    <div class="grid gap-3 lg:grid-cols-2">
                        {{-- Categories --}}
                        <section class="panel">
                            <header class="panel-header"><p class="hud-label"><x-icon name="bar-chart" class="size-3.5" /> Where they run</p></header>

                            <div class="space-y-4 p-4">
                                @forelse ($insights['categories'] as $category)
                                    <div>
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="truncate font-semibold">{{ $category->name }}</span>
                                            <span class="text-[12px] text-muted tabular">
                                                {{ $category->runs }} {{ Str::plural('run', $category->runs) }}
                                                @if ($category->records)
                                                    · <span class="font-semibold text-ink">{{ $category->records }} WR</span>
                                                @endif
                                            </span>
                                        </div>
                                        <span class="meter mt-1.5"><span class="meter-fill" style="width: {{ round($category->runs / $catMax * 100) }}%"></span></span>
                                    </div>
                                @empty
                                    <p class="py-4 text-center text-[12px] text-subtle">No runs yet.</p>
                                @endforelse
                            </div>
                        </section>

                        {{-- Closest to a record --}}
                        <section class="panel">
                            <header class="panel-header">
                                <p class="hud-label"><x-icon name="target" class="size-3.5" /> Closest to a record</p>
                            </header>

                            <div class="divide-y divide-line">
                                @forelse ($withinReach as $run)
                                    <a href="{{ route('runs.show', [$run->MapUUID, $run->CategoryId, $user->uuid]) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-accent-soft">
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-semibold">{{ $run->MapName }}</span>
                                            <span class="block truncate text-[11px] text-subtle">{{ $run->CategoryName }} · #{{ $run->Rank }}</span>
                                        </span>
                                        <span class="time text-[12px] text-muted">{{ TimeFormat::runtime($run->Time) }}</span>
                                        <span class="delta !text-accent">{{ TimeFormat::delta($run->Delta) }}</span>
                                    </a>
                                @empty
                                    <p class="px-4 py-8 text-center text-[12px] text-subtle">Nothing left to chase: every run is a record.</p>
                                @endforelse
                            </div>
                            <p class="border-t border-line px-4 py-2 text-[11px] text-subtle">records not held yet, smallest gap first</p>
                        </section>
                    </div>
                </div>

                <div x-show="tab === 'records'" x-cloak>
                    <livewire:player-records :user-uuid="$user->uuid" />
                </div>

                <div x-show="tab === 'time'" x-cloak>
                    @include('player.partials.time-chart')
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
