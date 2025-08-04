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
        <td class="px-4 py-2">{{ $record->Rank }}</td>
        <td class="px-4 py-2">{{ $record->MapName }}</td>
        <td class="px-4 py-2">{{ $record->CategoryName }}</td>
        <td class="px-4 py-2 font-mono">{{ formatTime($record->Time) }}</td>
        <td class="px-4 py-2">{{ $record->RecordDate }}</td>
    </tr>
@endforeach
</tbody>
</table>
<div id="paginationLinks">
    {{ $latestTimes->links() }}
</div>

