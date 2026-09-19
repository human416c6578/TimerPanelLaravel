@props(['href'])

{{-- Watch the replay of a record: just the play icon, named for screen readers and on hover. --}}
<a href="{{ $href }}" class="btn btn-primary btn-sm !px-2" title="Watch the replay" aria-label="Watch the replay">
    <x-icon name="play" class="size-3.5" />
</a>
