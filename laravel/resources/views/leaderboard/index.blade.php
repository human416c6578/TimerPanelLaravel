<x-layouts.app title="Leaderboards">
    <div class="space-y-3">
        <x-ui.breadcrumb :trail="['Leaderboards' => null]" />

        <x-ui.page-header
            icon="trophy"
            eyebrow="Season standings"
            title="Leaderboards"
            description="Score, medals, world records and time on the server, in one table. Click a column to order it."
        />

        <livewire:leaderboard-table />
    </div>
</x-layouts.app>
