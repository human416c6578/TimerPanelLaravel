import Chart from 'chart.js/auto';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

/**
 * Read a CSS custom property off <html>, so charts and toasts use the same
 * palette as the stylesheet instead of their own hardcoded hex values.
 */
const token = (name, fallback = '') =>
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;

/**
 * The one runtime formatter. The game stores run times as milliseconds; this
 * matches App\Support\TimeFormat::runtime() on the PHP side exactly.
 */
export function formatRuntime(milliseconds) {
    const value = Number(milliseconds);

    if (!Number.isFinite(value) || value < 0) {
        return '—';
    }

    const pad = (n, size = 2) => String(Math.floor(n)).padStart(size, '0');
    const hours = Math.floor(value / 3600000);
    const minutes = Math.floor((value % 3600000) / 60000);
    const seconds = Math.floor((value % 60000) / 1000);
    const millis = Math.floor(value % 1000);
    const tail = `${pad(minutes)}:${pad(seconds)}.${pad(millis, 3)}`;

    return hours > 0 ? `${hours}:${tail}` : tail;
}

/** Gap to a record in ms: +0.421, +1:02.310, or WR when it is the record. */
export function formatDelta(milliseconds) {
    const value = Number(milliseconds);

    if (!Number.isFinite(value)) {
        return '—';
    }

    if (value <= 0) {
        return 'WR';
    }

    if (value < 60000) {
        return `+${Math.floor(value / 1000)}.${String(Math.floor(value % 1000)).padStart(3, '0')}`;
    }

    return `+${formatRuntime(value)}`;
}

/** Played time, stored as seconds, rendered hh:mm:ss. */
export function formatPlayedTime(seconds) {
    const value = Math.max(0, Number(seconds) || 0);
    const pad = (n) => String(Math.floor(n)).padStart(2, '0');

    return `${pad(value / 3600)}:${pad((value % 3600) / 60)}:${pad(value % 60)}`;
}

export function toast(message, { type = 'success' } = {}) {
    const background = type === 'error' ? token('--danger', '#dc2626') : token('--accent', '#0e7490');

    Toastify({
        text: message,
        duration: 4000,
        gravity: 'bottom',
        position: 'right',
        close: true,
        style: {
            background,
            color: token('--accent-foreground', '#fff'),
            borderRadius: '0.5rem',
            fontWeight: '600',
        },
    }).showToast();
}

/**
 * Played time per day, one bar group per game server. The controller hands us
 * { labels, datasets:[{ label, stack, data }] } with the values in minutes.
 */
function playedTimeChart(canvas) {
    const payload = JSON.parse(document.getElementById(canvas.dataset.source)?.textContent || '{}');
    const rangeSelect = canvas.dataset.range ? document.querySelector(canvas.dataset.range) : null;
    // The dataviz skill's validated categorical slots, in fixed order. Never the
    // accent or the record colour: those already mean something.
    const palette = [
        token('--series-1', '#3987e5'),
        token('--series-2', '#d95926'),
        token('--series-3', '#199e70'),
    ];
    const grid = token('--line', 'rgba(255,255,255,0.08)');
    const label = token('--muted', '#9aa1b8');
    const tick = token('--subtle', '#6a7189');
    const minutes = (value) => formatPlayedTime(Number(value) * 60);

    const slice = (days) => {
        const labels = payload.labels ?? [];
        const from = Math.max(0, labels.length - Number(days));

        return {
            labels: labels.slice(from),
            datasets: (payload.datasets ?? []).map((dataset, index) => ({
                ...dataset,
                data: (dataset.data ?? []).slice(from),
                backgroundColor: palette[index % palette.length],
                // <=24px, rounded at the data end, square on the baseline.
                borderRadius: { topLeft: 4, topRight: 4 },
                borderSkipped: 'bottom',
                maxBarThickness: 24,
                barPercentage: 0.8,
                categoryPercentage: 0.8,
            })),
        };
    };

    let chart = null;

    const create = () => new Chart(canvas, {
        type: 'bar',
        data: slice(rangeSelect?.value ?? payload.labels?.length ?? 30),
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    labels: { color: label, boxWidth: 10, boxHeight: 10, usePointStyle: true },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => ` ${context.dataset.label}: ${minutes(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: tick, maxRotation: 0, autoSkipPadding: 16 } },
                y: {
                    beginAtZero: true,
                    grid: { color: grid },
                    border: { display: false },
                    ticks: { color: tick, callback: (value) => minutes(value) },
                },
            },
        },
    });

    // The chart sits on a tab that starts hidden. Chart.js sizes itself from
    // the canvas at construction, so building it while display:none leaves the
    // bars laid out at zero width. Wait until the canvas is actually on screen.
    const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            observer.disconnect();
            chart = create();
        }
    });

    observer.observe(canvas);

    rangeSelect?.addEventListener('change', () => {
        if (!chart) {
            return;
        }

        chart.data = slice(rangeSelect.value);
        chart.update();
    });

    return canvas;
}

/** Flash messages handed over by the server as a JSON island. */
function flashMessages() {
    const node = document.getElementById('flash-message');

    if (!node) {
        return;
    }

    const { message, type } = JSON.parse(node.textContent || '{}');

    if (message) {
        toast(message, { type });
    }

    node.remove();
}

/**
 * Livewire fires `livewire:navigated` on first load as well as after a
 * wire:navigate swap, so boot() runs more than once per page. Each element is
 * only ever wired up once; nodes that arrive with a new page are untouched and
 * get picked up normally.
 */
function once(selector, initialise) {
    document.querySelectorAll(selector).forEach((node) => {
        if (node.dataset.tpReady) {
            return;
        }

        node.dataset.tpReady = '1';
        initialise(node);
    });
}

function boot() {
    once('[data-chart="played-time"]', playedTimeChart);
    flashMessages();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);

window.TimerPanel = { formatRuntime, formatDelta, formatPlayedTime, toast, Chart };
