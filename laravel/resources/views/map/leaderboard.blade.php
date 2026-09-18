@php
    $categories = $leaderboards
        ->map(fn ($records, $id) => [
            'id' => (string) $id,
            'name' => $records->first()->CategoryName,
            'count' => $records->count(),
        ])
        ->values();

    $mode = str_contains(strtolower($map->name), 'deathrun') ? 'Deathrun' : 'Bhop';
@endphp

<x-layouts.app :title="$map->name">
    <div
        class="space-y-5"
        x-data="{
            boards: {{ Js::from($leaderboards) }},
            categories: {{ Js::from($categories) }},
            current: {{ Js::from($categories->first()['id'] ?? null) }},
            pending: null,
            get records() {
                return this.boards[this.current] ?? [];
            },
            get best() {
                return this.records.find((record) => record.Rank === 1) ?? null;
            },
            runtime: (ms) => window.TimerPanel.formatRuntime(ms),
            playerUrl: (uuid) => {{ Js::from(route('players.show', 'PLAYER_UUID')) }}.replace('PLAYER_UUID', uuid),
            replayUrl: (categoryId) => {{ Js::from(route('replays.show', [$map->uuid, 'CATEGORY_ID'])) }}.replace('CATEGORY_ID', categoryId),
        }"
    >
        {{-- Map hero --}}
        <section class="panel bracket">
            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="hud-label">Map leaderboard</p>
                    <h1 class="display-xl mt-1 break-all">{{ $map->name }}</h1>

                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <x-ui.pill accent>{{ $mode }}</x-ui.pill>
                        <x-ui.pill>{{ $categories->count() }} categories</x-ui.pill>
                        <span class="font-mono text-[11px] text-subtle">{{ $map->uuid }}</span>
                    </div>
                </div>

                <div class="shrink-0 border border-line bg-surface-2 px-4 py-2 text-right" x-show="best">
                    <p class="text-[11px] font-bold uppercase text-muted">World record</p>
                    <p class="time mt-1 text-2xl leading-none text-gold" x-text="best ? runtime(best.time) : ''"></p>
                    <p class="mt-1 text-xs text-muted">
                        by <a class="link" :href="best ? playerUrl(best.UserUUID) : '#'" x-text="best?.UserName"></a>
                    </p>
                </div>
            </div>

            {{-- Category tabs --}}
            <div class="flex overflow-x-auto border-t border-line">
                <template x-for="category in categories" :key="category.id">
                    <button
                        type="button"
                        class="nav-link shrink-0 border-e border-line"
                        :class="category.id === current && 'is-active'"
                        x-on:click="current = category.id"
                    >
                        <span x-text="category.name"></span>
                        <span class="font-mono text-[10px] opacity-60" x-text="category.count"></span>
                    </button>
                </template>
            </div>
        </section>

        <x-ui.panel flush>
            <x-ui.table min="560px">
                <thead>
                    <tr>
                        <th class="w-20">Rank</th>
                        <th class="w-32 text-right">Time</th>
                        <th>Player</th>
                        <th class="hidden text-right md:table-cell">Start speed</th>
                        <th class="hidden sm:table-cell">Date</th>
                        @auth
                            <th class="text-right">Action</th>
                        @endauth
                    </tr>
                </thead>
                <tbody>
                    <template x-for="record in records" :key="record.UserUUID + '-' + record.Rank">
                        <tr>
                            <td>
                                <template x-if="record.Rank === 1">
                                    <a :href="replayUrl(record.CategoryId)" class="rank rank-1" title="Watch the replay">
                                        <svg class="size-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z" />
                                        </svg>
                                        1
                                    </a>
                                </template>

                                <template x-if="record.Rank !== 1">
                                    <span class="rank" :class="record.Rank === 2 ? 'rank-2' : (record.Rank === 3 ? 'rank-3' : '')" x-text="'#' + record.Rank"></span>
                                </template>
                            </td>
                            <td class="time time-lg text-right" :class="record.Rank === 1 && 'text-gold'" x-text="runtime(record.time)"></td>
                            <td><a class="link" :href="playerUrl(record.UserUUID)" x-text="record.UserName"></a></td>
                            <td class="hidden text-right tabular text-muted md:table-cell" x-text="record.start_speed ?? '—'"></td>
                            <td class="hidden font-mono text-xs text-subtle sm:table-cell" x-text="record.record_date"></td>
                            @auth
                                <td class="text-right">
                                    <button type="button" class="btn btn-danger btn-sm" x-on:click="pending = record">Delete</button>
                                </td>
                            @endauth
                        </tr>
                    </template>

                    <template x-if="records.length === 0">
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-subtle">No records on this category yet.</td>
                        </tr>
                    </template>
                </tbody>
            </x-ui.table>
        </x-ui.panel>

        @auth
            <div
                x-cloak
                x-show="pending"
                x-on:keydown.escape.window="pending = null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            >
                <div class="panel bracket w-full max-w-md p-6" x-on:click.outside="pending = null">
                    <p class="hud-label">Destructive</p>
                    <h2 class="mt-2 font-display text-lg font-bold uppercase">Delete this record?</h2>
                    <p class="mt-2 text-sm text-muted">
                        <span x-text="pending?.UserName"></span> ·
                        <span class="time" x-text="pending ? runtime(pending.time) : ''"></span> ·
                        <span x-text="pending?.CategoryName"></span>.
                        The replay file goes with it, and this cannot be undone.
                    </p>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" class="btn btn-ghost" x-on:click="pending = null">Cancel</button>

                        <form method="POST" action="{{ route('maps.delete.time', $map->uuid) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="user_uuid" :value="pending?.UserUUID">
                            <input type="hidden" name="category_id" :value="pending?.CategoryId">
                            <input type="hidden" name="category_name" :value="pending?.CategoryName">
                            <input type="hidden" name="rank" :value="pending?.Rank">
                            <button type="submit" class="btn btn-danger">Delete record</button>
                        </form>
                    </div>
                </div>
            </div>
        @endauth
    </div>
</x-layouts.app>
