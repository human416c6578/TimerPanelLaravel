<x-layouts.app :title="$mapName.' · '.$categoryName">
    <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_16rem]">
        <x-ui.panel flush>
            <header class="panel-header">
                <div class="min-w-0">
                    <a href="{{ route('maps.show', $time->map_uuid) }}" class="link text-xs">← Map leaderboard</a>
                    <h1 class="mt-1 truncate text-xl font-bold">{{ $mapName }}</h1>
                    <p class="mt-0.5 text-sm text-muted">{{ $categoryName }} · record line</p>
                </div>

                <button type="button" id="downloadBtn" class="btn btn-primary btn-sm">Download .rec</button>
            </header>

            <div class="bg-black p-3">
                <div id="hlv-target" class="h-[420px] overflow-hidden rounded-lg sm:h-[540px] xl:h-[620px]"></div>
            </div>
        </x-ui.panel>

        <aside class="space-y-3">
            <x-ui.panel flush title="Current record" eyebrow="This category">
                <dl class="divide-y divide-line text-sm">
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="text-muted">Time</dt>
                        <dd class="time text-gold">@runtime($time->time)</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="text-muted">Recorded</dt>
                        <dd>{{ $time->record_date ?? 'Unknown' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="text-muted">Start speed</dt>
                        <dd class="tabular">{{ $time->start_speed ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.panel>

            <x-ui.panel flush title="Other categories" eyebrow="Same map">
                <div class="max-h-[26rem] space-y-2 overflow-y-auto p-3">
                    @forelse ($relatedReplays as $replay)
                        @php($isCurrent = (int) $replay->category_id === (int) $time->category_id)

                        <a
                            href="{{ route('replays.show', [$replay->map_uuid, $replay->category_id]) }}"
                            @class([
                                'flex items-start justify-between gap-3 rounded-lg border p-3 transition',
                                'border-accent bg-accent-soft' => $isCurrent,
                                'border-line hover:border-line-strong hover:bg-surface-2' => ! $isCurrent,
                            ])
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $replay->category_name }}</p>
                                <p class="mt-0.5 truncate text-xs text-muted">{{ $replay->user_name }}</p>
                            </div>

                            <span class="time shrink-0 text-xs">@runtime($replay->time)</span>
                        </a>
                    @empty
                        <p class="px-2 py-8 text-center text-sm text-subtle">No other categories on this map.</p>
                    @endforelse
                </div>
            </x-ui.panel>
        </aside>
    </div>

    @push('scripts')
        <script src="{{ asset('js/hlviewer.min.js') }}"></script>
        <script>
            window.addEventListener('load', async () => {
                const urlBhop = @json(config('replays.fastdl.bhop'));
                const urlDr = @json(config('replays.fastdl.deathrun'));
                const downloadUrl = @json(rtrim(config('replays.download_url'), '/'));
                const mapName = @json($mapName);
                const categoryName = @json($categoryName);
                const resourceUrl = mapName.includes('deathrun') ? urlDr : urlBhop;

                const paths = {
                    base: `/proxy?url=${resourceUrl}`,
                    replays: `/proxy?url=${downloadUrl}`,
                    maps: `/proxy?url=${resourceUrl}maps`,
                    wads: `/proxy?url=${resourceUrl}`,
                    skies: `/proxy?url=${resourceUrl}gfx/env`,
                    sounds: `/proxy?url=${resourceUrl}sound`,
                };

                const viewer = HLViewer.init('#hlv-target', { paths });
                await viewer.load(`${mapName}.bsp`);
                await viewer.load(`${mapName}/[${categoryName}].rec`);

                document.getElementById('downloadBtn').addEventListener('click', () => {
                    const link = document.createElement('a');
                    link.href = `${downloadUrl}/${mapName}/[${categoryName}].rec`;
                    link.download = `${mapName} - [${categoryName}].rec`;
                    link.click();
                    link.remove();
                });
            });
        </script>
    @endpush
</x-layouts.app>
