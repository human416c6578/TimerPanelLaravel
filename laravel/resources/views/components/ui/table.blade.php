@props(['min' => '720px', 'tight' => false])

<div class="overflow-x-auto">
    <table @class(['data-table', 'data-table-tight' => $tight]) style="min-width: {{ $min }}">
        {{ $slot }}
    </table>
</div>
