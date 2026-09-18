@extends('layouts.app')

@section('title', 'Player Profile - ' . ($user->name ?? 'Unknown'))


@section('content')
<div class="grid min-h-[75vh] gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">

<div class="speed-panel rounded-lg p-6 text-white space-y-6">
    <div class="border-b border-cyan-400/10 pb-4">
        <p class="speed-eyebrow text-xs font-bold">Runner profile</p>
        <h2 class="mt-1 text-2xl font-bold text-white">Player Profile</h2>
    </div>

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
                   class="speed-link text-xl font-semibold">
                    {{ $user->name ?? 'Unknown' }}
                </a>
            </div>
        </div>
    @endif

    <div class="space-y-2">
        <div>
            <p class="speed-eyebrow text-sm font-semibold">Auth ID</p>
            <p class="speed-muted text-sm font-mono break-words">{{ $user->auth_id }}</p>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="space-y-3 border-t border-cyan-400/10 pt-4">
        <h3 class="text-lg font-bold text-white">Stats</h3>
        
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="speed-eyebrow text-xs font-semibold">Total Time</p>
                <p class="font-bold text-amber-200">{{ round($totalTimePlayed / 60 / 60, 2) }} hrs</p>
            </div>

            <div>
                <p class="speed-eyebrow text-xs font-semibold">Total Runs</p>
                <p class="font-bold text-amber-200">{{ $totalTimes }}</p>
            </div>

            <div>
        @auth
        <div x-data="{ open: false }">
            <!-- Delete button triggers modal -->
            <button 
                @click="open = true"
                class="speed-btn-danger py-2 px-4">
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
                class="rounded-lg border border-cyan-400/20 bg-slate-950 p-6 text-slate-100 w-96">
                    <h2 class="text-lg font-bold mb-4">Confirm Deletion</h2>
                    <p class="mb-4">Are you sure you want to delete all times for this player? This action cannot be undone.</p>
                    <div class="flex justify-end space-x-2">
                        <button 
                            @click="open = false" 
                            class="speed-btn-secondary py-2 px-4">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('players.delete.times', $user->uuid) }}">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                class="speed-btn-danger py-2 px-4">
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
                <p class="speed-eyebrow font-semibold">Maps Finished</p>
                <p class="font-bold text-amber-200">XX</p>
            </div>
            --}}
        </div>
    </div>
</div>


    <!-- Right Section with Tabs -->
<div class="text-white">
    <div class="speed-panel rounded-lg h-[750px]">
        <div class="flex border-b border-cyan-400/10 text-cyan-200">
            <button class="tab-button px-6 py-3 font-semibold hover:bg-cyan-400/10 active" data-tab="time">Played Time</button>
            <button class="tab-button px-6 py-3 font-semibold hover:bg-cyan-400/10" data-tab="records">Records</button>
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
                    class="speed-input rounded px-4 py-2 w-full"
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
            document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active', 'border-b-2', 'border-amber-400'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));

            btn.classList.add('active', 'border-b-2', 'border-amber-400');
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
