@props(['colspan' => 1, 'message' => 'Nothing here yet.'])

<tr>
    <td colspan="{{ $colspan }}" class="px-5 py-12 text-center text-sm text-subtle">
        {{ $slot->isEmpty() ? $message : $slot }}
    </td>
</tr>
