@props(['code' => null])

@if ($code && preg_match('/^[a-z]{2}$/i', $code))
    <img
        src="https://flagcdn.com/16x12/{{ strtolower($code) }}.png"
        srcset="https://flagcdn.com/32x24/{{ strtolower($code) }}.png 2x"
        width="16" height="12"
        alt="{{ strtoupper($code) }}"
        title="{{ strtoupper($code) }}"
        loading="lazy"
        {{ $attributes->class(['inline-block shrink-0']) }}
    >
@endif
