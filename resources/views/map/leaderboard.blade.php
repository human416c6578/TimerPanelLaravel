@extends('layouts.app')

@section('title', 'Map Leaderboard - ' . ($map->name ?? 'Unknown'))

@section('content')

<div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
    <div class="speed-panel rounded-lg p-4">
        <p class="speed-eyebrow mb-3 text-xs font-bold">Categories</p>
        @foreach ($leaderboards as $categoryId => $records)
    <button class="category-btn mb-2 block w-full rounded border px-4 py-3 text-left text-sm font-semibold transition
                   {{ $loop->first ? 'border-amber-400/70 bg-amber-400/10 text-white' : 'speed-card text-slate-300' }}"
        data-category-id="{{ $categoryId }}"
        data-category-name="{{ $records->first()->CategoryName }}">
        {{ $records->first()->CategoryName }}
    </button>
@endforeach
    </div>

    <div class="speed-panel overflow-hidden rounded-lg">
        <div class="border-b border-cyan-400/10 px-5 py-4">
            <p class="speed-eyebrow text-xs font-bold">Map leaderboard</p>
            <h2 class="mt-1 text-2xl font-bold text-white" id="mapTitle">{{$map->name}}</h2>
            <h2 class="speed-muted mt-1 text-sm" id="categoryTitle">Select a category</h2>
        </div>
        <div x-data="{ open: false, rank: null, category_name: null, category_id: null, category_id: null, user_uuid: null }">
            <table class="w-full text-sm text-left border-collapse" id="leaderboardTable">
                <thead class="speed-table-head uppercase text-xs tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-2">Rank</th>
                        <th class="px-4 py-2">User</th>
                        <th class="px-4 py-2">Time</th>
                        <th class="px-4 py-2">Record Date</th>
                        <th class="px-4 py-2">Start Speed</th>
                        @auth
                        <th class="px-4 py-2">Action</th>
                        @endauth
                        
                    </tr>
                </thead>
                <tbody id="leaderboardBody" class="divide-y divide-slate-800">
                </tbody>
            </table>
            @auth
            <div x-cloak x-show="open">
                <div class="fixed inset-0 flex items-center justify-center bg-black/70 z-50">
                    <div class="rounded-lg border border-cyan-400/20 bg-slate-950 p-6 text-slate-100 w-96">
                        <h2 class="text-lg font-bold mb-4">Confirm Deletion</h2>
                        <p class="mb-4">Are you sure you want to delete this record? This action cannot be undone.</p>
                        <div class="flex justify-end space-x-2">
                            <button @click="$store.deleteModal.closeModal()" class="speed-btn-secondary py-2 px-4">Cancel</button>
                            <form method="POST" action="{{ route('maps.delete.time', $map->uuid) }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="user_uuid" :value="user_uuid">
                                <input type="hidden" name="category_id" :value="category_id">
                                <input type="hidden" name="category_name" :value="category_name">
                                <input type="hidden" name="rank" :value="rank">
                                <button type="submit" class="speed-btn-danger py-2 px-4">Confirm Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endauth
        </div>
    </div>
        
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.6.1/toastify.min.js" integrity="sha512-79j1YQOJuI8mLseq9icSQKT6bLlLtWknKwj1OpJZMdPt2pFBry3vQTt+NZuJw7NSd1pHhZlu0s12Ngqfa371EA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

@if (session('status'))
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            Toastify({
                text: "{{ session('status') }}",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#4ade80",
            }).showToast();
        });
    </script>
@endif

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
        buttons.forEach(btn => {
            btn.classList.remove('border-amber-400/70', 'bg-amber-400/10', 'text-white');
            btn.classList.add('speed-card', 'text-slate-300');
        });
        button.classList.add('border-amber-400/70', 'bg-amber-400/10', 'text-white');
        button.classList.remove('speed-card', 'text-slate-300');

        const categoryId = button.dataset.categoryId;
        const categoryName = button.dataset.categoryName;
        const records = leaderboards[categoryId] || [];
        console.log(records);

        title.textContent = categoryName;
        tbody.innerHTML = '';

        const isLoggedIn = @json(Auth::check());

        if (records.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No records</td></tr>`;
        } else {
            records.forEach(record => {
                tbody.innerHTML += `
                    <tr class="speed-row transition duration-75">
                        <td class="px-6 py-3 border-slate-700 xl:text-sm text-xs xl:text-medium text-slate-400">
                        ${record.Rank == 1 
                            ? `<a href="/replays/{{$map->uuid}}/${record.CategoryId}" 
                                class="flex flex-row"
                                title="Play Replay">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 fill-amber-300 text-amber-300 cursor-pointer duration-150">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                </svg>

                            </a>`
                            : record.Rank }
                    </td>
                        <td class="px-6 py-3"><a class="speed-link" href="/players/${record.UserUUID}">${record.UserName}</a></td>
                        <td class="px-6 py-3 font-mono text-amber-200">${formatTime(record.time)}</td>
                        <td class="px-6 py-3 text-slate-400">${record.record_date}</td>
                        <td class="px-6 py-3 text-slate-300">${record.start_speed ?? ''}</td>
                        ${isLoggedIn ? `
                        <td>
                            <button 
                                class="speed-btn-danger py-1 px-2 text-xs"
                                x-on:click="open = true; rank = '${record.Rank}'; category_name = '${record.CategoryName}'; category_id = '${record.CategoryId}'; user_uuid= '${record.UserUUID}';">
                                Delete
                            </button>
                        </td>
                        ` : ''}
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
