@extends('layouts.app')

@section('content')
<div class="w-4/5 mx-auto rounded-xl shadow-lg bg-gray-900 max-h-[80vh] overflow-y-auto">
    <table class="w-full text-sm text-left border-collapse">
        <thead class="bg-gray-800 text-indigo-300 uppercase text-xs tracking-wider sticky top-0 z-10">
            <tr>
                <th class="px-6 py-3">Rank</th>
                <th class="px-6 py-3">User</th>
                <th class="px-6 py-3">Map</th>
                <th class="px-6 py-3">Category</th>
                <th class="px-6 py-3">Time</th>
                <th class="px-6 py-3">Record Date</th>
                <th class="px-6 py-3">Start Speed</th>
            </tr>
        </thead>
        <tbody id="leaderboardBody" class="divide-y divide-gray-700">
           
        </tbody>
    </table>
</div>



<script>
    function formatTime(ms) {
        const minutes = Math.floor(ms / 60000);
        const seconds = Math.floor((ms % 60000) / 1000);
        const milliseconds = ms % 1000;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}.${milliseconds.toString().padStart(3, '0')}`;
    }

    const tbody = document.getElementById('leaderboardBody');
    const records = @json($latestTimes);
    console.log(records);

    if (records.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-2 text-gray-500">No records</td></tr>`;
    } else {
        records.forEach(record => {
            tbody.innerHTML += `
                <tr class="hover:bg-gray-800 transition duration-75">
                    <td class="px-6 py-3 border-b border-slate-700 dark:border-slate-700 xl:text-sm text-xs xl:text-medium text-slate-400 dark:text-slate-400">
                        ${record.rank == 1 
                            ? `<a href="/replays/${record.map_uuid}/${record.category_id}" 
                                class="flex flex-row"
                                title="Play Replay">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 hover:fill-blue-500 fill-yellow-400 cursor-pointer duration-150">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                </svg>

                            </a>`
                            : record.rank }
                    </td>
                    <td class="px-6 py-3 text-gray-200">
                        <a href="/players/${record.user_uuid}" class="hover:underline">${record.user_name}</a>
                    </td>
                    <td class="px-6 py-3 text-gray-200">
                        <a href="/maps/${record.map_uuid}" class="hover:underline">${record.map_name}</a>
                    </td>
                    <td class="px-6 py-3 text-gray-300">${record.category_name}</td>
                    <td class="px-6 py-3 text-gray-300">${formatTime(record.time)}</td>
                    <td class="px-6 py-3 text-gray-400">${record.record_date}</td>
                    <td class="px-6 py-3 text-gray-300">${record.start_speed ?? ''}</td>
                </tr>`
        });
    }

    </script>

@endsection
