<x-layouts.app :title="$user->name ?? 'Player'">
    <div class="space-y-3">
        {{-- Banner --}}
        <section class="panel bracket">
            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                @if ($steamData['avatar'] ?? null)
                    <a href="https://steamcommunity.com/profiles/{{ $steamData['steamid64'] ?? $user->auth_id }}" target="_blank" rel="noopener" class="shrink-0">
                        <img src="{{ $steamData['avatar'] }}" alt="{{ $user->name }}" class="size-20 border border-line-strong object-cover" />
                    </a>
                @else
                    <div class="flex size-20 shrink-0 items-center justify-center border border-line-strong bg-surface-2 font-display text-3xl font-bold text-subtle">
                        {{ mb_substr($user->name ?? '?', 0, 1) }}
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="hud-label">Runner profile</p>

                    <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                        @if ($user->nationality ?? null)
                            <img src="https://flagcdn.com/48x36/{{ strtolower($user->nationality) }}.png" alt="{{ $user->nationality }}" class="h-4 w-6 border border-line" />
                        @endif

                        <h1 class="truncate text-2xl font-bold uppercase">{{ $user->name ?? 'Unknown' }}</h1>
                    </div>

                    <p class="mt-1.5 font-mono text-xs text-subtle">
                        {{ $user->auth_id }}
                        @if ($steamData['steamid64'] ?? null)
                            · <a href="https://steamcommunity.com/profiles/{{ $steamData['steamid64'] }}" target="_blank" rel="noopener" class="link">steam</a>
                        @endif
                    </p>
                </div>

                <dl class="grid shrink-0 grid-cols-2 gap-px border border-line bg-line sm:w-64">
                    <div class="bg-surface-2 px-3 py-2">
                        <dt class="text-[11px] font-bold uppercase text-muted">Time</dt>
                        <dd class="mt-0.5 font-mono text-lg font-bold leading-none tabular">{{ number_format($totalTimePlayed / 3600, 1) }}h</dd>
                    </div>
                    <div class="bg-surface-2 px-3 py-2">
                        <dt class="text-[11px] font-bold uppercase text-muted">Runs</dt>
                        <dd class="mt-0.5 font-mono text-lg font-bold leading-none tabular text-accent">{{ number_format($totalTimes) }}</dd>
                    </div>
                </dl>
            </div>

            @auth
                <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-2.5" x-data="{ confirming: false }">
                    <p class="me-auto font-mono text-[11px] text-subtle">admin actions</p>

                    <button type="button" class="btn btn-danger btn-sm" x-on:click="confirming = true">Wipe all times</button>

                    <div
                        x-cloak
                        x-show="confirming"
                        x-on:keydown.escape.window="confirming = false"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                    >
                        <div class="panel bracket w-full max-w-md p-6" x-on:click.outside="confirming = false">
                            <p class="hud-label">Destructive</p>
                            <h2 class="mt-1 text-base font-bold uppercase">Wipe every time for {{ $user->name }}?</h2>
                            <p class="mt-2 text-sm text-muted">
                                All {{ number_format($totalTimes) }} runs go, and the replay files of their
                                first-place records are deleted from disk. This cannot be undone.
                            </p>

                            <div class="mt-6 flex justify-end gap-2">
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

        {{-- Tabs --}}
        <div x-data="{ tab: 'records' }" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex border-y border-e border-line bg-surface-1">
                    <button type="button" class="nav-link" :class="tab === 'records' && 'is-active'" x-on:click="tab = 'records'">Records</button>
                    <button type="button" class="nav-link" :class="tab === 'time' && 'is-active'" x-on:click="tab = 'time'">Played time</button>
                </div>

                <div x-show="tab === 'records'" class="w-full sm:w-72">
                    <x-ui.search id="record-search" label="Search records" placeholder="filter by map…" />
                </div>

                <div x-show="tab === 'time'" x-cloak>
                    <label for="chart-range" class="sr-only">Range</label>
                    <select id="chart-range" class="input w-auto">
                        <option value="7">last 7 days</option>
                        <option value="30" selected>last 30 days</option>
                    </select>
                </div>
            </div>

            <div x-show="tab === 'records'">
                <div
                    data-records-table
                    data-input="#record-search"
                    data-sort-by="RecordDate"
                    data-direction="desc"
                    class="transition-opacity"
                >
                    @include('player.partials.latest-times')
                </div>
            </div>

            <div x-show="tab === 'time'" x-cloak>
                @include('player.partials.time-chart')
            </div>
        </div>
    </div>
</x-layouts.app>
