@props([
    'title',
    'description' => null,
])

<div class="flex w-full flex-col text-start">
    <h1 class="text-sm font-bold uppercase">{{ $title }}</h1>

    @if ($description)
        <p class="mt-0.5 text-[11px] text-muted">{{ $description }}</p>
    @endif
</div>
