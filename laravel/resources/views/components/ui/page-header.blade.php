@props([
    'title',
    'eyebrow' => null,
    'description' => null,
])

<div {{ $attributes->class(['panel']) }}>
    <div class="flex flex-col gap-3 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-base font-bold uppercase tracking-wide">{{ $title }}</h1>

            @if ($eyebrow || $description)
                <p class="mt-0.5 truncate text-[11px] text-muted">
                    {{ $eyebrow }}@if ($eyebrow && $description) &middot; @endif{{ $description }}
                </p>
            @endif
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-1.5">{{ $actions }}</div>
        @endisset
    </div>
</div>
