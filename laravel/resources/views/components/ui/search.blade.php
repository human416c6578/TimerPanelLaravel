@props([
    'id' => 'search',
    'placeholder' => 'search...',
    'value' => '',
    'label' => 'Search',
])

<div {{ $attributes->only('class')->class(['flex items-center gap-1.5']) }}>
    <label for="{{ $id }}" class="shrink-0 text-[11px] font-bold uppercase text-muted">{{ $label }}</label>

    <input
        id="{{ $id }}"
        type="search"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        class="input"
        {{ $attributes->except('class') }}
    >
</div>
