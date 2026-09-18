@props([
    'title' => null,
    'eyebrow' => null,
    'flush' => false,
    'bracket' => false,
])

<section {{ $attributes->class(['panel', 'panel-flush' => $flush, 'bracket' => $bracket]) }}>
    @if ($title || $eyebrow || isset($actions))
        <header class="panel-header">
            <div class="min-w-0">
                @if ($eyebrow)
                    <p class="hud-label">{{ $eyebrow }}</p>
                @endif

                @if ($title)
                    <h2 class="truncate text-sm font-semibold uppercase tracking-wide">{{ $title }}</h2>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    {{ $slot }}
</section>
