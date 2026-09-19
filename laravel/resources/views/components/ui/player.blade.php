@props([
    'name',
    'uuid' => null,
    'nationality' => null,
    'avatar' => null,
])

{{--
    A player wherever they appear in a list: Steam picture (or an initial when
    there is none, so rows keep their alignment), country flag, name.
--}}
<span {{ $attributes->class(['inline-flex min-w-0 items-center gap-2']) }}>
    @if ($avatar)
        <img src="{{ $avatar }}" alt="" width="24" height="24" loading="lazy" class="size-6 shrink-0 rounded-md bg-surface-3 object-cover">
    @else
        <span class="grid size-6 shrink-0 place-items-center rounded-md bg-surface-3 text-[10px] font-bold uppercase text-subtle">{{ mb_substr($name, 0, 1) }}</span>
    @endif

    <x-flag :code="$nationality" />

    @if ($uuid)
        <a href="{{ route('players.show', $uuid) }}" class="link truncate">{{ $name }}</a>
    @else
        <span class="truncate font-semibold">{{ $name }}</span>
    @endif
</span>
