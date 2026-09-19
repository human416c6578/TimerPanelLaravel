@props([
    'value' => null,
    'best' => null,
    'decimals' => 0,
    'suffix' => '',
])

{{-- A stat in a table cell: a dash when the run predates the stat, boxed when it leads the column. --}}
@if ($value === null)
    <span class="text-subtle">—</span>
@else
    @php($text = number_format((float) $value, $decimals).$suffix)

    @if ($best !== null && (float) $value === (float) $best)
        <span class="best tabular font-semibold" title="Best on this category">{{ $text }}</span>
    @else
        <span class="tabular text-muted">{{ $text }}</span>
    @endif
@endif
