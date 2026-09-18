@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')

@php
    function formatTimePlayed($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
@endphp


<div class="space-y-6">
    <div class="speed-panel rounded-lg p-6">
        <p class="speed-eyebrow text-sm font-bold">Season standings</p>
        <h1 class="mt-2 text-4xl font-black text-white">Leaderboard</h1>
        <p class="speed-muted mt-2 text-sm">Compare server grinders by total playtime or ranked score.</p>

    <div class="mt-6 flex flex-wrap gap-3">
        <button id="btnPlayedTime" 
                class="speed-btn-primary px-5 py-2 text-sm">
            Played Time
        </button>
        <button id="btnRanking" 
                class="speed-btn-secondary px-5 py-2 text-sm">
            Ranking
        </button>
    </div>
    </div>

    <div id="playedTimeBoard" class="speed-panel overflow-hidden rounded-lg">
        <div class="border-b border-cyan-400/10 px-5 py-4">
            <h2 class="text-xl font-semibold text-white">Top Played Time</h2>
            <p class="speed-muted text-sm">Most time spent routing and practicing.</p>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="speed-table-head uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3">Rank</th>
                    <th class="px-5 py-3">Player</th>
                    <th class="px-5 py-3">Time Played</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($topPlayedTimes as $index => $record)
                    <tr class="speed-row transition">
                        <td class="px-5 py-3 font-mono font-semibold text-amber-200">
                            #{{ $index + 1 }}
                        </td>
                        <td class="px-5 py-3 font-medium text-white">{{ $record->name }}</td>
                        <td class="px-5 py-3 font-mono text-cyan-100">{{ formatTimePlayed($record->time_played) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-8 text-center text-slate-500">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="rankingBoard" class="speed-panel hidden overflow-hidden rounded-lg">
    <div class="border-b border-cyan-400/10 px-5 py-4">
        <h2 class="text-xl font-semibold text-white">Top Rankings</h2>
        <p class="speed-muted text-sm">Score and medal totals from ranked completions.</p>
    </div>
    <table class="w-full text-left text-sm">
        <thead class="speed-table-head uppercase tracking-wide">
            <tr>
                <th class="px-5 py-3">Rank</th>
                <th class="px-5 py-3">Player</th>
                <th class="px-5 py-3">Score</th>
                <th class="px-5 py-3">Bronze</th>
                <th class="px-5 py-3">Silver</th>
                <th class="px-5 py-3">Gold</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($topRankings as $index => $ranking)
                <tr class="speed-row transition">
                    <td class="px-5 py-3 font-mono font-semibold text-amber-200">
                        #{{ $index + 1 }}
                    </td>
                    <td class="px-5 py-3 font-medium text-white">{{ $ranking->user->name ?? 'Unknown' }}</td>
                    <td class="px-5 py-3 font-mono text-cyan-100">{{ $ranking->score }}</td>
                    <td class="px-5 py-3">{{ $ranking->bronze }}</td>
                    <td class="px-5 py-3">{{ $ranking->silver }}</td>
                    <td class="px-5 py-3 text-amber-200">{{ $ranking->gold }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">No rankings found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btnPlayedTime = document.getElementById('btnPlayedTime');
    const btnRanking = document.getElementById('btnRanking');
    const playedTimeBoard = document.getElementById('playedTimeBoard');
    const rankingBoard = document.getElementById('rankingBoard');

    function activatePlayedTime() {
        playedTimeBoard.classList.remove('hidden');
        rankingBoard.classList.add('hidden');
        btnPlayedTime.classList.add('speed-btn-primary');
        btnPlayedTime.classList.remove('speed-btn-secondary');
        btnRanking.classList.add('speed-btn-secondary');
        btnRanking.classList.remove('speed-btn-primary');
    }

    function activateRanking() {
        rankingBoard.classList.remove('hidden');
        playedTimeBoard.classList.add('hidden');
        btnRanking.classList.add('speed-btn-primary');
        btnRanking.classList.remove('speed-btn-secondary');
        btnPlayedTime.classList.add('speed-btn-secondary');
        btnPlayedTime.classList.remove('speed-btn-primary');
    }

    btnPlayedTime.addEventListener('click', activatePlayedTime);
    btnRanking.addEventListener('click', activateRanking);

    // Default active
    activatePlayedTime();
});
</script>
@endsection
