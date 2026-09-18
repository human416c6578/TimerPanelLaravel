@extends('layouts.app')

@section('title', 'Players')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="speed-eyebrow text-sm font-bold">Runner directory</p>
            <h1 class="text-3xl font-bold text-white">Players</h1>
            <p class="speed-muted mt-2 text-sm">Search by name or Steam ID and inspect routes, stats, and records.</p>
        </div>

        <div class="w-full md:max-w-md">
            <label for="searchInput" class="sr-only">Search players</label>
            <input
                type="search"
                id="searchInput"
                placeholder="Search players..."
                class="speed-input w-full rounded-lg px-4 py-3 text-sm"
            >
        </div>
    </div>

    <div id="playersTable">
        @include('player.partials.players-table', ['players' => $players])
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    let timer = null;

    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            fetch(`/players?search=${encodeURIComponent(searchInput.value)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('playersTable').innerHTML = html;
            });
        }, 150);
    });

    document.addEventListener('click', function (e) {
        const link = e.target.closest('.pagination a');

        if (!link) {
            return;
        }

        e.preventDefault();
        const url = new URL(link.href);
        url.searchParams.set('ajax', '1');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('playersTable').innerHTML = html;
        });
    });
});
</script>
@endsection
