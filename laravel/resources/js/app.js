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
 * Search-as-you-type + paginate-without-reloading for the server-rendered
 * tables. The markup declares everything:
 *
 *   <div data-live-table data-endpoint="/players" data-input="#player-search">
 *       ...server-rendered partial...
 *   </div>
 *
 * The endpoint returns just the table partial when the request is XHR, which
 * is what PlayerController@index and ReplayController@index already do.
 */
function liveTable(root) {
    const input = root.dataset.input ? document.querySelector(root.dataset.input) : null;
    const endpoint = root.dataset.endpoint || window.location.pathname;
    const delay = Number(root.dataset.delay || 150);
    let timer = null;
    let controller = null;

    const load = (url, { push = true } = {}) => {
        controller?.abort();
        controller = new AbortController();
        root.classList.add('opacity-50');

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        })
            .then((response) => response.text())
            .then((html) => {
                root.innerHTML = html;
                root.classList.remove('opacity-50');

                if (push) {
                    window.history.replaceState({}, '', url);
                }
            })
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    root.classList.remove('opacity-50');
                    toast('Could not load that page.', { type: 'error' });
                }
            });
    };

    input?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('search', input.value);
            url.searchParams.delete('page');
            load(url);
        }, delay);
    });

    root.addEventListener('click', (event) => {
        const link = event.target.closest('.pagination a');

        if (!link) {
            return;
        }

        event.preventDefault();
        const url = new URL(link.href);
        url.searchParams.set('ajax', '1');
        load(url);
    });
}

/**
 * Sortable, searchable record table on the player profile. The server renders
 * the rows and the pagination; this only swaps them out.
 */
function recordsTable(root) {
    const input = root.dataset.input ? document.querySelector(root.dataset.input) : null;
    let sortBy = root.dataset.sortBy || 'RecordDate';
    let direction = root.dataset.direction || 'desc';
    let timer = null;
    let controller = null;

    const load = (page = 1) => {
        controller?.abort();
        controller = new AbortController();
        root.classList.add('opacity-50');

        const url = new URL(window.location.href);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('page', page);
        url.searchParams.set('search', input?.value ?? '');
        url.searchParams.set('sort_by', sortBy);
        url.searchParams.set('direction', direction);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        })
            .then((response) => response.text())
            .then((html) => {
                root.innerHTML = html;
                root.classList.remove('opacity-50');
                markSortedColumn();
            })
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    root.classList.remove('opacity-50');
                }
            });
    };

    const markSortedColumn = () => {
        root.querySelectorAll('th[data-sort]').forEach((th) => {
            const active = th.dataset.sort === sortBy;
            th.dataset.active = active ? direction : '';
            const caret = th.querySelector('[data-caret]');

            if (caret) {
                caret.textContent = active ? (direction === 'asc' ? '↑' : '↓') : '';
            }
        });
    };

    input?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => load(1), 300);
    });

    root.addEventListener('click', (event) => {
        const header = event.target.closest('th[data-sort]');

        if (header) {
            const column = header.dataset.sort;
            direction = sortBy === column && direction === 'asc' ? 'desc' : 'asc';
            sortBy = column;
            load(1);

            return;
        }

        const link = event.target.closest('.pagination a');

        if (link) {
            event.preventDefault();
            const page = new URL(link.href).searchParams.get('page') || 1;
            load(page);
        }
    });

    markSortedColumn();
}

/**
 * Played time per day, one bar group per game server. The controller hands us
 * { labels, datasets:[{ label, stack, data }] } with the values in minutes.
 */
function playedTimeChart(canvas) {
    const payload = JSON.parse(document.getElementById(canvas.dataset.source)?.textContent || '{}');
    const rangeSelect = canvas.dataset.range ? document.querySelector(canvas.dataset.range) : null;
    const palette = [
        token('--accent', '#22d3ee'),
        token('--gold', '#fbbf24'),
        token('--positive', '#4ade80'),
        token('--bronze', '#d97706'),
        token('--silver', '#cbd5e1'),
    ];
    const grid = token('--line', 'rgba(255,255,255,0.08)');
    const label = token('--muted', '#93a4b3');
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
                borderRadius: 3,
                borderSkipped: false,
                maxBarThickness: 28,
            })),
        };
    };

    const chart = new Chart(canvas, {
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
                x: { grid: { display: false }, ticks: { color: label, maxRotation: 0, autoSkipPadding: 16 } },
                y: {
                    beginAtZero: true,
                    grid: { color: grid },
                    ticks: { color: label, callback: (value) => minutes(value) },
                },
            },
        },
    });

    rangeSelect?.addEventListener('change', () => {
        chart.data = slice(rangeSelect.value);
        chart.update();
    });

    return chart;
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
    once('[data-live-table]', liveTable);
    once('[data-records-table]', recordsTable);
    once('[data-chart="played-time"]', playedTimeChart);
    flashMessages();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);

window.TimerPanel = { formatRuntime, formatPlayedTime, toast, Chart };
