@props([
    'column',
    'sort',
    'direction',
    'label',
    'align' => 'left',
])

{{-- A column header that sorts a Livewire list: wire:click="sortBy('column')". --}}
<th
    {{ $attributes->class(['sortable', 'text-right' => $align === 'right']) }}
    wire:click="sortBy('{{ $column }}')"
    aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"
>
    <span @class(['inline-flex items-center gap-1', 'flex-row-reverse' => $align === 'right'])>
        {{ $label }}
        @if ($sort === $column)
            <x-icon :name="$direction === 'asc' ? 'arrow-up' : 'arrow-down'" class="size-3 text-accent" />
        @endif
    </span>
</th>
