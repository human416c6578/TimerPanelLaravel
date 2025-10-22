@php 
    function formatTime($milliseconds) 
    { 
        $minutes = floor($milliseconds / 60000); 
        $seconds = floor(($milliseconds % 60000) / 1000); 
        $ms = $milliseconds % 1000; 

        return sprintf("%02d:%02d.%03d", $minutes, $seconds, $ms);
    } 
@endphp 
<table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-800 text-indigo-300 sticky top-0 z-10">
                        <tr>
                            <th class="cursor-pointer px-4 py-2" data-sort="Rank">Rank ↑↓</th>
                            <th class="cursor-pointer px-4 py-2" data-sort="MapName">Map ↑↓</th>
                            <th class="cursor-pointer px-4 py-2" data-sort="CategoryName">Category ↑↓</th>
                            <th class="px-4 py-2">Time</th>
                            <th class="cursor-pointer px-4 py-2" data-sort="RecordDate">Date ↑↓</th>
                        </tr>
                    </thead>
<tbody>
@foreach ($latestTimes as $record)
    <tr>
        <td class="px-4 py-2">
            @if($record->Rank == 1)
                <a href="/replays/{{ $record->MapUUID }}/{{ $record->CategoryId }}"
                    class="flex flex-row"
                    title="Play Replay">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 hover:fill-blue-500 fill-yellow-400 cursor-pointer duration-150">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                    </svg>
                </a>
            @else 
                {{$record->Rank}}
            @endif
        </td>
        <td class="px-4 py-2">
            <a class="hover:underline" href="/maps/{{ $record->MapUUID }}">
                {{ $record->MapName }}
            </a>
        </td>
        <td class="px-4 py-2">{{ $record->CategoryName }}</td>
        <td class="px-4 py-2 font-mono">{{ formatTime($record->Time) }}</td>
        <td class="px-4 py-2">{{ $record->RecordDate }}</td>

        <td class="px-6 py-3 border-b border-slate-700 dark:border-slate-700 xl:text-sm text-xs xl:text-medium text-slate-400 dark:text-slate-400">
                        
                    </td>
    </tr>
@endforeach
</tbody>
</table>
<div id="paginationLinks">
    {{ $latestTimes->links() }}
</div>

