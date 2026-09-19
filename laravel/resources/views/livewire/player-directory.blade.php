<div class="space-y-2">
    <div class="panel flex items-center gap-2 p-2">
        <x-icon name="search" class="text-subtle" />
        <label for="player-search" class="sr-only">Search players</label>
        <input
            id="player-search"
            type="search"
            wire:model.live.debounce.250ms="search"
            placeholder="Search by name or Steam ID"
            autocomplete="off"
            class="input"
        >
        <span wire:loading class="shrink-0 text-[11px] text-subtle">searching…</span>
    </div>

    <x-ui.panel flush>
        <x-ui.table min="520px">
            <thead>
                <tr>
                    <x-ui.sort-th column="name" :sort="$sort" :direction="$direction" label="Player" />
                    <x-ui.sort-th column="auth_id" :sort="$sort" :direction="$direction" label="Steam ID" />
                    <th class="hidden md:table-cell">UUID</th>
                    <th class="text-right">Profile</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($players as $player)
                    <tr wire:key="player-{{ $player->uuid }}">
                        <td>
                            <x-ui.player :name="$player->name" :uuid="$player->uuid" :nationality="$player->nationality" :avatar="$avatars[$player->auth_id] ?? null" />
                        </td>
                        <td class="font-mono text-[11px] text-muted">{{ $player->auth_id }}</td>
                        <td class="hidden font-mono text-[11px] text-subtle md:table-cell">{{ $player->uuid }}</td>
                        <td class="text-right">
                            <a href="{{ route('players.show', $player->uuid) }}" class="btn btn-sm">View profile</a>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty :colspan="4" message="No players match that search." />
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.panel>

    <div>{{ $players->links('components.ui.livewire-pagination') }}</div>
</div>
