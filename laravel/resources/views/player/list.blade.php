<x-layouts.app title="Players">
    <div class="space-y-2">
        <x-ui.breadcrumb :trail="['Players' => null]" />

        <x-ui.page-header
            icon="users"
            eyebrow="Runner directory"
            title="Players"
            description="Search by name or Steam ID, then open a profile for records, medals and played time."
        />

        <livewire:player-directory />
    </div>
</x-layouts.app>
