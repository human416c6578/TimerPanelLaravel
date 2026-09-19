@props(['name'])

{{--
    A map has no image, so it gets an identity instead: a hue derived from its
    name. The same map is the same colour on every page, the way a game's map
    thumbnails are.
--}}
@php($hue = crc32(strtolower($name)) % 360)

<div {{ $attributes->class(['cover']) }} style="--hue: {{ $hue }}">
    {{ $slot }}
</div>
