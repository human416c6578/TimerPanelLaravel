@props(['min' => '720px'])

<div class="overflow-x-auto">
    <table class="data-table" style="min-width: {{ $min }}">
        {{ $slot }}
    </table>
</div>
