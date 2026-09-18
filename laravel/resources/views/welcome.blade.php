@extends('layouts.app')

@section('title', 'Timer Panel')

@section('content')
@php
    $formatTime = function ($milliseconds) {
        $milliseconds = (int) $milliseconds;
        $minutes = floor($milliseconds / 60000);
        $seconds = floor(($milliseconds % 60000) / 1000);
        $ms = $milliseconds % 1000;

        return sprintf('%02d:%02d.%03d', $minutes, $seconds, $ms);
    };
@endphp

<div class="space-y-6">
    {{-- Top-level 2-column grid: main content | sidebar --}}
    <div class="flex gap-6 md:grid-cols-[1fr_18rem]">
        {{-- LEFT COLUMN: Main content --}}
        <div class="space-y-6 min-w-0">
            {{-- Hero / Stats --}}
            <div class="speed-panel rounded-lg p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="speed-eyebrow text-sm font-bold">CS-GFX Speedrun</p>
                        <h1 class="mt-2 text-3xl font-black text-white sm:text-4xl">Track runs, records, and maps</h1>
                        <p class="speed-muted mt-3">
                            See the newest times, compare leaderboards, find maps, and watch top runs from the server.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('maps.index') }}" class="speed-btn-primary px-4 py-2 text-sm">Browse maps</a>
                        <a href="{{ route('leaderboard.index') }}" class="speed-btn-secondary px-4 py-2 text-sm">Leaderboards</a>
                        <a href="{{ route('replays.index') }}" class="speed-btn-danger px-4 py-2 text-sm">Replays</a>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="speed-card rounded-lg p-4">
                        <p class="speed-muted text-xs uppercase tracking-wide">Players</p>
                        <p class="mt-2 text-2xl font-bold text-white">{{ number_format($homeStats['players']) }}</p>
                    </div>
                    <div class="speed-card rounded-lg p-4">
                        <p class="speed-muted text-xs uppercase tracking-wide">Maps</p>
                        <p class="mt-2 text-2xl font-bold text-white">{{ number_format($homeStats['maps']) }}</p>
                    </div>
                    <div class="speed-card rounded-lg p-4">
                        <p class="speed-muted text-xs uppercase tracking-wide">Categories</p>
                        <p class="mt-2 text-2xl font-bold text-white">{{ number_format($homeStats['categories']) }}</p>
                    </div>
                    <div class="speed-card rounded-lg p-4">
                        <p class="speed-muted text-xs uppercase tracking-wide">Total runs</p>
                        <p class="mt-2 text-2xl font-bold text-white">{{ number_format($homeStats['records']) }}</p>
                    </div>
                </div>
            </div>

            {{-- Recent activity --}}
            <div class="speed-panel rounded-lg">
                <div class="flex flex-col gap-2 border-b border-cyan-400/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Recent activity</h2>
                        <p class="speed-muted text-sm">Latest runs from the last 24 hours.</p>
                    </div>
                    <a href="{{ route('leaderboard.index') }}" class="speed-link text-sm font-medium">View leaderboards</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[920px] text-left text-sm">
                        <thead class="speed-table-head text-xs uppercase tracking-wide">
                            <tr>
                                <th class="px-5 py-3">Rank</th>
                                <th class="px-5 py-3">Player</th>
                                <th class="px-5 py-3">Map</th>
                                <th class="px-5 py-3">Category</th>
                                <th class="px-5 py-3">Time</th>
                                <th class="px-5 py-3">Recorded</th>
                                <th class="px-5 py-3">Start speed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @forelse ($latestTimes as $record)
                                <tr class="speed-row transition">
                                    <td class="px-5 py-4">
                                        @if ((int) $record->rank === 1)
                                            <a href="{{ route('replays.show', [$record->map_uuid, $record->category_id]) }}" class="speed-btn-danger inline-flex items-center px-2.5 py-1 text-xs">
                                                Watch #1
                                            </a>
                                        @else
                                            <span class="font-mono text-gray-300">#{{ $record->rank }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('players.show', $record->user_uuid) }}" class="speed-link font-medium">
                                            {{ $record->user_name }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('maps.show', $record->map_uuid) }}" class="font-medium text-white hover:text-amber-200">
                                            {{ $record->map_name }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="speed-pill rounded px-2.5 py-1 text-xs font-semibold">{{ $record->category_name }}</span>
                                    </td>
                                    <td class="px-5 py-4 font-mono text-amber-200">{{ $formatTime($record->time) }}</td>
                                    <td class="px-5 py-4 text-gray-400">{{ $record->record_date }}</td>
                                    <td class="px-5 py-4 text-gray-300">{{ $record->start_speed ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-gray-500">No records in the last 24 hours.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Sticky sidebar --}}
        <div class="space-y-6 lg:sticky lg:top-4 lg:self-start">
            {{-- Today on the server --}}
            <div class="speed-panel rounded-lg p-4">
                <h2 class="text-base font-semibold text-white">Today on the server</h2>
                <p class="speed-muted mt-1 text-xs">Quick look at current activity.</p>
                <div class="mt-3 grid gap-2">
                    <div class="speed-card rounded-lg p-3">
                        <p class="speed-muted text-xs uppercase tracking-wide">Runs submitted</p>
                        <p class="mt-1 text-xl font-bold text-white">{{ number_format($homeStats['recent_records']) }}</p>
                    </div>
                    <div class="speed-card rounded-lg p-3">
                        <p class="speed-muted text-xs uppercase tracking-wide">Players active</p>
                        <p class="mt-1 text-xl font-bold text-white">{{ number_format($homeStats['active_players']) }}</p>
                    </div>
                </div>
            </div>
            {{-- Live servers — compact --}}
            <div class="speed-panel rounded-lg">
                <div class="flex items-center justify-between border-b border-cyan-400/10 px-4 py-3">
                    <div>
                        <h2 class="text-base font-semibold text-white">Live servers</h2>
                        <p class="speed-muted text-xs">Current map & players.</p>
                    </div>
                    <span class="speed-muted text-[10px]">1m refresh</span>
                </div>

                <div class="space-y-2 p-3">
                    @foreach ($servers as $server)
                        <div class="speed-card rounded-lg p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $server['online'] ? 'bg-cyan-400' : 'bg-slate-600' }}"></span>
                                        <h3 class="truncate text-sm font-bold text-white">{{ $server['name'] }}</h3>
                                    </div>
                                    <p class="speed-muted mt-0.5 truncate text-[10px]">
                                        {{ $server['host'] }}{{ $server['port'] !== 27015 ? ':' . $server['port'] : '' }}
                                        @if (! empty($server['aliases']))
                                            <span class="text-slate-500">/ {{ implode(', ', $server['aliases']) }}</span>
                                        @endif
                                    </p>
                                </div>

                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold {{ $server['online'] ? 'bg-cyan-400/10 text-cyan-200' : 'bg-slate-800 text-slate-400' }}">
                                    {{ $server['online'] ? $server['players'] . '/' . $server['max_players'] : 'DOWN' }}
                                </span>
                            </div>

                            @if ($server['online'])
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <p class="truncate text-xs font-medium text-white">{{ $server['map'] ?? 'Unknown' }}</p>
                                    <a href="steam://connect/{{ $server['host'] }}:{{ $server['port'] }}"
                                       class="shrink-0 inline-flex items-center gap-1 rounded bg-green-500/10 px-2 py-0.5 text-[10px] font-bold text-green-400 hover:bg-green-500/20 transition"
                                       title="Join {{ $server['name'] }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                        </svg>
                                        Connect
                                    </a>
                                </div>
                            @else
                                <p class="mt-1.5 truncate text-xs font-medium text-slate-500">{{ $server['map'] ?? 'Unknown' }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
