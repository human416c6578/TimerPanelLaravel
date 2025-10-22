@extends('layouts.app')

@section('title', 'Player List')

@section('content')
<div class="mb-4">
    <input type="text" id="searchInput" placeholder="Search players..." class="w-full p-2 border rounded" />
</div>

<div id="playersTable">
    @include('player.partials.players-table', ['players' => $players])
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
        }, 100);
    });

    // handle pagination clicks
    document.addEventListener('click', function (e) {
        if (e.target.closest('.pagination a')) {
            e.preventDefault();
            const url = e.target.closest('.pagination a').href;
            url.searchParams.set("ajax", "1");
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('playersTable').innerHTML = html;
            });
        }
    });
});
</script>
@endsection
