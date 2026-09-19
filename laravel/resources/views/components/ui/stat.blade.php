@props([
    'label',
    'value',
    'hint' => null,
    'accent' => false,
])

<div class="panel px-3 py-2">
    <p class="text-[11px] font-bold uppercase tracking-wide text-muted">{{ $label }}</p>

    <p @class([
        'mt-0.5 font-mono text-xl font-bold leading-none tabular',
        'text-accent' => $accent,
    ])>{{ $value }}</p>

    @if ($hint)
        <p class="mt-1 text-[11px] text-subtle">{{ $hint }}</p>
    @endif
</div>
