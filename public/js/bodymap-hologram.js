// Body Map hologram HUD: additive, non-invasive companion to bodymap.js.
// It only observes the DOM that bodymap.js already maintains (selection classes, status markers, viewBox)
// and reads the same /api/body-map data. It never changes region ids, data attributes or click handlers.
(function () {
    'use strict';

    const NS = 'http://www.w3.org/2000/svg';
    const REFRESH_MS = 60000;
    // The 60 min/day figure mirrors the experimental physical_deconditioning rule parameter (a team assumption).
    const EXERCISE_TARGET_MIN = 60;
    const SEGMENTS = 12;
    const CONDITION_BY_STATUS = { nominal: 0.92, watch: 0.58, review: 0.4, urgent: 0.2 };
    const STATUS_LABEL = { nominal: 'NOMINAL', watch: 'MONITORING', review: 'REVIEW', urgent: 'PRIORITY' };
    // Short callout names, keyed by the figure region key used in data-region.
    const CALLOUT_LABEL = {
        head: 'BRAIN', eyes: 'EYES', heart: 'HEART', lungs: 'LUNG', spine: 'SPINE', 'long-bones': 'BONES',
        'upper-muscles': 'MUSCLE', 'lower-muscles': 'MUSCLE', digestive: 'DIGESTIVE', skin: 'SKIN',
    };

    function init() {
        const root = document.querySelector('[data-bodymap-root]');
        const figure = root?.querySelector('[data-bodymap-figure]');
        const svg = root?.querySelector('[data-bodymap-svg]');
        const stage = root?.querySelector('.bm-stage');
        const callout = root?.querySelector('[data-hud="callout"]');
        if (!root || !figure || !svg || !stage) return;

        const q = (name) => root.querySelector(`[data-hud="${name}"]`);
        const markersLayer = svg.querySelector('[data-bodymap-markers]');
        let regions = Array.isArray(window.__bodyMapRegions) ? window.__bodyMapRegions : [];

        // ---------- front/back tag ----------
        const modeEl = q('mode');
        const syncMode = () => { if (modeEl) modeEl.textContent = figure.dataset.view === 'back' ? 'BACK' : 'FRONT'; };
        root.querySelectorAll('input[name="bm-view"]').forEach((input) => input.addEventListener('change', syncMode));
        syncMode();

        // ---------- live readouts ----------
        function setSegments(el, fraction) {
            if (!el) return;
            const on = Math.round(Math.max(0, Math.min(1, fraction)) * SEGMENTS);
            [...el.children].forEach((seg, i) => seg.classList.toggle('on', i < on));
        }

        function heartMetric() {
            const heart = regions.find((r) => /heart/i.test(`${r.slug} ${r.name}`));
            return heart?.metrics?.find((m) => /heart.?rate|bpm/i.test(`${m.label ?? ''} ${m.unit ?? ''}`) && m.value != null) || null;
        }

        function renderPulse() {
            const metric = heartMetric();
            const bpm = metric ? parseFloat(metric.value) : NaN;
            const real = Number.isFinite(bpm);
            const shown = real ? Math.min(140, Math.max(40, bpm)) : 60;
            const bpmEl = q('bpm');
            if (bpmEl) bpmEl.textContent = real ? String(Math.round(bpm)) : '--';
            figure.style.setProperty('--bm-ecg-period', `${(60 / shown).toFixed(2)}s`);
            const note = q('bpm-note');
            if (note) {
                const source = metric?.source === 'simulation' ? 'SIMULATION' : (metric?.source ? 'RECORDED' : '');
                note.textContent = real ? `${source || 'RECORDED'} RESTING HR` : 'DEMO RHYTHM · NO DATA';
            }
        }

        function renderBars() {
            const muscleRegions = regions.filter((r) => /muscle/i.test(`${r.slug} ${r.name}`));
            const exercise = muscleRegions
                .flatMap((r) => r.metrics || [])
                .find((m) => /exercise/i.test(m.label ?? '') && m.value != null);
            const minutes = exercise ? parseFloat(exercise.value) : NaN;
            const hasExercise = Number.isFinite(minutes);

            const severity = ['urgent', 'review', 'watch', 'nominal'];
            const status = severity.find((level) => muscleRegions.some((r) => r.status_level === level));

            setSegments(q('strength'), hasExercise ? Math.min(1, minutes / EXERCISE_TARGET_MIN) : 0.66);
            const strengthValue = q('strength-value');
            if (strengthValue) strengthValue.textContent = hasExercise ? `${Math.round(minutes)} MIN` : 'DEMO';

            setSegments(q('condition'), status ? CONDITION_BY_STATUS[status] : 0.75);
            const conditionValue = q('condition-value');
            if (conditionValue) conditionValue.textContent = status ? STATUS_LABEL[status] : 'DEMO';

            const note = q('bars-note');
            if (note) note.textContent = hasExercise || status ? 'ESTIMATE · NOT MEASURED' : 'ILLUSTRATIVE · NO DATA';
        }

        function renderReadouts() {
            renderPulse();
            renderBars();
        }

        function refreshData() {
            const api = root.dataset.api;
            if (!api) return;
            fetch(api, { headers: { Accept: 'application/json' } })
                .then((r) => (r.ok ? r.json() : Promise.reject()))
                .then((data) => {
                    if (Array.isArray(data.regions)) {
                        regions = data.regions;
                        renderReadouts();
                    }
                })
                .catch(() => { /* keep the last known values while offline */ });
        }

        renderReadouts();
        refreshData();
        setInterval(refreshData, REFRESH_MS);
        window.addEventListener('online', refreshData);

        // ---------- circular status nodes ----------
        function decorateMarkers() {
            if (!markersLayer) return;
            markersLayer.querySelectorAll('.bm-marker').forEach((marker) => {
                if (marker.querySelector('.bm-node-ring')) return;
                const halo = document.createElementNS(NS, 'circle');
                halo.setAttribute('class', 'bm-node-halo');
                halo.setAttribute('r', '11');
                const ring = document.createElementNS(NS, 'circle');
                ring.setAttribute('class', 'bm-node-ring');
                ring.setAttribute('r', '10');
                marker.prepend(halo, ring);
            });
        }
        if (markersLayer) {
            new MutationObserver(decorateMarkers).observe(markersLayer, { childList: true });
            decorateMarkers();
        }

        // ---------- selection callout ----------
        const calloutSvg = callout?.querySelector('.bm-callout-svg');
        const line = callout?.querySelector('.bm-callout-line');
        const dot = callout?.querySelector('.bm-callout-dot');
        const rings = callout ? [...callout.querySelectorAll('.bm-ring')] : [];
        const label = callout?.querySelector('.bm-callout-label');

        function visibleRect(key) {
            let rect = null;
            svg.querySelectorAll(`.bm-region[data-region="${key}"]`).forEach((g) => {
                if (!g.getClientRects().length) return;
                const b = g.getBoundingClientRect();
                if (!rect) rect = { left: b.left, top: b.top, right: b.right, bottom: b.bottom };
                else {
                    rect.left = Math.min(rect.left, b.left);
                    rect.top = Math.min(rect.top, b.top);
                    rect.right = Math.max(rect.right, b.right);
                    rect.bottom = Math.max(rect.bottom, b.bottom);
                }
            });
            return rect;
        }

        function hideCallout() {
            if (callout) callout.hidden = true;
        }

        function placeCallout() {
            if (!callout || !label || !line) return;
            const selected = svg.querySelector('.bm-region.is-selected');
            figure.classList.toggle('bm-has-selection', !!selected);
            if (!selected) return hideCallout();

            const key = selected.dataset.region;
            const rect = visibleRect(key);
            const stageBox = stage.getBoundingClientRect();
            if (!rect || !stageBox.width) return hideCallout();

            const cx = (rect.left + rect.right) / 2 - stageBox.left;
            const cy = (rect.top + rect.bottom) / 2 - stageBox.top;
            const radius = Math.min(40, Math.max(14, Math.max(rect.right - rect.left, rect.bottom - rect.top) / 2 + 6));
            const status = getComputedStyle(selected).getPropertyValue('--st').trim();
            if (status) callout.style.setProperty('--co', status);

            callout.hidden = false;
            label.textContent = CALLOUT_LABEL[key] || key.replace(/-/g, ' ');

            const labelW = label.offsetWidth;
            const labelH = label.offsetHeight;
            const onRight = cx >= stageBox.width / 2;
            const edge = 14;
            const elbowX = onRight ? cx + radius * 0.7 + 10 : cx - radius * 0.7 - 10;
            const elbowY = Math.max(labelH / 2 + edge, cy - radius * 0.7 - 14);
            const labelX = onRight ? stageBox.width - edge - labelW : edge;
            const attachX = onRight ? labelX : labelX + labelW;
            const reach = onRight ? Math.max(attachX, elbowX) : Math.min(attachX, elbowX);

            line.setAttribute('d', `M${cx.toFixed(1)} ${cy.toFixed(1)} L${elbowX.toFixed(1)} ${elbowY.toFixed(1)} L${reach.toFixed(1)} ${elbowY.toFixed(1)}`);
            dot?.setAttribute('cx', cx.toFixed(1));
            dot?.setAttribute('cy', cy.toFixed(1));
            rings.forEach((ring) => {
                ring.setAttribute('cx', cx.toFixed(1));
                ring.setAttribute('cy', cy.toFixed(1));
                ring.setAttribute('r', radius.toFixed(1));
            });
            label.style.left = `${labelX}px`;
            label.style.top = `${elbowY - labelH / 2}px`;
            if (calloutSvg) calloutSvg.setAttribute('viewBox', `0 0 ${stageBox.width} ${stageBox.height}`);
        }

        // bodymap.js changes selection classes and animates the viewBox; follow both, one update per frame.
        // The timer is a fallback for throttled or background tabs where animation frames do not fire.
        let queued = false;
        function run() {
            if (!queued) return;
            queued = false;
            placeCallout();
        }
        function schedule() {
            if (queued) return;
            queued = true;
            requestAnimationFrame(run);
            setTimeout(run, 120);
        }
        new MutationObserver(schedule).observe(svg, { attributes: true, attributeFilter: ['class', 'viewBox'], subtree: true });
        new MutationObserver(schedule).observe(figure, { attributes: true, attributeFilter: ['data-view', 'class'] });
        window.addEventListener('resize', schedule);
        schedule();
    }

    document.addEventListener('DOMContentLoaded', init);
})();
