@use('App\Support\TimeFormat')

@php
    $columns = [
        'score' => ['Score', 'text-right'],
        'played' => ['Time played', 'text-right'],
        'records' => ['Records', 'text-right'],
        'gold' => ['Gold', 'text-right'],
        'silver' => ['Silver', 'text-right hidden sm:table-cell'],
        'bronze' => ['Bronze', 'text-right hidden sm:table-cell'],
    ];
    $medalTone = ['gold' => 'text-gold', 'silver' => 'text-silver', 'bronze' => 'text-bronze'];
@endphp

<div class="space-y-2">
    <x-ui.panel flush>
        <x-ui.table min="640px">
            <thead>
                <tr>
                    <th class="w-16">#</th>
                    <th>Player</th>
                    @foreach ($columns as $key => [$label, $align])
                        <th @class(['sortable', $align, 'text-ink' => $sort === $key]) wire:click="sortBy('{{ $key }}')" aria-sort="{{ $sort === $key ? 'descending' : 'none' }}">
                            <span class="inline-flex items-center gap-1 {{ str_contains($align, 'text-right') ? 'flex-row-reverse' : '' }}">
                                @if (isset($medalTone[$key]))
                                    <x-icon name="medal" @class(['size-3.5', $medalTone[$key]]) />
                                @endif
                                {{ $label }}
                                @if ($sort === $key)
                                    <x-icon name="arrow-down" class="size-3 text-accent" />
                                @endif
                            </span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $index => $entry)
                    @php($place = $entries->firstItem() + $index)

                    <tr wire:key="lb-{{ $entry->uuid }}" @class(['lb-row', 'lb-row-'.$place => $place <= 3])>
                        <td>
                            @if ($place <= 3)
                                <span class="rank rank-{{ $place }}"><x-icon name="crown" class="size-3" />{{ $place }}</span>
                            @else
                                <x-ui.rank :rank="$place" />
                            @endif
                        </td>
                        <td><x-ui.player :name="$entry->name" :uuid="$entry->uuid" :nationality="$entry->nationality" :avatar="$avatars[$entry->auth_id] ?? null" /></td>
                        <td @class(['text-right tabular', 'font-bold' => $sort === 'score'])>{{ number_format($entry->score) }}</td>
                        <td @class(['time text-right', 'font-bold' => $sort === 'played'])>{{ TimeFormat::played($entry->played) }}</td>
                        <td @class(['text-right tabular', 'font-bold' => $sort === 'records'])>
                            @if ($entry->records) <span class="inline-flex items-center gap-1"><x-icon name="crown" class="size-3 text-gold" />{{ number_format($entry->records) }}</span> @else <span class="text-subtle">0</span> @endif
                        </td>
                        <td class="text-right tabular text-gold">{{ number_format($entry->gold) }}</td>
                        <td class="hidden text-right tabular text-silver sm:table-cell">{{ number_format($entry->silver) }}</td>
                        <td class="hidden text-right tabular text-bronze sm:table-cell">{{ number_format($entry->bronze) }}</td>
                    </tr>
                @empty
                    <x-ui.empty :colspan="8" message="Nobody is on the board yet." />
                @endforelse
            </tbody>
        </x-ui.table>

        <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-line px-4 py-2.5">
            {{ $entries->links('components.ui.livewire-pagination') }}
            <span class="text-[11px] text-subtle">ordered by {{ $columns[$sort][0] ?? 'score' }}</span>
        </footer>
    </x-ui.panel>
</div>
