@extends('layouts.app')

@section('title', 'Player Profile - ' . ($user->name ?? 'Unknown'))


@section('content')
<div class="flex gap-10 min-h-[75vh]">

    <!-- Profile Card -->
<div class="w-full md:w-1/3 bg-gray-900 text-white rounded-xl shadow-lg p-6 space-y-6">
    <h2 class="text-2xl font-bold text-indigo-400 border-b border-gray-700 pb-2">Player Profile</h2>

    @if($steamData)
        <div class="flex flex-col items-center space-y-3">
            <a href="https://steamcommunity.com/profiles/{{ $steamData['steamid64'] ?? $user->auth_id }}" target="_blank">
                <img src="{{ $steamData['avatar'] }}" alt="Avatar" class="w-32 h-32 rounded-full shadow-lg hover:scale-105 transition" />
            </a>

            <div class="flex items-center space-x-2">
                @if($user->nationality)
                    <img src="https://flagcdn.com/48x36/{{ strtolower($user->nationality) }}.png" alt="{{ $user->nationality }}" class="w-6 h-4 rounded shadow" />
                @endif
                <a href="https://steamcommunity.com/profiles/{{ $steamData['steamid64'] ?? $user->auth_id  }}" target="_blank"
                   class="text-xl font-semibold hover:underline">
                    {{ $user->name ?? 'Unknown' }}
                </a>
            </div>
        </div>
    @endif

    <div class="space-y-2">
        <div>
            <p class="text-sm text-indigo-300 uppercase font-semibold">Auth ID</p>
            <p class="text-sm font-mono text-gray-400 break-words">{{ $user->auth_id }}</p>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="pt-4 border-t border-gray-700 space-y-3">
        <h3 class="text-lg font-bold text-indigo-400">Stats</h3>
        
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-indigo-300 uppercase font-semibold">Total Time</p>
                <p class="text-indigo-100 font-bold">{{ round($totalTimePlayed / 60 / 60, 2) }} hrs</p>
            </div>

            <div>
                <p class="text-indigo-300 uppercase font-semibold">Total Runs</p>
                <p class="text-indigo-100 font-bold">{{ $totalTimes }}</p>
            </div>

            <div>
        @auth
        <div x-data="{ open: false }">
            <!-- Delete button triggers modal -->
            <button 
                @click="open = true"
                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                Delete Player Times
            </button>

            <!-- Modal -->
            <div 
                x-show="open" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="z-2 fixed inset-0 flex items-center justify-center bg-black/70 bg-opacity-50"
                style="display: none;">
                <div 
                class="bg-white text-black rounded-lg p-6 w-96">
                    <h2 class="text-lg font-bold mb-4">Confirm Deletion</h2>
                    <p class="mb-4">Are you sure you want to delete all times for this player? This action cannot be undone.</p>
                    <div class="flex justify-end space-x-2">
                        <button 
                            @click="open = false" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('players.delete.times', $user->uuid) }}">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Confirm Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endauth

</div>


            {{-- Add more stats if available --}}
            {{-- 
            <div>
                <p class="text-indigo-300 uppercase font-semibold">Maps Finished</p>
                <p class="text-indigo-100 font-bold">XX</p>
            </div>
            --}}
        </div>
    </div>
</div>


    <!-- Right Section with Tabs -->
<div class="flex-1 text-white">
    <div class="bg-gray-900 rounded-xl shadow-lg h-[750px]">
        <!-- Tabs -->
        <div class="flex border-b border-gray-700 text-indigo-400">
            <button class="tab-button px-6 py-3 font-semibold hover:bg-gray-800 active" data-tab="time">Played Time</button>
            <button class="tab-button px-6 py-3 font-semibold hover:bg-gray-800" data-tab="records">Records</button>
        </div>

        <!-- Time Played Tab -->
        <div class="tab-content p-6" id="tab-time">
            @include('player.partials.time-chart', ['chartData' => json_encode($chartData)])
        </div>

        <!-- Records Tab -->
        <div class="tab-content p-6 hidden" id="tab-records">
            <div class="flex items-center gap-4 mb-4">
                <input
                    type="text"
                    id="searchInput"
                    class="bg-gray-800 border border-gray-600 rounded px-4 py-2 w-full text-white placeholder-gray-400"
                    placeholder="Search by map"
                />
            </div>
            <div 
                id="tableWrapper"
                class="overflow-auto max-h-[600px]">
                    @include('player.partials.latest-times', ['latestTimes' => $latestTimes])
            </div>
        </div>
    </div>
</div>


</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.6.1/toastify.min.js" integrity="sha512-79j1YQOJuI8mLseq9icSQKT6bLlLtWknKwj1OpJZMdPt2pFBry3vQTt+NZuJw7NSd1pHhZlu0s12Ngqfa371EA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

@if (session('status'))
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            Toastify({
                text: "{{ session('status') }}",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#4ade80", // green
            }).showToast();
        });
    </script>
@endif
<script>
    // Tabs toggle
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active', 'border-b-2', 'border-indigo-500'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));

            btn.classList.add('active', 'border-b-2', 'border-indigo-500');
            document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
        });
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const table = document.querySelector("table");
        const paginationContainer = document.getElementById("paginationLinks");

        let currentSort = {
            column: null,
            direction: "asc"
        };

        let timeoutId;

        document.getElementById("searchInput").addEventListener("input", function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                fetchLatestTimes(1);
            }, 300);
        });

        // Sorting handler
        table.querySelectorAll("[data-sort]").forEach(header => {
            header.addEventListener("click", function () {
                const column = this.getAttribute("data-sort");

                // Toggle direction
                currentSort.direction = (currentSort.column === column && currentSort.direction === "asc") ? "desc" : "asc";
                currentSort.column = column;

                fetchLatestTimes(1); // Reset to page 1 on new sort
            });
        });

        // Pagination handler (delegated)
        paginationContainer.addEventListener("click", function (e) {
            const link = e.target.closest("a");

            if (link) {
                e.preventDefault();
                const page = new URL(link.href).searchParams.get("page");
                fetchLatestTimes(page);
            }
        });

        function fetchLatestTimes(page = 1) {
            const url = new URL(window.location.href);
            url.searchParams.set("ajax", "1");
            url.searchParams.set("page", page);
            url.searchParams.set("search", document.getElementById("searchInput").value);
            if (currentSort.column) {
                url.searchParams.set("sort_by", currentSort.column);
                url.searchParams.set("direction", currentSort.direction);
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;

                const newTbody = tempDiv.querySelector("tbody");
                const newPagination = tempDiv.querySelector("#paginationLinks");

                document.querySelector("tbody").innerHTML = newTbody.innerHTML;
                document.getElementById("paginationLinks").innerHTML = newPagination.innerHTML;
            });
        }
    });
</script>
@endsection
