<x-layouts.app :title="$map->name">
    <div class="space-y-2">
        <x-ui.breadcrumb :trail="['Maps' => route('maps.index'), $map->name => null]" />

        <livewire:map-leaderboard :map-uuid="$map->uuid" :map-name="$map->name" />
    </div>
</x-layouts.app>
