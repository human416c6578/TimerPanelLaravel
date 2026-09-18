<x-ui.panel flush>
    <header class="panel-header">
        <p class="hud-label">Minutes per day</p>
        <p class="font-mono text-[11px] text-subtle">one colour per server</p>
    </header>

    <div class="p-4">
        <div class="relative h-[24rem]">
            <canvas
                data-chart="played-time"
                data-source="played-time-data"
                data-range="#chart-range"
            ></canvas>
        </div>
    </div>
</x-ui.panel>

<script type="application/json" id="played-time-data">@json($chartData)</script>
