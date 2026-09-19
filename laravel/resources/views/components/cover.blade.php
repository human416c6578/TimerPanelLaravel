@props(['name'])

{{--
    A map's identity. Underneath, a hue derived from its name, the same on every
    page. On top, its picture when one is configured and stored locally; if the
    request 404s the image removes itself and the hue is all that shows.
--}}
@php
    $hue = crc32(strtolower($name)) % 360;
    $images = app(\App\Services\MapImages::class);
    // Either a picture is already stored, or a source is configured that may have one.
    $showImage = $images->find($name) !== null || $images->enabled();
@endphp

<div {{ $attributes->class(['cover']) }} style="--hue: {{ $hue }}">
    @if ($showImage)
        <img
            src="{{ route('maps.image', $name) }}"
            alt=""
            loading="lazy"
            onerror="this.remove()"
            class="cover-photo absolute inset-0 -z-20 size-full object-cover"
        >
    @endif

    {{ $slot }}
</div>
