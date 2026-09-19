<x-layouts.app title="Maps">
    <div class="space-y-2">
        <x-ui.breadcrumb :trail="['Maps' => null]" />

        <x-ui.page-header
            icon="map"
            eyebrow="Course library"
            title="Maps"
            :description="number_format($total).' routes on the servers. Pick one and see who owns it.'"
        />

        <livewire:map-directory />
    </div>
</x-layouts.app>
