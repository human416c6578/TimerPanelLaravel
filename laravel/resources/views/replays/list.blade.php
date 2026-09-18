<x-layouts.app title="Replays">
    <div class="space-y-3">
        <x-ui.page-header
            eyebrow="Replay vault"
            title="Record lines"
            description="The fastest run on every map and category, with the replay attached."
        >
            <x-slot:actions>
                <x-ui.search
                    id="replay-search"
                    label="Search replays"
                    placeholder="Map, category or player…"
                    :value="$search"
                    class="w-full md:w-80"
                />
            </x-slot:actions>
        </x-ui.page-header>

        <div
            data-live-table
            data-endpoint="{{ route('replays.index') }}"
            data-input="#replay-search"
            class="transition-opacity"
        >
            @include('replays.partials.replays-table')
        </div>
    </div>
</x-layouts.app>
