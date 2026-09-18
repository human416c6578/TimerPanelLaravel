@props(['trail' => []])

{{-- Home » Maps » bhop_arcane --}}
<nav {{ $attributes->class(['breadcrumb']) }} aria-label="Breadcrumb">
    <a href="{{ route('home') }}">Home</a>

    @foreach ($trail as $label => $url)
        <span aria-hidden="true">&raquo;</span>

        @if ($url)
            <a href="{{ $url }}">{{ $label }}</a>
        @else
            <span class="font-bold text-ink">{{ $label }}</span>
        @endif
    @endforeach
</nav>
