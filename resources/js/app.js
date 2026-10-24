// All assets are bundled locally (no CDNs) so the demo works with no internet access.
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '@fontsource-variable/inter';
import '../css/orbitcare.css';

import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-chart]').forEach((el) => {
        const cfg = JSON.parse(el.dataset.chart);
        const y = { title: { display: true, text: cfg.unit } };
        if (cfg.min !== null) y.min = cfg.min;
        if (cfg.max !== null) y.max = cfg.max;

        new Chart(el, {
            type: cfg.type,
            data: {
                labels: cfg.labels,
                datasets: [{
                    label: cfg.label,
                    data: cfg.data,
                    borderColor: cfg.color,
                    backgroundColor: cfg.fill,
                    borderWidth: 2,
                    tension: 0.25,
                    pointRadius: 3,
                    spanGaps: false, // missing days show as gaps, never as "healthy"
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y, x: { title: { display: true, text: 'Date (UTC)' } } },
            },
        });
    });
});
