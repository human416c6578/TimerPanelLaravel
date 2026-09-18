@props(['accent' => false])

<span {{ $attributes->class(['pill', 'pill-accent' => $accent]) }}>{{ $slot }}</span>
