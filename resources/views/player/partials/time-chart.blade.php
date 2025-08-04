<div class="bg-gray-900 p-6 rounded-lg shadow-lg text-white">
    <select id="rangeSelector" style="margin-bottom: 1rem;">
        <option value="7">Last 7 Days</option>
        <option value="30" selected>Last 30 Days</option>
    </select>

    <div class="relative h-[550px]">
        <canvas id="playedTimeChart"></canvas>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let originalData = {!! $chartData !!};
    let chartInstance;

    function getStackColor(stack) {
        const colors = [
            '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6',
            '#ec4899', '#14b8a6', '#84cc16', '#eab308'
        ];
        let index = [...new Set(originalData.datasets.map(d => d.stack))].indexOf(stack);
        return colors[index % colors.length];
    }

    originalData.datasets.forEach(ds => {
        ds.backgroundColor = getStackColor(ds.stack);
    });

    function getFilteredChartData(range) {
        const total = originalData.labels.length;
        const start = Math.max(0, total - range);

        return {
            labels: originalData.labels.slice(start),
            datasets: originalData.datasets.map(ds => ({
                ...ds,
                data: ds.data.slice(start),
                backgroundColor: getStackColor(ds.stack),
            }))
        };
    }

    function renderChart(data) {
        const ctx = document.getElementById('playedTimeChart').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: false,
                        title: {
                            display: true,
                            text: 'Days',
                            color: 'white'
                        },
                        ticks: {
                            color: 'white'
                        }
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        min: 0,
                        title: {
                            display: true,
                            text: 'Minutes',
                            color: 'white'
                        },
                        ticks: {
                            color: 'white'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: { size: 18 },
                            color: '#6366f1',
                            generateLabels: (chart) => {
                                const labels = [];
                                const stacks = [...new Set(chart.data.datasets.map(ds => ds.stack))];

                                stacks.forEach(stack => {
                                    const dsIndex = chart.data.datasets.findIndex(ds => ds.stack === stack);
                                    const meta = chart.getDatasetMeta(dsIndex);
                                    const isHidden = meta.hidden;
                                    labels.push({
                                        text: stack,
                                        fillStyle: isHidden? 'gray' : getStackColor(stack),
                                        fontColor: 'white',
                                        hidden: isHidden,
                                    });
                                });
                                return labels;
                            }
                        },
                        onClick: (e, legendItem, legend) => {
                            const chart = legend.chart;
                            const stackId = legendItem.text;
                            const areVisible = chart.data.datasets.some((dataset) =>
                                dataset.stack === stackId &&
                                chart.getDatasetMeta(chart.data.datasets.indexOf(dataset)).hidden !== true
                            );
                            chart.data.datasets.forEach((dataset, i) => {
                                if (dataset.stack === stackId) {
                                    chart.getDatasetMeta(i).hidden = areVisible;
                                }
                            });
                            chart.update();
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: function (tooltipItems) {
                                const item = tooltipItems[0];
                                const date = data.labels[item.dataIndex];
                                return `${item.dataset.label} - ${date}`;
                            },
                            label: function (tooltipItem) {
                                const value = tooltipItem.raw;
                                return `Total Minutes: ${value}`;
                            }
                        }
                    }
                }
            }
        });
    }

    document.getElementById('rangeSelector').addEventListener('change', function () {
        const range = parseInt(this.value);
        const filteredData = getFilteredChartData(range);
        renderChart(filteredData);
    });

    renderChart(originalData);
</script>

@endpush
