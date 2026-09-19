<footer class="mt-12 border-t border-line bg-surface-0">
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8">
        <livewire:server-status-bar />

        <div class="flex flex-col gap-2 border-t border-line pt-5 text-[11px] text-subtle sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} &mdash; times are read live from the game servers.</p>

            <nav class="flex flex-wrap gap-x-4 gap-y-1">
                <a href="{{ route('leaderboard.index') }}" class="hover:text-accent">Leaderboards</a>
                <a href="{{ route('maps.index') }}" class="hover:text-accent">Maps</a>
                <a href="{{ route('players.index') }}" class="hover:text-accent">Players</a>
                <a href="{{ route('replays.index') }}" class="hover:text-accent">Replays</a>
            </nav>
        </div>
    </div>
</footer>
