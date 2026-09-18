@extends('layouts.app')

@section('title', $mapName . ' - ' . $categoryName . ' Replay')

@section('content')
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <section class="speed-panel overflow-hidden rounded-lg">
        <div class="flex flex-col gap-3 border-b border-cyan-400/10 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <a href="{{ route('maps.show', $time->map_uuid) }}" class="speed-link text-sm font-medium">Map leaderboard</a>
                <h1 class="mt-1 truncate text-2xl font-bold text-white">{{ $mapName }}</h1>
                <p class="speed-muted text-sm">{{ $categoryName }} replay line</p>
            </div>
            <button
                id="downloadBtn"
                class="speed-btn-danger inline-flex items-center justify-center px-4 py-2 text-sm"
            >
                Download replay
            </button>
        </div>

        <div class="bg-black/40 p-3 sm:p-4">
            <div class="overflow-hidden rounded-lg border border-cyan-400/20 bg-black">
                <div id="hlv-target" class="h-[420px] sm:h-[560px] xl:h-[640px]"></div>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <section class="speed-panel rounded-lg">
            <div class="border-b border-cyan-400/10 px-5 py-4">
                <h2 class="text-lg font-semibold text-white">Categories on this map</h2>
                <p class="speed-muted mt-1 text-sm">Switch to another fastest replay.</p>
            </div>

            <div class="max-h-[430px] overflow-y-auto p-3">
                @forelse ($relatedReplays as $replay)
                    <a
                        href="{{ route('replays.show', [$replay->map_uuid, $replay->category_id]) }}"
                        class="mb-2 block rounded-lg border px-3 py-3 transition {{ (int) $replay->category_id === (int) $time->category_id ? 'border-amber-400/70 bg-amber-400/10' : 'speed-card' }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-white">{{ $replay->category_name }}</p>
                                <p class="speed-muted mt-1 truncate text-xs">{{ $replay->user_name }}</p>
                            </div>
                            <span class="shrink-0 font-mono text-xs text-amber-200">{{ $replay->time }}</span>
                        </div>
                    </a>
                @empty
                    <p class="px-2 py-6 text-center text-sm text-slate-500">No other replay categories found.</p>
                @endforelse
            </div>
        </section>

        <section class="speed-panel rounded-lg p-5">
            <h2 class="text-lg font-semibold text-white">Current record</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="speed-muted">Time</dt>
                    <dd class="font-mono text-amber-200">{{ $time->time }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="speed-muted">Recorded</dt>
                    <dd class="text-slate-200">{{ $time->record_date ?? 'Unknown' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="speed-muted">Start speed</dt>
                    <dd class="text-slate-200">{{ $time->start_speed ?? 'N/A' }}</dd>
                </div>
            </dl>
        </section>
    </aside>
</div>

<script src="{{ asset('js/hlviewer.min.js') }}"></script>

<script>
    window.addEventListener('load', async () => {
        const urlBhop = "http://fastdl.cs-gfx.eu/7c1cc7a5-03d1-4475-93d5-abab1847c0f2/cstrike/";
        const urlDr = "http://fastdl.cs-gfx.eu/0d3d0661-5f4e-45c2-acbc-000eeeccb067/cstrike/";
        const mapName = @json($mapName);
        const categoryName = @json($categoryName);
        const resourceUrl = mapName.includes("deathrun") ? urlDr : urlBhop;

        const paths = {
            base: `/proxy?url=${resourceUrl}`,
            replays: '/proxy?url=https://cs-gfx.eu/uploads/recording',
            maps: `/proxy?url=${resourceUrl}maps`,
            wads: `/proxy?url=${resourceUrl}`,
            skies: `/proxy?url=${resourceUrl}gfx/env`,
            sounds: `/proxy?url=${resourceUrl}sound`,
        };

        const viewer = HLViewer.init('#hlv-target', { paths });
        await viewer.load(`${mapName}.bsp`);
        await viewer.load(`${mapName}/[${categoryName}].rec`);

        document.getElementById("downloadBtn").addEventListener("click", () => {
            const link = document.createElement("a");
            link.href = `https://cs-gfx.eu/uploads/recording/${mapName}/[${categoryName}].rec`;
            link.download = `${mapName} - [${categoryName}].rec`;
            link.click();
            link.remove();
        });
    });
</script>
@endsection
