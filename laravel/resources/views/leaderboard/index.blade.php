@php
    $podiumOrder = [1, 0, 2]; // silver, gold, bronze — tallest in the middle
@endphp

<x-layouts.app title="Leaderboard">
    <div class="space-y-2" x-data="{ tab: 'played' }">
        <x-ui.breadcrumb :trail="['Rankings' => null]" />

        <x-ui.page-header
            icon="trophy"
            eyebrow="Season standings"
            title="Leaderboard"
            description="Who lives on the server, and who actually finishes maps."
        >
            <x-slot:actions>
                <div class="tabs !border-b-0 bg-transparent">
                    <button type="button" class="tab" :class="tab === 'played' && 'is-active'" x-on:click="tab = 'played'"><x-icon name="clock" class="size-3.5" /> Played time</button>
                    <button type="button" class="tab" :class="tab === 'ranking' && 'is-active'" x-on:click="tab = 'ranking'"><x-icon name="trophy" class="size-3.5" /> Ranking</button>
                </div>
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Played time --}}
        <div x-show="tab === 'played'" class="space-y-2">
            @if ($topPlayedTimes->count() >= 3)
                <div class="grid grid-cols-3 items-end gap-2">
                    @foreach ($podiumOrder as $slot)
                        @php($record = $topPlayedTimes[$slot])
                        @php($place = $slot + 1)

                        <div @class([
                            'panel flex flex-col items-center gap-1 px-3 text-center',
                            'bracket border-gold py-8' => $place === 1,
                            'py-5' => $place !== 1,
                        ])>
                            <x-icon name="crown" @class(['size-6', 'text-gold' => $place === 1, 'text-silver' => $place === 2, 'text-bronze' => $place === 3]) />

                            <p @class([
                                'mt-1.5 w-full truncate font-bold uppercase',
                                'text-lg text-gold' => $place === 1,
                                'text-[12px]' => $place !== 1,
                            ])>{{ $record->name }}</p>

                            <p @class(['time text-xs', 'text-gold/80' => $place === 1, 'text-muted' => $place !== 1])>@played($record->time_played)</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <x-ui.panel flush title="Most time on the server" eyebrow="Hours logged">
                <x-ui.table min="420px">
                    <thead>
                        <tr>
                            <th class="w-20">#</th>
                            <th>Player</th>
                            <th class="text-right">Time played</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topPlayedTimes as $index => $record)
                            <tr>
                                <td><x-ui.rank :rank="$index + 1" /></td>
                                <td class="font-medium">{{ $record->name }}</td>
                                <td class="time text-right">@played($record->time_played)</td>
                            </tr>
                        @empty
                            <x-ui.empty :colspan="3" message="No played time recorded yet." />
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.panel>
        </div>

        {{-- Ranking --}}
        <div x-show="tab === 'ranking'" x-cloak class="space-y-2">
            <x-ui.panel flush title="Ranked score" eyebrow="Medals earned">
                <x-ui.table min="520px">
                    <thead>
                        <tr>
                            <th class="w-20">#</th>
                            <th>Player</th>
                            <th class="text-right">Score</th>
                            <th class="text-right">Gold</th>
                            <th class="text-right">Silver</th>
                            <th class="text-right">Bronze</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topRankings as $index => $ranking)
                            <tr>
                                <td><x-ui.rank :rank="$index + 1" /></td>
                                <td class="font-medium">{{ $ranking->user->name ?? 'Unknown' }}</td>
                                <td class="text-right font-mono font-semibold tabular text-accent">{{ number_format($ranking->score) }}</td>
                                <td class="text-right tabular text-gold">{{ $ranking->gold }}</td>
                                <td class="text-right tabular text-silver">{{ $ranking->silver }}</td>
                                <td class="text-right tabular text-bronze">{{ $ranking->bronze }}</td>
                            </tr>
                        @empty
                            <x-ui.empty :colspan="6" message="No rankings yet." />
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.panel>
        </div>
    </div>
</x-layouts.app>
