@props(['run', 'userUuid'])

@use('App\Support\TimeFormat')

{{-- One run in the form strip. Colour is the tier; the tooltip says exactly which run. --}}
<a
    href="{{ route('runs.show', [$run->mapUuid, $run->categoryId, $userUuid]) }}"
    class="relative block"
    x-data="{ tip: false }"
    x-on:mouseenter="tip = true"
    x-on:mouseleave="tip = false"
    x-on:focus="tip = true"
    x-on:blur="tip = false"
    aria-label="{{ $run->map }}, {{ $run->category }}, rank {{ $run->rank }}, {{ TimeFormat::runtime($run->time) }}"
>
    <span class="form-dot" data-tier="{{ $run->tier }}"></span>

    <span
        x-show="tip" x-cloak
        class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-2 w-max max-w-[15rem] -translate-x-1/2 rounded-lg border border-line-strong bg-surface-1 px-3 py-2 text-left shadow-xl"
    >
        <span class="block truncate text-[12px] font-bold">{{ $run->map }}</span>
        <span class="block text-[11px] text-muted">{{ $run->category }}</span>
        <span class="mt-1 flex items-center gap-2 text-[12px]">
            <b class="font-mono">{{ TimeFormat::runtime($run->time) }}</b>
            <span class="text-subtle">#{{ $run->rank }} · {{ TimeFormat::delta($run->delta) }}</span>
        </span>
    </span>
</a>
