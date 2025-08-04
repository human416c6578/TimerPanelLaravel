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


<div class="container mx-auto px-4 py-6 text-gray-300 bg-gray-900 rounded-lg shadow-lg">
    <h1 class="text-4xl font-extrabold mb-6 text-indigo-400 drop-shadow-lg">Leaderboard</h1>

    <!-- Toggle buttons -->
    <div class="mb-6 flex space-x-4">
        <button id="btnPlayedTime" 
                class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 font-semibold shadow-md">
            ⏱️ Played Time
        </button>
        <button id="btnRanking" 
                class="px-5 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 font-semibold shadow-md text-gray-400">
            🏆 Ranking
        </button>
    </div>

    <!-- Played Time Leaderboard -->
    <div id="playedTimeBoard" class="rounded-lg border border-indigo-600 overflow-hidden shadow-md">
        <h2 class="text-2xl font-semibold mb-4 text-indigo-300 px-4 py-2 bg-indigo-900 border-b border-indigo-600 drop-shadow-md">⏱️ Top Played Time</h2>
        <table class="w-full text-left">
            <thead class="bg-indigo-700 text-indigo-200 uppercase tracking-wider select-none">
                <tr>
                    <th class="px-5 py-3">🏅 Rank</th>
                    <th class="px-5 py-3">Player</th>
                    <th class="px-5 py-3">Time Played (hours)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topPlayedTimes as $index => $record)
                    <tr class="{{ $index % 2 === 0 ? 'bg-indigo-900/50' : 'bg-indigo-900/30' }} hover:bg-indigo-800 transition-colors duration-300">
                        <td class="px-5 py-3 font-semibold text-indigo-400">
                            {!! $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : $index + 1)) !!}
                        </td>
                        <td class="px-5 py-3 font-medium text-indigo-100">{{ $record->name }}</td>
                        <td class="px-5 py-3">{{ formatTimePlayed($record->time_played) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-3 text-center text-gray-500">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Ranking Leaderboard -->
    <div id="rankingBoard" class="hidden rounded-lg border border-green-600 overflow-hidden shadow-md">
    <h2 class="text-2xl font-semibold mb-4 text-green-400 px-4 py-2 bg-green-900 border-b border-green-600 drop-shadow-md">
        🏆 Top Rankings (Score & Medals)
    </h2>
    <table class="w-full text-left">
        <thead class="bg-green-700 text-green-200 uppercase tracking-wider select-none">
            <tr>
                <th class="px-5 py-3">🏅 Rank</th>
                <th class="px-5 py-3">Player</th>
                <th class="px-5 py-3">Score</th>
                <th class="px-5 py-3">🥉 Bronze</th>
                <th class="px-5 py-3">🥈 Silver</th>
                <th class="px-5 py-3">🥇 Gold</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topRankings as $index => $ranking)
                <tr class="{{ $index % 2 === 0 ? 'bg-green-900/50' : 'bg-green-900/30' }} hover:bg-green-800 transition-colors duration-300">
                    <td class="px-5 py-3 font-semibold text-green-400">
                        {!! $index === 0 ? '🏆' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : $index + 1)) !!}
                    </td>
                    <td class="px-5 py-3 font-medium text-green-100">{{ $ranking->user->name ?? 'Unknown' }}</td>
                    <td class="px-5 py-3">{{ $ranking->score }}</td>
                    <td class="px-5 py-3">{{ $ranking->bronze }}</td>
                    <td class="px-5 py-3">{{ $ranking->silver }}</td>
                    <td class="px-5 py-3">{{ $ranking->gold }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-3 text-center text-gray-500">No rankings found.</td>
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
        btnPlayedTime.classList.add('bg-indigo-600', 'text-white');
        btnPlayedTime.classList.remove('bg-gray-700', 'text-gray-400');
        btnRanking.classList.remove('bg-yellow-600', 'text-white');
        btnRanking.classList.add('bg-gray-700', 'text-gray-400');
    }

    function activateRanking() {
        rankingBoard.classList.remove('hidden');
        playedTimeBoard.classList.add('hidden');
        btnRanking.classList.add('bg-yellow-600', 'text-white');
        btnRanking.classList.remove('bg-gray-700', 'text-gray-400');
        btnPlayedTime.classList.remove('bg-indigo-600', 'text-white');
        btnPlayedTime.classList.add('bg-gray-700', 'text-gray-400');
    }

    btnPlayedTime.addEventListener('click', activatePlayedTime);
    btnRanking.addEventListener('click', activateRanking);

    // Default active
    activatePlayedTime();
});
</script>
@endsection
