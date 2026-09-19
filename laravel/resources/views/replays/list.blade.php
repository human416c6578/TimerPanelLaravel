<x-layouts.app title="Replays">
    <div class="space-y-2">
        <x-ui.breadcrumb :trail="['Replays' => null]" />

        <x-ui.page-header
            icon="play"
            eyebrow="Replay vault"
            title="Record lines"
            description="The fastest run on every map and category, with its replay attached."
        />

        <livewire:replay-browser />
    </div>
</x-layouts.app>
