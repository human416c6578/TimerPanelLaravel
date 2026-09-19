@use('App\Support\TimeFormat')

@php
    $filtered = $category !== '' || $country !== '' || $recordsOnly;
    $runs = $this->runs;
@endphp

<x-ui.panel flush>
    <header class="panel-header flex-wrap">
        <p class="hud-label"><x-icon name="flame" class="size-3.5" /> Latest runs</p>

        <div class="flex flex-wrap items-center gap-2">
            <label class="sr-only" for="feed-category">Category</label>
            <select id="feed-category" wire:model.live="category" class="input !w-auto !py-1 text-[12px]">
                <option value="">All categories</option>
                @foreach ($this->categories as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

            @if ($this->countries->isNotEmpty())
                <label class="sr-only" for="feed-country">Country</label>
                <select id="feed-country" wire:model.live="country" class="input !w-auto !py-1 text-[12px]">
                    <option value="">All countries</option>
                    @foreach ($this->countries as $code)
                        <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                    @endforeach
                </select>
            @endif

            <button type="button" wire:click="$toggle('recordsOnly')" @class(['btn btn-sm', 'btn-primary' => $recordsOnly, 'btn-ghost' => ! $recordsOnly])>
                <x-icon name="crown" class="size-3" /> Records
            </button>

            @if ($filtered)
                <button type="button" wire:click="clear" class="text-[12px] font-semibold text-muted hover:text-accent">Clear</button>
            @endif
        </div>
    </header>

    <x-ui.table min="700px" tight>
        <thead>
            <tr>
                <th class="w-16">Rank</th>
                <th>Player</th>
                <th>Map</th>
                <th>Category</th>
                <th class="text-right">Time</th>
                <th class="text-right" title="Speed when the run started, in units per second">Start (ups)</th>
                <th class="text-right">Recorded</th>
                <th class="w-12"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($runs as $record)
                @php
                    $isRecord = (int) $record->rank === 1;
                    $runUrl = route('runs.show', [$record->map_uuid, $record->category_id, $record->user_uuid]);
                    $gap = $record->best_time !== null ? (int) $record->time - (int) $record->best_time : null;
                    // Still counting since it was cached.
                    $age = (int) $record->age_seconds + max(0, time() - (int) ($record->cached_at ?? time()));
                @endphp

                <tr
                    wire:key="feed-{{ $record->user_uuid }}-{{ $record->map_uuid }}-{{ $record->category_id }}-{{ $record->time }}"
                    @class(['is-record' => $isRecord, 'cursor-pointer'])
                    x-data
                    x-on:click="if (! $event.target.closest('a, button')) window.location = '{{ $runUrl }}'"
                >
                    <td>
                        @if ($isRecord)
                            <span class="rank rank-1"><x-icon name="crown" class="size-3" /> WR</span>
                        @else
                            <x-ui.rank :rank="$record->rank" />
                        @endif
                    </td>
                    <td class="max-w-[10rem]">
                        <x-ui.player :name="$record->user_name" :uuid="$record->user_uuid" :nationality="$record->nationality" :avatar="$this->avatars[$record->auth_id] ?? null" />
                    </td>
                    <td class="max-w-[9rem] truncate"><a href="{{ route('maps.show', $record->map_uuid) }}" class="text-ink hover:text-accent" title="{{ $record->map_name }}">{{ $record->map_name }}</a></td>
                    <td><x-ui.pill>{{ $record->category_name }}</x-ui.pill></td>
                    <td class="text-right">
                        <a href="{{ $runUrl }}" class="time time-lg block hover:text-accent">{{ TimeFormat::runtime($record->time) }}</a>
                        <span @class(['delta block leading-none', 'delta-record' => $isRecord])>{{ TimeFormat::delta($gap) }}</span>
                    </td>
                    <td class="text-right tabular text-muted">{{ $record->start_speed === null ? '—' : number_format((float) $record->start_speed) }}</td>
                    <td class="whitespace-nowrap text-right text-[12px] text-muted" title="{{ $record->record_date }}">{{ TimeFormat::age($age) }}</td>
                    <td class="text-right">
                        @if ($isRecord)
                            <x-ui.play-button :href="$runUrl.'?tab=replay'" />
                        @endif
                    </td>
                </tr>
            @empty
                <x-ui.empty :colspan="8" :message="$filtered ? 'No runs match those filters.' : 'No runs yet.'" />
            @endforelse
        </tbody>
    </x-ui.table>

    <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-line px-4 py-2.5">
        {{ $runs->links('components.ui.livewire-pagination') }}
        <span wire:loading class="text-[11px] text-subtle">updating…</span>
    </footer>
</x-ui.panel>
