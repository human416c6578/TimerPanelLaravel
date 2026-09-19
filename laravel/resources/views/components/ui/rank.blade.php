@props(['rank' => null])

@php($rank = $rank === null || $rank === '' ? null : (int) $rank)

<span @class([
    'rank',
    'rank-1' => $rank === 1,
    'rank-2' => $rank === 2,
    'rank-3' => $rank === 3,
])>{{ $rank === null ? '—' : '#'.$rank }}</span>
