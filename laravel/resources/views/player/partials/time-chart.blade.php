<x-ui.panel flush>
    <header class="panel-header">
        <p class="hud-label"><x-icon name="bar-chart" class="size-3.5" /> Minutes per day</p>
        <p class="text-[11px] text-subtle">one colour per server</p>
    </header>

    <div class="p-4">
        <div class="relative h-[22rem]">
            <canvas
                data-chart="played-time"
                data-source="played-time-data"
                data-range="#chart-range"
            ></canvas>
        </div>
    </div>
</x-ui.panel>

<script type="application/json" id="played-time-data">@json($chartData)</script>
