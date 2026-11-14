/*
 * ORBIT assistant UI controller. The engine (engine.js), storage (store.js) and
 * sync (sync.js) do the work; this file only wires them to the DOM.
 * All text is written with textContent so engine or user text is never parsed as HTML.
 */
(function () {
    'use strict';

    const PING_MS = 30000;
    const TYPING_MS = 450;
    const NOTE_MS = 3500;
    const KNOWLEDGE_FILES = ['intents.json', 'health-knowledge.json', 'procedures.json', 'faq.json'];
    const DEFAULT_CHIPS = ['What is my latest heart rate?', 'how to get good sleep', 'I have a headache', 'What radiation protection should I follow?'];
    const STATUS_LABELS = { normal: 'No trend flag', watch: 'Watch', unknown: 'Insufficient data' };
    const MENU = [
        { label: 'Vitals', icon: 'bi-heart-pulse', items: ['What is my latest heart rate?', 'What is my latest blood pressure?', 'What is my latest SpO2?'] },
        { label: 'Sleep', icon: 'bi-moon-stars', items: ['how to get good sleep', 'circadian rhythm routine'] },
        { label: 'Exercise', icon: 'bi-activity', items: ['How do I protect my bones and muscles?', 'Give me a treadmill routine'] },
        { label: 'Radiation', icon: 'bi-shield-check', items: ['What radiation protection should I follow?'] },
        { label: 'Mental Wellness', icon: 'bi-emoji-smile', items: ['How do I cope with isolation?', 'How do I handle crew conflict?'] },
        { label: 'Nutrition', icon: 'bi-cup-hot', items: ['How much water should I drink?', 'weight management routine'] },
        { label: 'Emergency', icon: 'bi-exclamation-octagon', items: ['I have chest pain', 'I have severe bleeding'] },
    ];
    // Keys are the real route names (data-page). The first matching entry wins.
    const PAGE_CHIPS = [
        { match: /^dashboard$/, hello: 'Your dashboard is open. I can read your latest cached vitals and explain any flagged alerts.', questions: ['What is my latest heart rate?', 'What is my latest blood pressure?'] },
        { match: /^alerts\./, hello: 'You are on the alerts page. I can explain which trend rules are flagged and what to do next.', questions: ['Explain my alerts', 'What is my latest heart rate?'] },
        { match: /^readings\./, hello: 'You are on your readings. Ask about any recorded metric.', questions: ['What is my latest SpO2?', 'What is my latest blood pressure?'] },
        { match: /^checkin\./, hello: 'Daily check-in is open. I can share sleep and recovery routines.', questions: ['how to get good sleep', 'circadian rhythm routine'] },
        { match: /^monitor\.radiation$/, hello: 'You are on the radiation monitor. I can explain protection steps and when to escalate.', questions: ['What radiation protection should I follow?'] },
        { match: /^monitor\.wellbeing$/, hello: 'You are on the wellbeing monitor. I can suggest coping techniques for isolation and conflict.', questions: ['How do I cope with isolation?', 'How do I handle crew conflict?'] },
        { match: /^monitor\.physical$/, hello: 'You are on the gravity and physical monitor. I can share exercise countermeasure routines.', questions: ['How do I protect my bones and muscles?', 'Give me a treadmill routine'] },
        { match: /^monitor\.environment$/, hello: 'You are on the environment monitor. I can explain cabin air and CO2 symptoms.', questions: ['What is cabin CO2 exposure?', 'I have a headache'] },
    ];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Builds a DOM element; text goes through textContent, never innerHTML.
    function h(tag, attrs = {}, children = []) {
        const node = document.createElement(tag);
        Object.entries(attrs).forEach(([key, value]) => {
            if (key === 'class') node.className = value;
            else if (key === 'text') node.textContent = value;
            else if (key.startsWith('on')) node.addEventListener(key.slice(2), value);
            else node.setAttribute(key, value);
        });
        [].concat(children).forEach((child) => {
            if (child !== null && child !== undefined && child !== false) {
                node.append(child instanceof Node ? child : document.createTextNode(String(child)));
            }
        });
        return node;
    }

    function uuid() {
        if (globalThis.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
    const timeOf = (iso) => new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const dateTimeOf = (iso) => new Date(iso).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });

    // Inline **bold** only; everything else stays plain text.
    function inline(text) {
        return String(text).split(/\*\*(.+?)\*\*/).map((part, index) => (index % 2 ? h('strong', { text: part }) : document.createTextNode(part)));
    }

    // Markdown-lite: bold, bullets, numbered lists, and - [ ] / - [x] checklists.
    function renderText(parent, text) {
        let current = null;
        const block = (type, tag, cls) => {
            if (!current || current.type !== type) {
                current = { type, el: h(tag, { class: cls }) };
                parent.append(current.el);
            }
            return current.el;
        };
        String(text).split('\n').forEach((raw) => {
            const line = raw.trimEnd();
            const check = line.match(/^\s*[-*]\s\[( |x|X)\]\s+(.*)$/);
            const bullet = line.match(/^\s*[-*]\s+(.*)$/);
            const numbered = line.match(/^\s*\d+[.)]\s+(.*)$/);
            if (check) {
                const input = h('input', { type: 'checkbox', ...(check[1].toLowerCase() === 'x' ? { checked: '' } : {}) });
                block('check', 'ul', 'oc-orbit-checklist').append(h('li', {}, [h('label', { class: 'oc-orbit-check' }, [input, h('span', {}, inline(check[2]))])]));
            } else if (bullet) {
                block('bullet', 'ul', 'oc-orbit-list').append(h('li', {}, inline(bullet[1])));
            } else if (numbered) {
                block('num', 'ol', 'oc-orbit-list').append(h('li', {}, inline(numbered[1])));
            } else if (line.trim() === '') {
                current = null;
            } else {
                current = null;
                parent.append(h('p', {}, inline(line)));
            }
        });
    }

    function loadChartJs(assets) {
        if (globalThis.Chart) return Promise.resolve(globalThis.Chart);
        if (!loadChartJs.pending) {
            loadChartJs.pending = new Promise((resolve, reject) => {
                const script = h('script', { src: `${assets}vendor/chart.umd.min.js` });
                script.onload = () => resolve(globalThis.Chart);
                script.onerror = () => reject(new Error('Chart.js could not be loaded.'));
                document.head.append(script);
            });
        }
        return loadChartJs.pending;
    }

    function init() {
        const root = document.querySelector('[data-orbit-root]');
        if (!root || !globalThis.OrbitEngine || !globalThis.OrbitStore) return;

        const mode = root.dataset.mode;
        const owner = Number(root.dataset.owner);
        const base = root.dataset.base;
        const assets = root.dataset.assets;
        const page = root.dataset.page || '';
        const syncEnabled = root.dataset.sync === '1';
        const q = (selector) => root.querySelector(selector);
        const el = {
            panel: q('[data-orbit-panel]'), launch: q('[data-orbit-launch]'), badge: q('[data-orbit-badge]'),
            log: q('[data-orbit-log]'), chips: q('[data-orbit-chips]'), menu: q('[data-orbit-menu]'),
            menuToggle: q('[data-orbit-menu-toggle]'), form: q('[data-orbit-form]'), input: q('[data-orbit-input]'),
            mic: q('[data-orbit-mic]'), speak: q('[data-orbit-speak]'), clear: q('[data-orbit-clear]'),
            minimise: q('[data-orbit-minimise]'), close: q('[data-orbit-close]'), status: q('[data-orbit-status]'),
        };

        let knowledge = null;
        let snapshot = null;
        let context = {};
        let busy = false;
        let online = navigator.onLine;
        let speakReplies = localStorage.getItem('orbit-speak-replies') === '1';
        let noteTimer = null;

        // ---------- connection and status ----------
        function setStatus(text) {
            el.status.textContent = text;
        }

        async function refreshStatusText() {
            const queued = syncEnabled ? await OrbitStore.queue(owner).catch(() => []) : [];
            const base = !syncEnabled ? 'Local only on this device' : online ? 'Online' : 'Offline mode, fully functional';
            setStatus(queued.length ? `${base} · ${queued.length} waiting to sync` : base);
            root.querySelectorAll('[data-orbit-dot]').forEach((dot) => {
                dot.classList.toggle('is-online', online);
                dot.classList.toggle('is-offline', !online);
            });
        }

        function flashNote(text) {
            clearTimeout(noteTimer);
            setStatus(text);
            noteTimer = setTimeout(refreshStatusText, NOTE_MS);
        }

        function registerBackgroundSync() {
            if (!('serviceWorker' in navigator)) return;
            navigator.serviceWorker.ready
                .then((registration) => registration.sync && registration.sync.register('orbit-message-sync'))
                .catch(() => {});
        }

        async function flushQueue() {
            if (!online || !syncEnabled) return;
            try {
                await globalThis.OrbitSync(base);
            } catch (error) {
                flashNote('Could not sync yet; messages are kept on this device.');
                return;
            }
            refreshStatusText();
        }

        async function checkConnection() {
            if (!navigator.onLine) return setOnline(false);
            // Staff cannot use the astronaut-only session endpoint; trust the browser for them.
            if (!syncEnabled) return setOnline(true);
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 4000);
            try {
                const response = await fetch(new URL('chatbot/session', base), {
                    cache: 'no-store', credentials: 'same-origin', signal: controller.signal, headers: { Accept: 'application/json' },
                });
                setOnline(response.ok && !response.redirected);
            } catch (error) {
                setOnline(false);
            } finally {
                clearTimeout(timer);
            }
        }

        function setOnline(value) {
            const changed = value !== online;
            online = value;
            refreshStatusText();
            if (changed && online) flushQueue();
        }

        // ---------- knowledge and snapshot ----------
        async function loadKnowledge() {
            const [intents, health, procedures, faq] = await Promise.all(KNOWLEDGE_FILES.map((name) => fetch(`${assets}${name}`).then((response) => {
                if (!response.ok) throw new Error(`Could not load ${name}.`);
                return response.json();
            })));
            knowledge = {
                intents: intents.intents,
                topics: health.topics,
                categories: health.categories,
                disclaimer: health.disclaimer || procedures.disclaimer || '',
                procedures: procedures.procedures,
                faq: faq.questions,
            };
        }

        async function refreshSnapshot() {
            try {
                if (!navigator.onLine || !syncEnabled) throw new Error('offline');
                const response = await fetch(new URL('chatbot/snapshot', base), {
                    cache: 'no-store', credentials: 'same-origin', headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('snapshot');
                const fresh = await response.json();
                snapshot = { ...fresh, astronaut_id: fresh.astronaut_id ?? owner };
                await OrbitStore.saveSnapshot(snapshot);
            } catch (error) {
                snapshot = await OrbitStore.snapshot(owner).catch(() => null);
            }
            updateBadge();
        }

        function updateBadge() {
            const triggered = triggeredAlerts();
            if (el.badge) {
                el.badge.hidden = triggered === 0;
                el.badge.textContent = String(triggered);
            }
            if (el.launch && triggered) el.launch.setAttribute('aria-label', `Open ORBIT assistant, ${triggered} alert${triggered > 1 ? 's' : ''}`);
        }

        function triggeredAlerts() {
            return Object.values(snapshot?.rule_states || {}).filter((state) => state === 'triggered').length;
        }

        // ---------- messages ----------
        function scrollToEnd() {
            el.log.scrollTop = el.log.scrollHeight;
        }

        function copyText(text, button) {
            const done = () => { button.querySelector('i').className = 'bi bi-check2'; setTimeout(() => { button.querySelector('i').className = 'bi bi-clipboard'; }, 1500); };
            if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, () => {});
        }

        function sparkline(history) {
            const values = (history || []).map((point) => (typeof point === 'number' ? point : Number(point?.value))).filter(Number.isFinite);
            if (values.length < 2) return h('p', { class: 'oc-orbit-muted small mb-0', text: 'Trend graph appears once history is available.' });
            const canvas = h('canvas', { 'aria-hidden': 'true' });
            const wrap = h('div', { class: 'oc-orbit-spark' }, [canvas]);
            loadChartJs(assets).then((Chart) => {
                const styles = getComputedStyle(root);
                const accent = styles.getPropertyValue('--orbit-accent').trim() || '#2dd4bf';
                new Chart(canvas, {
                    type: 'line',
                    data: { labels: values.map((_, index) => index + 1), datasets: [{ data: values, borderColor: accent, backgroundColor: 'rgba(45,212,191,.15)', fill: true, tension: 0.35, pointRadius: 0, borderWidth: 2 }] },
                    options: { responsive: true, maintainAspectRatio: false, animation: reduceMotion ? false : { duration: 600 }, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } } },
                });
            }).catch(() => wrap.replaceChildren(h('p', { class: 'oc-orbit-muted small mb-0', text: 'Trend graph unavailable offline.' })));
            return wrap;
        }

        function vitalsCard(card) {
            const badge = h('span', { class: `oc-orbit-status oc-orbit-status--${card.status}`, text: STATUS_LABELS[card.status] || 'Insufficient data' });
            const recorded = `Recorded ${dateTimeOf(card.observed_at)} · ${card.source}`;
            return h('div', { class: 'oc-orbit-card oc-orbit-card--vitals' }, [
                h('div', { class: 'oc-orbit-card-head' }, [h('strong', { text: card.label }), badge]),
                h('div', { class: 'oc-orbit-metric' }, [h('span', { class: 'oc-orbit-metric-value', text: String(card.value) }), h('span', { class: 'oc-orbit-metric-unit', text: card.unit })]),
                h('p', { class: 'oc-orbit-muted small mb-1', text: recorded }),
                card.stale ? h('p', { class: 'small mb-1 oc-orbit-stale', text: 'This record is stale. Check the latest reading.' }) : null,
                sparkline(card.history),
            ]);
        }

        function emergencyCard(card) {
            return h('div', { class: 'oc-orbit-card oc-orbit-card--emergency', role: 'alert' }, [
                h('div', { class: 'oc-orbit-card-head' }, [h('span', { class: 'oc-orbit-tag oc-orbit-tag--danger', text: 'EMERGENCY' }), h('strong', { text: card.title })]),
                h('ol', { class: 'oc-orbit-list' }, card.steps.map((step) => h('li', { text: step }))),
                h('p', { class: 'mb-1' }, [h('strong', { text: 'Escalate now: ' }), card.escalate]),
                h('p', { class: 'oc-orbit-muted small mb-0', text: `Source: ${card.source}` }),
            ]);
        }

        function checklistCard(card) {
            const head = [h('strong', { text: card.title })];
            if (card.category === 'bone_muscle') head.push(h('span', { class: 'oc-orbit-tag', text: 'Exercise routine' }));
            return h('div', { class: 'oc-orbit-card oc-orbit-card--checklist' }, [
                h('div', { class: 'oc-orbit-card-head' }, head),
                h('p', { class: 'mb-2', text: card.answer }),
                h('ul', { class: 'oc-orbit-checklist' }, card.steps.map((step) => h('li', {}, [h('label', { class: 'oc-orbit-check' }, [h('input', { type: 'checkbox' }), h('span', { text: step })])]))),
                h('p', { class: 'mb-1' }, [h('strong', { text: 'Escalate to the flight surgeon: ' }), card.escalate]),
                h('p', { class: 'oc-orbit-muted small mb-0', text: `Source: ${card.source}` }),
            ]);
        }

        function flightSurgeonCard(summary) {
            const button = h('button', { type: 'button', class: 'oc-orbit-action-btn' }, [h('i', { class: 'bi bi-clipboard', 'aria-hidden': 'true' }), ' Copy handoff note']);
            button.addEventListener('click', () => copyText(summary, button));
            return h('div', { class: 'oc-orbit-card oc-orbit-card--action' }, [
                h('div', { class: 'oc-orbit-card-head' }, [h('i', { class: 'bi bi-broadcast', 'aria-hidden': 'true' }), h('strong', { text: 'Talk to the flight surgeon' })]),
                h('p', { class: 'small mb-2', text: 'Share this note with the flight surgeon or ground medical team. It contains no personal data.' }),
                button,
            ]);
        }

        function appendMessage(entry, card = null) {
            const { role, message, created_at: createdAt } = entry;
            const bubble = h('div', { class: 'oc-orbit-bubble' });
            const usesCardOnly = card && (card.type === 'emergency' || card.type === 'checklist');
            if (!usesCardOnly) renderText(bubble, message);
            if (card?.type === 'vitals') bubble.append(vitalsCard(card));
            if (card?.type === 'emergency') bubble.append(emergencyCard(card));
            if (card?.type === 'checklist') bubble.append(checklistCard(card));
            if (role === 'assistant' && card && (card.type === 'emergency' || card.type === 'checklist' || (card.type === 'vitals' && card.status === 'watch'))) {
                bubble.append(flightSurgeonCard(`ORBIT handoff: ${card.title || card.label}. ${card.escalate ? `Escalation: ${card.escalate}` : `Status: ${STATUS_LABELS[card.status]}.`}`));
            }
            const meta = h('div', { class: 'oc-orbit-meta' }, [h('time', { datetime: createdAt, text: timeOf(createdAt) })]);
            if (role === 'assistant') {
                const copy = h('button', { type: 'button', class: 'oc-orbit-copy', 'aria-label': 'Copy reply', title: 'Copy reply' }, [h('i', { class: 'bi bi-clipboard', 'aria-hidden': 'true' })]);
                copy.addEventListener('click', () => copyText(message, copy));
                meta.append(copy);
            }
            const row = h('div', { class: `oc-orbit-msg oc-orbit-msg--${role}${reduceMotion ? '' : ' is-entering'}` }, [bubble, meta]);
            el.log.append(row);
            scrollToEnd();
            return row;
        }

        function appendTyping() {
            const row = h('div', { class: 'oc-orbit-msg oc-orbit-msg--assistant is-typing', role: 'status', 'aria-label': 'ORBIT is typing' }, [
                h('div', { class: 'oc-orbit-bubble oc-orbit-dots' }, [h('span'), h('span'), h('span')]),
            ]);
            el.log.append(row);
            scrollToEnd();
            return row;
        }

        function showNotice(text) {
            el.log.append(h('p', { class: 'oc-orbit-notice', text }));
        }

        function renderChips(questions) {
            el.chips.replaceChildren(...questions.map((question) => {
                const chip = h('button', { type: 'button', class: 'oc-orbit-chip', text: question });
                chip.addEventListener('click', () => ask(question));
                return chip;
            }));
        }

        function pageEntry() {
            return PAGE_CHIPS.find((entry) => entry.match.test(page)) || null;
        }

        function pageQuestions() {
            return pageEntry()?.questions || [];
        }

        // Proactive offer: when alerts are flagged, "Explain my alerts" leads the chips.
        function defaultChips() {
            const alertChips = triggeredAlerts() ? ['Explain my alerts'] : [];
            return [...new Set([...alertChips, ...pageQuestions(), ...DEFAULT_CHIPS])].slice(0, 5);
        }

        function renderMenu() {
            el.menu.replaceChildren(...MENU.map((group) => h('section', { class: 'oc-orbit-menu-group' }, [
                h('h3', { class: 'oc-orbit-menu-title' }, [h('i', { class: `bi ${group.icon}`, 'aria-hidden': 'true' }), ` ${group.label}`]),
                h('div', { class: 'oc-orbit-menu-items' }, group.items.map((item) => {
                    const button = h('button', { type: 'button', class: 'oc-orbit-chip', text: item });
                    button.addEventListener('click', () => {
                        setMenu(false);
                        ask(item);
                    });
                    return button;
                })),
            ])));
        }

        function setMenu(open) {
            el.menu.hidden = !open;
            el.menuToggle.setAttribute('aria-expanded', String(open));
        }

        async function renderHistory() {
            el.log.replaceChildren();
            const past = (await OrbitStore.history(owner).catch(() => [])).sort((a, b) => (a.created_at < b.created_at ? -1 : 1));
            if (past.length === 0) {
                welcome();
            } else {
                past.forEach((entry) => appendMessage(entry));
            }
            renderChips(defaultChips());
        }

        function welcome() {
            const greeting = OrbitEngine.respond('hello', knowledge, {}, snapshot, new Date()).reply;
            const triggered = triggeredAlerts();
            const parts = [pageEntry()?.hello, greeting];
            if (triggered) parts.push(`${triggered} alert${triggered > 1 ? 's are' : ' is'} flagged for review. Ask "Explain my alerts" for details.`);
            appendMessage({ role: 'assistant', message: parts.filter(Boolean).join('\n\n'), created_at: new Date().toISOString() });
        }

        // ---------- conversation ----------
        async function ask(text) {
            const question = String(text || '').trim();
            if (!question || busy || !knowledge) return;
            busy = true;
            const userEntry = { client_id: uuid(), astronaut_id: owner, role: 'user', message: question, intent: null, created_at: new Date().toISOString() };
            appendMessage(userEntry);
            OrbitStore.saveMessage(userEntry).catch(() => showNotice('Could not save this message on the device.'));

            try {
                const typing = reduceMotion ? null : appendTyping();
                if (typing) await sleep(TYPING_MS);
                typing?.remove();

                const result = OrbitEngine.respond(question, knowledge, context, snapshot, new Date());
                context = result.context;
                const reply = { client_id: uuid(), astronaut_id: owner, role: 'assistant', message: result.reply, intent: result.intent, created_at: new Date().toISOString() };
                // Render first so the answer never depends on the device store finishing.
                appendMessage(reply, result.card);
                if (speakReplies) speak(result.reply);

                if (result.intent === 'clarify') {
                    renderChips(knowledge.categories.map((category) => category.label));
                } else {
                    renderChips(defaultChips());
                }
                OrbitStore.saveMessage(reply).catch(() => {});
            } catch (error) {
                console.error('ORBIT could not answer', error);
                appendMessage({ client_id: uuid(), astronaut_id: owner, role: 'assistant', message: 'Something went wrong while answering. Please try again.', intent: 'error', created_at: new Date().toISOString() });
            } finally {
                busy = false;
                if (!online) registerBackgroundSync();
                refreshStatusText();
                flushQueue();
            }
        }

        async function clearChat() {
            if (!window.confirm('Clear the chat on this device? Messages not yet synced will be removed too. Messages already saved to the server stay there.')) return;
            await OrbitStore.clearHistory(owner);
            context = {};
            el.log.replaceChildren();
            welcome();
            renderChips(defaultChips());
            refreshStatusText();
        }

        // ---------- voice ----------
        function setupVoice() {
            const Recognition = globalThis.SpeechRecognition || globalThis.webkitSpeechRecognition;
            if (Recognition && el.mic) {
                el.mic.hidden = false;
                el.mic.addEventListener('click', () => {
                    const recognition = new Recognition();
                    recognition.lang = 'en-US';
                    recognition.interimResults = false;
                    recognition.onresult = (event) => { el.input.value = event.results[0][0].transcript; el.input.focus(); };
                    recognition.onerror = () => flashNote('Voice input failed. Check microphone permission; some browsers need a connection.');
                    recognition.start();
                });
            }
            if ('speechSynthesis' in globalThis && el.speak) {
                el.speak.hidden = false;
                el.speak.setAttribute('aria-pressed', String(speakReplies));
                el.speak.addEventListener('click', () => {
                    speakReplies = !speakReplies;
                    localStorage.setItem('orbit-speak-replies', speakReplies ? '1' : '0');
                    el.speak.setAttribute('aria-pressed', String(speakReplies));
                    if (!speakReplies) speechSynthesis.cancel();
                });
            }
        }

        function speak(text) {
            if (!('speechSynthesis' in globalThis)) return;
            speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(String(text));
            utterance.rate = 0.95;
            speechSynthesis.speak(utterance);
        }

        // ---------- floating panel ----------
        function setOpen(open) {
            if (mode !== 'floating') return;
            el.panel.hidden = !open;
            el.launch.setAttribute('aria-expanded', String(open));
            root.classList.toggle('is-open', open);
            if (open) {
                el.input.focus();
            } else {
                el.launch.focus();
            }
        }

        function focusables() {
            return [...el.panel.querySelectorAll('button:not([hidden]), input, [href]')].filter((node) => node.offsetParent !== null && !node.disabled);
        }

        function trapFocus(event) {
            if (event.key !== 'Tab' || mode !== 'floating' || el.panel.hidden) return;
            const items = focusables();
            if (items.length === 0) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && (document.activeElement === first || !el.panel.contains(document.activeElement))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function attach() {
            el.form.addEventListener('submit', (event) => {
                event.preventDefault();
                const text = el.input.value;
                el.input.value = '';
                ask(text);
            });
            el.menuToggle.addEventListener('click', () => setMenu(el.menu.hidden));
            el.clear.addEventListener('click', clearChat);
            if (mode === 'floating') {
                el.launch.addEventListener('click', () => setOpen(el.panel.hidden));
                el.close.addEventListener('click', () => setOpen(false));
                el.minimise.addEventListener('click', () => setOpen(false));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !el.panel.hidden) setOpen(false);
                    trapFocus(event);
                });
            } else {
                el.panel.addEventListener('keydown', trapFocus);
            }
            window.addEventListener('online', checkConnection);
            window.addEventListener('offline', () => setOnline(false));
            setInterval(checkConnection, PING_MS);
        }

        async function start() {
            attach();
            renderMenu();
            setupVoice();
            refreshStatusText();
            el.log.append(h('div', { class: 'oc-orbit-skeleton', 'aria-hidden': 'true' }, [h('span'), h('span')]));
            try {
                await loadKnowledge();
            } catch (error) {
                el.log.replaceChildren();
                showNotice('ORBIT could not load its knowledge files. Reconnect once so they can be cached for offline use.');
                return;
            }
            await refreshSnapshot();
            await renderHistory();
            await checkConnection();
            flushQueue();
        }

        // Public hook so other pages (e.g. Body Map) can open ORBIT with a prefilled question.
        globalThis.OrbitAssistant = {
            askAbout(text) {
                if (mode === 'floating') setOpen(true);
                if (el.input) el.input.value = text;
                el.form?.requestSubmit();
            },
        };

        start();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
