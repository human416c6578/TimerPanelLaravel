@php
    // The same numbers as the chart, for anyone who cannot read colour, and for
    // days with nothing to plot: only days that had play are listed.
    $days = collect($chartData['labels'] ?? [])->map(function ($label, $index) use ($chartData) {
        $perServer = collect($chartData['datasets'] ?? [])
            ->mapWithKeys(fn ($dataset) => [$dataset['label'] => (float) ($dataset['data'][$index] ?? 0)])
            ->filter(fn ($minutes) => $minutes > 0);

        return ['day' => $label, 'servers' => $perServer];
    })->filter(fn ($row) => $row['servers']->isNotEmpty())->reverse()->values();
@endphp

<x-ui.panel flush>
    <header class="panel-header">
        <p class="hud-label"><x-icon name="bar-chart" class="size-3.5" /> Minutes played per day</p>
    </header>

    <div class="p-4">
        <div class="relative h-[22rem]">
            <canvas data-chart="played-time" data-source="played-time-data" data-range="#chart-range" role="img" aria-label="Minutes played per day, per game server"></canvas>
        </div>
    </div>

    <details class="border-t border-line">
        <summary class="cursor-pointer px-4 py-2.5 text-[12px] font-semibold text-muted hover:text-ink">Table view</summary>

        <x-ui.table min="360px">
            <thead>
                <tr><th>Day</th><th>Server</th><th class="text-right">Played</th></tr>
            </thead>
            <tbody>
                @forelse ($days as $row)
                    @foreach ($row['servers'] as $server => $minutes)
                        <tr>
                            <td class="font-mono text-[12px] text-muted">{{ $loop->first ? $row['day'] : '' }}</td>
                            <td>{{ $server }}</td>
                            <td class="time text-right">@played($minutes * 60)</td>
                        </tr>
                    @endforeach
                @empty
                    <x-ui.empty :colspan="3" message="No play recorded in this window." />
                @endforelse
            </tbody>
        </x-ui.table>
    </details>
</x-ui.panel>

<script type="application/json" id="played-time-data">@json($chartData)</script>
