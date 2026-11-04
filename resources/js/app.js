// All assets are bundled locally (no CDNs) so the demo works with no internet access.
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '@fontsource-variable/inter';
import '../css/orbitcare.css';

import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;

const preferredTheme = () => localStorage.getItem('orbitcare-theme')
    ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'night' : 'day');

const applyTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem('orbitcare-theme', theme);
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const night = theme === 'night';
        button.setAttribute('aria-pressed', String(night));
        button.setAttribute('aria-label', `Switch to ${night ? 'day' : 'night'} theme`);
        button.innerHTML = `<i class="bi ${night ? 'bi-sun' : 'bi-moon-stars'}" aria-hidden="true"></i><span class="d-none d-sm-inline">${night ? 'Day' : 'Night'}</span>`;
    });
};

applyTheme(preferredTheme());

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => button.addEventListener('click', () => {
        applyTheme(document.documentElement.dataset.theme === 'night' ? 'day' : 'night');
        window.dispatchEvent(new Event('orbitcare:theme-change'));
    }));

    document.querySelectorAll('canvas[data-chart]').forEach((el) => {
        const cfg = JSON.parse(el.dataset.chart);
        let chart;
        const render = () => {
            chart?.destroy();
            const night = document.documentElement.dataset.theme === 'night';
            const y = { title: { display: true, text: cfg.unit, color: night ? '#94A3B8' : '#475569' }, ticks: { color: night ? '#CBD5E1' : '#475569' }, grid: { color: night ? '#26334A' : '#E2E8F0' } };
            if (cfg.min !== null) y.min = cfg.min;
            if (cfg.max !== null) y.max = cfg.max;

            chart = new Chart(el, {
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
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => {
                        const meta = cfg.meta?.[context.dataIndex];
                        return `${context.dataset.label}: ${context.formattedValue} ${cfg.unit}${meta ? ` · ${meta.source} · ${meta.time} UTC` : ''}`;
                    } } },
                },
                scales: { y, x: { title: { display: true, text: 'Date (UTC)', color: night ? '#94A3B8' : '#475569' }, ticks: { color: night ? '#CBD5E1' : '#475569' }, grid: { color: night ? '#26334A' : '#E2E8F0' } } },
            },
            });
        };
        render();
        window.addEventListener('orbitcare:theme-change', render);
    });
});
