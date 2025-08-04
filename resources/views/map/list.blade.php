@extends('layouts.app')

@section('title', 'Maps')

@section('content')
<div class="max-w-4xl mx-auto mt-6">
    <h1 class="text-2xl font-bold mb-4">Maps</h1>

    <input type="text" id="mapSearch" placeholder="Search maps..." class="w-full px-4 py-2 mb-4 border rounded">

    <ul id="mapList" class="space-y-2">
        @forelse ($maps as $map)
            <li class="bg-gray-800 p-4 rounded hover:bg-gray-700 transition">
                <a href="{{ route('maps.show', $map->uuid) }}" class="text-indigo-300 hover:underline">
                    {{ $map->name }}
                </a>
            </li>
        @empty
            <li class="text-gray-500">No maps found.</li>
        @endforelse
    </ul>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('mapSearch');
        const listItems = document.querySelectorAll('#mapList li');

        input.addEventListener('input', () => {
            const search = input.value.toLowerCase();
            listItems.forEach(li => {
                const text = li.textContent.toLowerCase();
                li.style.display = text.includes(search) ? '' : 'none';
            });
        });
    });
</script>
@endsection
