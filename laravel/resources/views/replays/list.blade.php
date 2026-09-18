@extends('layouts.app')

@section('title', 'Replays')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="speed-eyebrow text-sm font-bold">Replay vault</p>
            <h1 class="text-3xl font-bold text-white">Record lines</h1>
            <p class="speed-muted mt-2 text-sm">Fastest available replay for each map and category.</p>
        </div>

        <form method="GET" action="{{ route('replays.index') }}" class="w-full md:max-w-md">
            <label for="replaySearch" class="sr-only">Search replays</label>
            <div class="flex overflow-hidden rounded-lg border border-cyan-400/20 bg-black/30 shadow-sm">
                <input
                    id="replaySearch"
                    name="search"
                    value="{{ $search }}"
                    type="search"
                    placeholder="Search map, category, or player"
                    class="min-w-0 flex-1 bg-transparent px-4 py-3 text-sm text-white outline-none placeholder:text-slate-500"
                >
                <button class="speed-btn-primary px-5 text-sm">
                    Search
                </button>
            </div>
        </form>
    </div>

    <div id="replaysTable">
        @include('replays.partials.replays-table', ['replays' => $replays])
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('replaySearch');
    const table = document.getElementById('replaysTable');
    let timer = null;
    let controller = null;

    const loadReplays = (url) => {
        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        table.classList.add('opacity-60');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: controller.signal
        })
        .then(res => res.text())
        .then(html => {
            table.innerHTML = html;
            table.classList.remove('opacity-60');
        })
        .catch(error => {
            if (error.name !== 'AbortError') {
                table.classList.remove('opacity-60');
            }
        });
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = new URL(window.location.href);
            url.pathname = @json(route('replays.index', [], false));
            url.searchParams.set('search', input.value);
            url.searchParams.delete('page');
            loadReplays(url);
            window.history.replaceState({}, '', url);
        }, 150);
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('#replaysTable .pagination a');

        if (!link) {
            return;
        }

        event.preventDefault();
        const url = new URL(link.href);
        loadReplays(url);
        window.history.replaceState({}, '', url);
    });
});
</script>
@endsection
