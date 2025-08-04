@extends('layouts.app')

@section('title', 'Map Leaderboard - ' . ($map->name ?? 'Unknown'))

@section('content')

<div class="flex gap-4">
    <div class="w-1/5">
        <h3 class="font-bold mb-2">Categories</h3>
        @foreach ($leaderboards as $categoryId => $records)
    <button class="category-btn block w-full text-left px-4 py-2 mb-1 bg-gray-700 text-white hover:bg-indigo-300 rounded
                   {{ $loop->first ? 'bg-indigo-300' : '' }}"
        data-category-id="{{ $categoryId }}"
        data-category-name="{{ $records->first()->CategoryName }}">
        {{ $records->first()->CategoryName }}
    </button>
@endforeach
    </div>

    <div class="w-4/5">
        <h2 class="text-xl font-semibold mb-4" id="mapTitle">{{ $map->name }}</h2>
        <h2 class="text-xl font-semibold mb-4" id="categoryTitle">Select a category</h2>
        <table class="w-full text-sm text-left hidden" id="leaderboardTable">
            <thead>
                <tr class="bg-gray-800 text-indigo-300">
                    <th class="px-4 py-2">Rank</th>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Time</th>
                    <th class="px-4 py-2">Record Date</th>
                    <th class="px-4 py-2">Start Speed</th>
                </tr>
            </thead>
            <tbody id="leaderboardBody">
                <!-- Filled via JS -->
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.category-btn');
    const table = document.getElementById('leaderboardTable');
    const tbody = document.getElementById('leaderboardBody');
    const title = document.getElementById('categoryTitle');
    const leaderboards = @json($leaderboards);

    function formatTime(ms) {
        const minutes = Math.floor(ms / 60000);
        const seconds = Math.floor((ms % 60000) / 1000);
        const milliseconds = ms % 1000;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}.${milliseconds.toString().padStart(3, '0')}`;
    }

    function activateCategory(button) {
        buttons.forEach(btn => btn.classList.remove('bg-indigo-300'));
        button.classList.add('bg-indigo-300');

        const categoryId = button.dataset.categoryId;
        const categoryName = button.dataset.categoryName;
        const records = leaderboards[categoryId] || [];

        title.textContent = categoryName;
        tbody.innerHTML = '';

        if (records.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-2 text-gray-500">No records</td></tr>`;
        } else {
            records.forEach(record => {
                tbody.innerHTML += `
                    <tr>
                        <td class="px-4 py-2">${record.Rank}</td>
                        <td class="px-4 py-2"><a href="/players/${record.UserUUID}">${record.UserName}</a></td>
                        <td class="px-4 py-2">${formatTime(record.time)}</td>
                        <td class="px-4 py-2">${record.record_date}</td>
                        <td class="px-4 py-2">${record.start_speed ?? ''}</td>
                    </tr>`;
            });
        }

        table.classList.remove('hidden');
    }

    buttons.forEach(btn => {
        btn.addEventListener('click', () => activateCategory(btn));
    });

    if (buttons.length > 0) {
        activateCategory(buttons[0]);
    }
});

</script>

@endsection
