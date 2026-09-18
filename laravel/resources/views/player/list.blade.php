<x-layouts.app title="Players">
    <div class="space-y-3">
        <x-ui.page-header
            eyebrow="Runner directory"
            title="Players"
            description="Search by name or Steam ID, then open a profile for records and played time."
        >
            <x-slot:actions>
                <x-ui.search
                    id="player-search"
                    label="Search players"
                    placeholder="Name or Steam ID…"
                    :value="request('search')"
                    class="w-full md:w-80"
                />
            </x-slot:actions>
        </x-ui.page-header>

        <div
            data-live-table
            data-endpoint="{{ route('players.index') }}"
            data-input="#player-search"
            class="transition-opacity"
        >
            @include('player.partials.players-table')
        </div>
    </div>
</x-layouts.app>
