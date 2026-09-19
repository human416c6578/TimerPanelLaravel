@props([
    'title',
    'eyebrow' => null,
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->class(['panel bracket']) }}>
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-center gap-3">
            @if ($icon)
                <span class="hidden size-10 shrink-0 items-center justify-center rounded-sm bg-black/25 text-accent sm:flex">
                    <x-icon :name="$icon" class="size-5" />
                </span>
            @endif

            <div class="min-w-0">
                @if ($eyebrow)
                    <p class="hud-label">{{ $eyebrow }}</p>
                @endif

                <h1 class="text-xl">{{ $title }}</h1>

                @if ($description)
                    <p class="mt-0.5 text-[12px] text-muted">{{ $description }}</p>
                @endif
            </div>
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-1.5">{{ $actions }}</div>
        @endisset
    </div>
</div>
