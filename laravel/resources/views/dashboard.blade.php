<x-layouts.app :title="__('Admin Dashboard')">
    @php
        $formatTime = function ($milliseconds) {
            $milliseconds = (int) $milliseconds;
            $minutes = floor($milliseconds / 60000);
            $seconds = floor(($milliseconds % 60000) / 1000);
            $ms = $milliseconds % 1000;

            return sprintf('%02d:%02d.%03d', $minutes, $seconds, $ms);
        };
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-500 dark:text-cyan-300">Admin panel</p>
                <h1 class="mt-1 text-3xl font-black text-zinc-900 dark:text-white">Server overview</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Manage leaderboards, monitor recent activity, and jump into moderation screens.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('home') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">View site</a>
                <a href="{{ route('maps.index') }}" class="rounded-md border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-500/20 dark:text-cyan-200">Maps</a>
                <a href="{{ route('players.index') }}" class="rounded-md border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-500/20 dark:text-amber-200">Players</a>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Players</p>
                <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['players']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Maps</p>
                <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['maps']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Total runs</p>
                <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['times']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Ranked rows</p>
                <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['ranked_times']) }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Latest runs</h2>
                    <p class="text-sm text-zinc-500">Newest submissions across all maps.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-zinc-100 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th class="px-5 py-3">Rank</th>
                                <th class="px-5 py-3">Player</th>
                                <th class="px-5 py-3">Map</th>
                                <th class="px-5 py-3">Category</th>
                                <th class="px-5 py-3">Time</th>
                                <th class="px-5 py-3">Recorded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($latestRecords as $record)
                                <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                                    <td class="px-5 py-4">
                                        @if ((int) $record->rank === 1)
                                            <a href="{{ route('replays.show', [$record->map_uuid, $record->category_id]) }}" class="rounded bg-red-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-red-500">Replay</a>
                                        @else
                                            <span class="font-mono text-zinc-500">#{{ $record->rank ?? '-' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('players.show', $record->user_uuid) }}" class="font-semibold text-cyan-700 hover:text-cyan-600 dark:text-cyan-300">{{ $record->user_name }}</a>
                                    </td>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('maps.show', $record->map_uuid) }}" class="font-semibold text-zinc-900 hover:text-amber-600 dark:text-white dark:hover:text-amber-200">{{ $record->map_name }}</a>
                                    </td>
                                    <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $record->category_name }}</td>
                                    <td class="px-5 py-4 font-mono text-amber-700 dark:text-amber-200">{{ $formatTime($record->time) }}</td>
                                    <td class="px-5 py-4 text-zinc-500">{{ $record->record_date }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-zinc-500">No recent runs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Today</h2>
                    <div class="mt-4 grid gap-3">
                        <div class="rounded-md bg-zinc-100 p-4 dark:bg-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Runs submitted</p>
                            <p class="mt-2 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['recent_times']) }}</p>
                        </div>
                        <div class="rounded-md bg-zinc-100 p-4 dark:bg-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Active players</p>
                            <p class="mt-2 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['active_players']) }}</p>
                        </div>
                        <div class="rounded-md bg-zinc-100 p-4 dark:bg-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Categories</p>
                            <p class="mt-2 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['categories']) }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Maintenance</h2>
                    <div class="mt-4 space-y-3">
                        <form method="POST" action="{{ route('dashboard.leaderboards.refresh') }}">
                            @csrf
                            <button class="w-full rounded-md bg-cyan-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-cyan-500" type="submit">
                                Refresh leaderboards
                            </button>
                        </form>

                        <form method="POST" action="{{ route('dashboard.cache.clear') }}">
                            @csrf
                            <button class="w-full rounded-md border border-zinc-300 px-4 py-2 text-sm font-bold text-zinc-800 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" type="submit">
                                Clear cache
                            </button>
                        </form>
                    </div>
                </section>

                <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Moderation</h2>
                    <div class="mt-4 grid gap-2 text-sm">
                        <a href="{{ route('players.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">Find player times</a>
                        <a href="{{ route('maps.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">Open map records</a>
                        <a href="{{ route('replays.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">Review replays</a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>
