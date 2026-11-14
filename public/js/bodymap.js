// Body Map: interactive 2D SVG figure + detail panel.
// Region statuses come from /api/body-map (same rules engine as Alerts).
(function () {
    'use strict';

    const NS = 'http://www.w3.org/2000/svg';
    const FULL = { x: 0, y: 0, w: 300, h: 640 };
    const ASPECT = FULL.w / FULL.h;

    const STATUS = {
        nominal: { label: 'Nominal', color: '#15803D', icon: 'bi-check-circle' },
        watch: { label: 'Monitoring', color: '#64748B', icon: 'bi-eye' },
        review: { label: 'Review', color: '#B45309', icon: 'bi-exclamation-circle' },
        urgent: { label: 'Priority review', color: '#B91C1C', icon: 'bi-exclamation-triangle-fill' },
    };

    // Which figure shape a seeded region maps to. An exact svg_id match wins;
    // otherwise these patterns are tried against slug + svg_id + name.
    const KEY_RULES = [
        ['eyes', /eye|ocular|vision/],
        ['heart', /heart|cardi/],
        ['lungs', /lung|respir|pulmon/],
        ['spine', /spine|spinal|vertebr/],
        ['long-bones', /long.?bone|bone|skelet/],
        ['upper-muscles', /upper/],
        ['lower-muscles', /lower/],
        ['digestive', /digest|gut|stomach|visceral|gastro/],
        ['head', /head|brain|neuro|cerebr/],
        ['skin', /skin|derm|integument/],
    ];

    const LAYER = {
        head: 'organs', eyes: 'organs', heart: 'organs', lungs: 'organs', digestive: 'organs',
        spine: 'skeleton', 'long-bones': 'skeleton',
        'upper-muscles': 'muscle', 'lower-muscles': 'muscle',
    };

    // Marker positions (x, y) per figure key and view.
    const ANCHORS = {
        head: { front: [186, 24], back: [186, 24] },
        eyes: { front: [174, 62] },
        heart: { front: [186, 148] },
        lungs: { front: [104, 124] },
        digestive: { front: [186, 206] },
        spine: { front: [164, 104], back: [166, 150] },
        'upper-muscles': { front: [92, 122], back: [196, 130] },
        'lower-muscles': { front: [100, 318], back: [196, 320] },
        'long-bones': { front: [244, 300], back: [244, 300] },
    };

    const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function h(tag, attrs = {}, children = []) {
        const el = document.createElement(tag);
        Object.entries(attrs).forEach(([k, v]) => {
            if (k === 'class') el.className = v;
            else el.setAttribute(k, v);
        });
        children.forEach((c) => el.append(c));
        return el;
    }

    function fmtTime(iso) {
        if (!iso) return 'No data recorded yet';
        const d = new Date(iso);
        return 'As of ' + d.toLocaleString(undefined, { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short' });
    }

    function sourceBadge(source) {
        const map = {
            simulation: ['Simulation', 'bg-info-subtle text-info-emphasis', 'bi-cpu'],
            manual: ['Manual entry', 'bg-primary-subtle text-primary-emphasis', 'bi-pencil-square'],
            csv_import: ['CSV import', 'bg-secondary-subtle', 'bi-filetype-csv'],
        };
        const [label, cls, icon] = map[source] || ['Unknown source', 'bg-secondary-subtle', 'bi-question-circle'];
        return `<span class="badge ${cls}"><i class="bi ${icon}" aria-hidden="true"></i> ${label}</span>`;
    }

    function freshnessBadge(freshness) {
        const map = {
            current: ['Current', 'bg-success-subtle text-success-emphasis', 'bi-check-circle'],
            stale: ['Stale', 'bg-warning-subtle text-warning-emphasis', 'bi-clock-history'],
            no_data: ['No data', 'bg-secondary-subtle', 'bi-question-circle'],
        };
        const [label, cls, icon] = map[freshness] || map.no_data;
        return `<span class="badge ${cls}"><i class="bi ${icon}" aria-hidden="true"></i> ${label}</span>`;
    }

    function statusBadgeHtml(level) {
        const s = STATUS[level] || STATUS.watch;
        return `<span class="badge d-inline-flex align-items-center gap-1" style="background-color:${s.color}"><i class="bi ${s.icon}" aria-hidden="true"></i> ${s.label}</span>`;
    }

    function markerHtml(level) {
        switch (level) {
            case 'nominal':
                return '<circle class="m-bg" r="7.5"/><path class="m-glyph" d="M-3.5 0.5 L-1 3 L3.5 -2.5"/>';
            case 'review':
                return '<path class="m-bg" d="M0 -8.5 L8.5 6.5 L-8.5 6.5 Z"/><path class="m-glyph" d="M0 -2.5 L0 1.8"/><circle class="m-dot" cx="0" cy="4" r="0.9"/>';
            case 'urgent':
                return '<path class="m-bg" d="M0 -9.5 L9.5 0 L0 9.5 L-9.5 0 Z"/><path class="m-glyph" d="M0 -4 L0 1.5"/><circle class="m-dot" cx="0" cy="4.2" r="1"/>';
            default:
                return '<circle class="m-bg" r="7.5"/><circle class="m-dot" r="2.2"/>';
        }
    }

    function sparkline(points) {
        if (!points || points.length < 2) {
            return '<span class="small text-secondary">Not enough history yet</span>';
        }
        const values = points.map((p) => p.v);
        const min = Math.min(...values);
        const max = Math.max(...values);
        const range = max - min || 1;
        const w = 120;
        const hgt = 28;
        const coords = values
            .map((v, i) => {
                const x = (i / (values.length - 1)) * (w - 4) + 2;
                const y = hgt - 3 - ((v - min) / range) * (hgt - 6);
                return `${x.toFixed(1)},${y.toFixed(1)}`;
            })
            .join(' ');
        return `<svg viewBox="0 0 ${w} ${hgt}" role="img" aria-label="Trend over the last ${values.length} readings, from ${min} to ${max}"><polyline points="${coords}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg>`;
    }

    function init() {
        const root = document.querySelector('[data-bodymap-root]');
        if (!root) return;

        const figure = root.querySelector('[data-bodymap-figure]');
        const svg = root.querySelector('[data-bodymap-svg]');
        const tooltip = root.querySelector('[data-bodymap-tooltip]');
        const panelBody = root.querySelector('[data-bodymap-panel-body]');
        const extras = root.querySelector('[data-bodymap-extras]');
        const markersLayer = svg.querySelector('[data-bodymap-markers]');
        const apiUrl = root.dataset.api;
        const placeholderHtml = panelBody.innerHTML;
        const figureKeys = new Set([...svg.querySelectorAll('[data-region]')].map((g) => g.dataset.region));

        let regions = [];
        let bySlug = new Map();
        let keyBySlug = new Map();
        let slugByKey = new Map();
        let selectedSlug = null;
        let raf = 0;
        const detailCache = new Map();

        // ---------- indexing ----------
        function keyFor(region) {
            if (region.svg_id && figureKeys.has(region.svg_id)) return region.svg_id;
            const text = [region.slug, region.svg_id, region.name].join(' ').toLowerCase();
            for (const [key, re] of KEY_RULES) {
                if (figureKeys.has(key) && re.test(text)) return key;
            }
            return null;
        }

        function buildIndex(list) {
            regions = list || [];
            bySlug = new Map();
            keyBySlug = new Map();
            slugByKey = new Map();
            regions.forEach((r) => {
                bySlug.set(r.slug, r);
                const key = keyFor(r);
                if (key && !slugByKey.has(key)) {
                    slugByKey.set(key, r.slug);
                    keyBySlug.set(r.slug, key);
                }
            });
        }

        // ---------- status rendering ----------
        function heartBpm() {
            const slug = slugByKey.get('heart');
            const region = slug && bySlug.get(slug);
            if (!region) return null;
            const m = region.metrics.find((x) => /heart.?rate|bpm/i.test(`${x.label ?? ''} ${x.unit ?? ''}`) && x.value != null);
            const bpm = m ? parseFloat(m.value) : NaN;
            return Number.isFinite(bpm) ? Math.min(140, Math.max(40, bpm)) : null;
        }

        function buildMarkers() {
            markersLayer.replaceChildren();
            Object.entries(ANCHORS).forEach(([key, views]) => {
                const slug = slugByKey.get(key);
                const region = slug && bySlug.get(slug);
                if (!region) return;
                Object.entries(views).forEach(([view, [x, y]]) => {
                    const g = document.createElementNS(NS, 'g');
                    g.setAttribute('class', `bm-marker bm-v-${view}`);
                    g.setAttribute('transform', `translate(${x} ${y})`);
                    g.setAttribute('data-status', region.status_level);
                    g.setAttribute('data-layer', LAYER[key] || 'organs');
                    g.setAttribute('aria-hidden', 'true');
                    g.innerHTML = markerHtml(region.status_level);
                    markersLayer.append(g);
                });
            });
        }

        function buildExtras() {
            extras.replaceChildren();
            regions.forEach((r) => {
                if (keyBySlug.has(r.slug)) return;
                const s = STATUS[r.status_level] || STATUS.watch;
                const btn = h('button', { type: 'button', class: 'btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1', 'data-extra-trigger': '', 'data-slug': r.slug });
                btn.innerHTML = `<i class="bi ${s.icon}" aria-hidden="true"></i> ${esc(r.name)} <span class="visually-hidden">(${s.label})</span>`;
                btn.addEventListener('click', () => select(r.slug));
                extras.append(btn);
            });
        }

        function applyStatuses() {
            regions.forEach((r) => {
                const key = keyBySlug.get(r.slug);
                const s = STATUS[r.status_level] || STATUS.watch;
                if (key) {
                    svg.querySelectorAll(`[data-region="${key}"]`).forEach((g) => {
                        g.dataset.status = r.status_level;
                        g.setAttribute('aria-label', `${r.name}: ${s.label}. Press Enter for details.`);
                    });
                }
                root.querySelectorAll(`[data-status-slot="${CSS.escape(r.slug)}"]`).forEach((slot) => {
                    slot.innerHTML = statusBadgeHtml(r.status_level);
                });
            });
            buildMarkers();
            buildExtras();
            const bpm = heartBpm();
            svg.style.setProperty('--bm-beat', bpm ? `${(60 / bpm).toFixed(2)}s` : '1.1s');
            markSelected();
        }

        // ---------- selection ----------
        function markSelected() {
            const key = selectedSlug ? keyBySlug.get(selectedSlug) : null;
            svg.querySelectorAll('.bm-region').forEach((g) => {
                const on = !!key && g.dataset.region === key;
                g.classList.toggle('is-selected', on);
                g.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            root.querySelectorAll('[data-region-trigger], [data-extra-trigger]').forEach((btn) => {
                btn.classList.toggle('active', btn.dataset.slug === selectedSlug);
            });
        }

        function select(slug) {
            const region = bySlug.get(slug);
            if (!region) return;
            selectedSlug = slug;
            markSelected();
            renderPanel(panelBody, region);
            loadDetail(slug);
            const key = keyBySlug.get(slug);
            key ? zoomTo(key) : animateTo(FULL);
            root.dispatchEvent(new CustomEvent('bodymap:region-selected', { detail: { slug } }));
        }

        function clearSelection() {
            if (!selectedSlug) return;
            selectedSlug = null;
            markSelected();
            panelBody.innerHTML = placeholderHtml;
            animateTo(FULL);
        }

        // ---------- zoom ----------
        function setView(v) {
            svg.setAttribute('viewBox', `${v.x} ${v.y} ${v.w} ${v.h}`);
        }

        function animateTo(target) {
            cancelAnimationFrame(raf);
            if (reduceMotion()) {
                setView(target);
                return;
            }
            const vb = svg.viewBox.baseVal;
            const from = { x: vb.x, y: vb.y, w: vb.width, h: vb.height };
            const start = performance.now();
            const dur = 450;
            const ease = (p) => (p < 0.5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2);
            const step = (now) => {
                const p = Math.min(1, (now - start) / dur);
                const e = ease(p);
                setView({
                    x: from.x + (target.x - from.x) * e,
                    y: from.y + (target.y - from.y) * e,
                    w: from.w + (target.w - from.w) * e,
                    h: from.h + (target.h - from.h) * e,
                });
                if (p < 1) raf = requestAnimationFrame(step);
            };
            raf = requestAnimationFrame(step);
        }

        function zoomTo(key) {
            if (key === 'skin') return animateTo(FULL);
            const els = [...svg.querySelectorAll(`[data-region="${key}"]`)].filter((el) => el.getClientRects().length > 0);
            if (!els.length) return animateTo(FULL);

            let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
            els.forEach((el) => {
                const b = el.getBBox();
                x1 = Math.min(x1, b.x);
                y1 = Math.min(y1, b.y);
                x2 = Math.max(x2, b.x + b.width);
                y2 = Math.max(y2, b.y + b.height);
            });

            const pad = 36;
            let w = x2 - x1 + pad * 2;
            let hgt = y2 - y1 + pad * 2;
            if (w / hgt > ASPECT) hgt = w / ASPECT; else w = hgt * ASPECT;
            w = Math.min(FULL.w, Math.max(w, 130));
            hgt = w / ASPECT;
            const x = Math.min(FULL.w - w, Math.max(0, (x1 + x2) / 2 - w / 2));
            const y = Math.min(FULL.h - hgt, Math.max(0, (y1 + y2) / 2 - hgt / 2));
            animateTo({ x, y, w, h: hgt });
        }

        // ---------- tooltip ----------
        function showTooltip(g, clientX, clientY) {
            const slug = slugByKey.get(g.dataset.region);
            const region = slug && bySlug.get(slug);
            if (!region) return hideTooltip();
            const s = STATUS[region.status_level] || STATUS.watch;
            tooltip.innerHTML = `<i class="bi ${s.icon}" aria-hidden="true"></i> <strong>${esc(region.name)}</strong> &middot; ${s.label}`;
            tooltip.hidden = false;
            const stage = svg.parentElement.getBoundingClientRect();
            const left = Math.min(stage.width - tooltip.offsetWidth - 4, Math.max(4, clientX - stage.left + 14));
            const top = Math.max(4, clientY - stage.top + 14);
            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
        }

        function hideTooltip() {
            tooltip.hidden = true;
        }

        svg.addEventListener('pointermove', (e) => {
            const g = e.target.closest('.bm-region');
            g ? showTooltip(g, e.clientX, e.clientY) : hideTooltip();
        });
        svg.addEventListener('pointerleave', hideTooltip);
        svg.addEventListener('focusin', (e) => {
            const g = e.target.closest('.bm-region');
            if (!g) return;
            const r = g.getBoundingClientRect();
            showTooltip(g, r.left + r.width / 2, r.top + r.height / 2);
        });
        svg.addEventListener('focusout', hideTooltip);

        svg.addEventListener('click', (e) => {
            const g = e.target.closest('.bm-region');
            if (!g) return clearSelection();
            const slug = slugByKey.get(g.dataset.region);
            if (slug) select(slug);
        });

        svg.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const g = e.target.closest('.bm-region');
            if (!g) return;
            e.preventDefault();
            const slug = slugByKey.get(g.dataset.region);
            if (slug) select(slug);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') clearSelection();
        });

        // ---------- toolbar ----------
        root.querySelectorAll('input[name="bm-view"]').forEach((input) => {
            input.addEventListener('change', () => {
                figure.dataset.view = input.value;
                hideTooltip();
                const key = selectedSlug ? keyBySlug.get(selectedSlug) : null;
                key ? zoomTo(key) : animateTo(FULL);
            });
        });

        root.querySelectorAll('[data-layer-toggle]').forEach((input) => {
            input.addEventListener('change', () => {
                figure.classList.toggle(`bm-hide-${input.dataset.layerToggle}`, !input.checked);
            });
        });

        root.querySelector('[data-action="reset-view"]').addEventListener('click', () => {
            clearSelection();
            animateTo(FULL);
        });

        const styleToggle = root.querySelector('[data-action="toggle-style"]');
        styleToggle?.addEventListener('click', () => {
            const modern = figure.classList.toggle('bm-modern');
            styleToggle.setAttribute('aria-pressed', modern ? 'true' : 'false');
            styleToggle.innerHTML = modern
                ? '<i class="bi bi-stars" aria-hidden="true"></i> Modern view'
                : '<i class="bi bi-diagram-3" aria-hidden="true"></i> Classic view';
        });

        root.querySelectorAll('[data-region-trigger]').forEach((btn) => {
            btn.addEventListener('click', () => select(btn.dataset.slug));
        });

        // ---------- panel ----------
        function renderPanel(container, region) {
            const tpl = document.getElementById('bodymap-panel-template');
            const frag = tpl.content.cloneNode(true);

            frag.querySelector('[data-f="name"]').textContent = region.name;
            frag.querySelector('[data-f="body_system"]').textContent = String(region.body_system || '').replace(/_/g, ' ');
            frag.querySelector('[data-f="status_badge"]').innerHTML = statusBadgeHtml(region.status_level);
            frag.querySelector('[data-f="as_of"]').textContent = fmtTime(region.as_of);
            frag.querySelector('[data-f="function_text"]').textContent = region.function_text;

            const metricsEl = frag.querySelector('[data-f="metrics"]');
            if (!region.metrics.length) {
                metricsEl.append(h('li', { class: 'text-secondary small' }, ['No metrics linked yet.']));
            }
            region.metrics.forEach((m, i) => {
                const li = h('li', { class: 'mb-3' });
                li.innerHTML = `<strong>${esc(m.label ?? 'Metric')}</strong>: ${m.value != null ? esc(m.value) + ' ' + esc(m.unit ?? '') : 'No data'}
                    <div class="d-flex flex-wrap gap-2 mt-1">${sourceBadge(m.source)}${freshnessBadge(m.freshness)}</div>
                    <div class="bm-spark text-primary mt-1" data-spark="${i}"></div>`;
                metricsEl.append(li);
            });

            const hazardsEl = frag.querySelector('[data-f="hazards"]');
            region.hazards.forEach((hz) => {
                const li = h('li', { class: 'mb-2' });
                li.innerHTML = `<i class="bi ${esc(hz.icon)}" style="color:${esc(hz.color)}" aria-hidden="true"></i> <strong>${esc(hz.name)}</strong> &mdash; ${esc(hz.why_it_matters)}`;
                hazardsEl.append(li);
            });

            const stepsEl = frag.querySelector('[data-f="self_care_steps"]');
            const flagsEl = frag.querySelector('[data-f="red_flags"]');
            const escalateEl = frag.querySelector('[data-f="escalate_to"]');

            if (region.guidance) {
                (region.guidance.self_care_steps || []).forEach((s) => stepsEl.append(h('li', {}, [s])));
                (region.guidance.red_flags || []).forEach((f) => flagsEl.append(h('li', {}, [f])));
                escalateEl.textContent = 'Escalate to: ' + region.guidance.escalate_to;
            } else {
                stepsEl.append(h('li', { class: 'text-secondary' }, ['No guidance available for this status yet.']));
            }

            frag.querySelector('[data-action="ask-orbit"]').addEventListener('click', () => {
                const question = `Tell me about my ${region.name.toLowerCase()} status`;
                if (window.OrbitAssistant && typeof window.OrbitAssistant.askAbout === 'function') {
                    window.OrbitAssistant.askAbout(question);
                }
            });

            container.replaceChildren(frag);
            if (detailCache.has(region.slug)) fillSparks(detailCache.get(region.slug));
        }

        function fillSparks(detail) {
            panelBody.querySelectorAll('[data-spark]').forEach((el) => {
                const entry = (detail.metric_history || [])[Number(el.dataset.spark)];
                el.innerHTML = entry ? sparkline(entry.points) : '';
            });
        }

        function loadDetail(slug) {
            if (detailCache.has(slug)) return;
            fetch(`${apiUrl.replace(/\/$/, '')}/${encodeURIComponent(slug)}`, { headers: { Accept: 'application/json' } })
                .then((r) => (r.ok ? r.json() : Promise.reject()))
                .then((detail) => {
                    detailCache.set(slug, detail);
                    if (selectedSlug === slug) fillSparks(detail);
                })
                .catch(() => { /* sparklines are optional; stay quiet offline */ });
        }

        // ---------- refresh ----------
        function refresh() {
            fetch(apiUrl, { headers: { Accept: 'application/json' } })
                .then((r) => (r.ok ? r.json() : Promise.reject()))
                .then((data) => {
                    const prev = selectedSlug && bySlug.get(selectedSlug);
                    buildIndex(data.regions);
                    applyStatuses();
                    const next = selectedSlug && bySlug.get(selectedSlug);
                    if (prev && next && prev.status_level !== next.status_level) {
                        renderPanel(panelBody, next);
                    }
                })
                .catch(() => { /* non-fatal: keep the server-rendered snapshot */ });
        }

        buildIndex(window.__bodyMapRegions || []);
        applyStatuses();
        refresh();
        setInterval(refresh, 60000);
        window.addEventListener('online', refresh);
    }

    document.addEventListener('DOMContentLoaded', init);
})();
