# OrbitCare — Improvement Roadmap (instructions for Claude in VS Code)

> **Read this whole file first, then work one phase at a time.** Do not skip ahead. After each phase: run migrations, run `php artisan test`, check the "Done when" list, commit (`git commit -m "phase N: ..."`) and tell me what changed and what I should check in the browser.

---

## 1. Context

**Project:** OrbitCare — a Laravel + MySQL web app for the **2026 NASA Space Apps Challenge: "Create Health Monitoring Software for Astronauts on Space Missions"** (intermediate, subjects: Human Exploration, Software, Space Exploration).

**The challenge, in one sentence:** build software that *gathers health indicators* and *enables astronauts to evaluate and act on the status of their health*, especially on long missions where astronauts must spot changes in themselves (radiation, isolation/confinement, altered gravity, closed environment → immune changes, bone loss, cardiovascular events, behavioural-health problems).

**Our entry is judged by NASA-affiliated judges**, so it must stand out on: **domain depth, explainability, autonomy, polish, and a flawless live demo.**

**Stack (keep it):** Laravel 12, PHP 8.2+, MySQL, Blade, Bootstrap 5, Chart.js, Vite. All assets bundled locally (no CDNs — the demo must work offline). Local dev runs on **XAMPP at `http://localhost/OrbitCare/public`** (sub-folder install — never hard-code root-relative URLs; always use `route()`, `asset()` and `@vite`).

### What exists today (Phase 1 output)
- Login/logout, roles (`astronaut`, `medical_officer`, `admin`), `EnsureRole` middleware.
- Tables: `users`, `missions`, `crew_profiles`, `metric_definitions`, `health_readings`, `daily_checkins`, `audit_events`.
- Astronaut pages: dashboard (4 metric cards, HR/sleep/fatigue charts, data table), daily check-in, manual heart-rate readings.
- Seeded fictional crew OC-01 (stable), OC-02 (sleep↓ fatigue↑), OC-03 (stale data) with **synthetic** data, labelled "Simulation".
- Medical officer/admin see only a placeholder page.
- 7 feature tests.

### Known problems to fix (see Phase 0)
1. **All icons render as empty boxes** (Bootstrap Icons font not loading — the page is under `/OrbitCare/public`, so font URLs in the built CSS probably 404).
2. The Inter font is probably not loading either (same cause).
3. Heart-rate chart auto-zooms to 56–60 bpm, so a 3-bpm wobble looks dramatic. Seeded data repeats in an obvious 3-day pattern (looks fake).
4. Medical officer/admin dead-end on a placeholder page.
5. No alerts, no follow-ups, only 4 indicators — too thin for the challenge.
6. Looks like a generic Bootstrap admin template, not a space-mission product.

---

## 2. Non-negotiable rules

1. **No diagnosis, no treatment or medication advice.** OrbitCare is a *research and decision-support prototype*. Show: "Experimental rule — not a medical device."
2. **"No flag" never means "healthy."** It means *no implemented rule triggered*. Never build a single "health score/percentage".
3. **Statuses (exact codes):** `no_flag`, `review`, `priority_review`, `insufficient_data`. Always shown as **text + icon + colour** (never colour alone).
4. **Data provenance on everything:** every reading/check-in carries `source` = `manual` | `csv_import` | `simulation`. Synthetic data must be visibly labelled. Never present simulated values as NASA measurements.
5. **Missing/old data is shown as "stale" or "no data", never as normal.** Charts show gaps as gaps.
6. **Thresholds are team assumptions, not NASA facts.** Store them in `alert_rules.parameters` (configurable), label them *experimental*, and cite sources only where a source really supports the indicator (not the threshold).
7. **Every scientific claim in the UI has a source or is marked "assumption".** Do not invent citations or URLs. Put sources in `research_sources`, and mark each `verified_at` only after a human opens the link. If unsure, write "source to be verified".
8. **Security:** server-side authorisation (Policies) for every record; astronauts see only their own data; medical officers only assigned crew; admins manage accounts but get **no automatic access to health records**. Exports obey the same rules. Never expose IDs that allow guessing.
9. **Offline-first demo:** no CDN links; fonts/icons/scripts bundled. Must work with the internet unplugged.
10. **Times:** stored in UTC, always shown with the "UTC" label.
11. **Keep it maintainable:** Form Requests for validation, Policies for authorisation, Services for logic (`AlertEvaluationService`), PHP Enums for statuses/sources, small controllers, Laravel Pint formatting. No big new dependencies unless a phase says so.
12. **Don't break existing seeds/tests.** `php artisan migrate:fresh --seed` must always produce a working demo.

---

## 3. Judging lens (design every phase around this)

NASA Space Apps projects are commonly assessed on **Impact, Creativity, Validity, Relevance and Presentation** (confirm the exact wording on the official site). Map our work to them:

| Criterion | How OrbitCare should win it |
|---|---|
| **Relevance** | Covers the challenge's named risks, not just sleep and heart rate (see indicator catalogue, Appendix A). |
| **Validity** | Transparent rules, evidence snapshots, provenance labels, research page, honest limitations. |
| **Impact** | Works when Earth communication is delayed or lost (crew autonomy); gives actionable, explainable guidance. |
| **Creativity** | Body-system readiness map, mission timeline, Earth-link simulator, scenario player. |
| **Presentation** | Mission-control look & feel, flawless guided demo, strong README, short video. |

---

## 4. Phases

### Phase 0 — Fix what's visibly broken (do this first, ~1 hour)

**Goal:** the app we already have must look and behave correctly.

Tasks
1. **Diagnose the icon problem.** Open DevTools → Network; look for 404s on `.woff2`/`.woff` files. Also check that `public/hot` does **not** exist unless `npm run dev` is running (delete it otherwise).
2. **Fix it so it works under any sub-folder (XAMPP) and offline.** Preferred fix: in `vite.config.js` set `build.assetsInlineLimit` high enough (e.g. `1024 * 1024`) so the icon and Inter fonts are inlined into the CSS. Alternative: `renderBuiltUrl` / correct `base`. Rebuild with `npm run build`, hard-refresh, verify icons and Inter render. Add a short "Troubleshooting" section to `SETUP.md`.
3. **Chart honesty.** HR chart y-axis: fixed sensible range (e.g. 40–100 bpm) or `suggestedMin/Max` so small variation looks small. Keep sleep 0–12, fatigue 1–5. Add axis units and tooltips showing value + source + time.
4. **Realistic seed data.** Replace the sine-wave pattern in `DemoSeeder` with deterministic pseudo-random noise (`mt_srand(<fixed seed>)`) so each crew member looks like a real person but the demo is reproducible.
5. **Staff placeholder** → at minimum a real "Crew overview" table (crew code, name, last check-in time, data freshness) for medical officers, limited to assigned crew. (Full version in Phase 5.)
6. Add `care_assignments` (medical officer ↔ crew) now, because it's needed for correct access control.
7. Fix layout inconsistencies (e.g. content width differs between pages when a scrollbar is present; use a consistent max-width container).

Done when: icons and fonts render on `localhost/OrbitCare/public`; charts look sane; medical officer sees only assigned crew; tests pass.

---

### Phase 1 — Visual identity & design system ("mission-control" look)

**Goal:** it must *feel* like flight software, not a Bootstrap template. Calm, dense, professional, accessible.

Tasks
1. **Design tokens** in `resources/css/orbitcare.css` (CSS variables) for **light "Day" and dark "Night" themes**. Add a theme toggle (persisted in `localStorage`, default to `prefers-color-scheme`). Night mode matters: low-light spacecraft use.
2. Palette (keep status colours exactly): navy `#0B1220`, blue `#2563EB`, teal `#0F766E`; no-flag green `#15803D`, review amber `#B45309`, priority red `#B91C1C`, unknown grey `#64748B`. Add dark-theme equivalents with checked contrast (WCAG AA).
3. **Typography:** Inter for UI; a monospace (e.g. JetBrains Mono via `@fontsource`) for numbers, timestamps, crew codes. Tabular numerals for metric values.
4. **Logo + favicon:** simple inline SVG mark (orbit ring + pulse line). Add to sidebar, login, favicon, print header.
5. **Login page redesign:** full-height split layout — left: brand, one-line mission statement, "prototype / not a medical device" note; right: form. Subtle CSS-only starfield / Earth-limb gradient (no images, no heavy animation, respect `prefers-reduced-motion`). Keep demo-account helper (local env only), as clickable "Sign in as…" buttons.
6. **App shell:** sticky top bar with **mission progress bar** ("Day 31 of 180"), **Earth-link status chip** (placeholder until Phase 6), theme toggle, user menu. Sidebar groups (Overview / Health / Mission / Reports), active-state indicator, collapsible on desktop, off-canvas on mobile.
7. **Component library** (Blade components under `resources/views/components`): `metric-card` (value, unit, **delta vs baseline** ▲/▼, sparkline, status, source, freshness), `status-badge`, `source-label`, `page-header`, `empty-state` (SVG illustration + call to action), `skeleton`, `toast`, `confirm-modal`, `data-table`, `stat-ring` (data completeness).
8. **Chart styling:** one shared Chart.js theme (fonts, grid, tooltips, colours for both themes), dashed **baseline line + shaded normal band**, markers for alerts and mission events (use `chartjs-plugin-annotation`, bundled via npm).
9. Add a dev-only **`/_components`** preview page showing every component in both themes.

Done when: both themes work, all pages use the shared components, Lighthouse accessibility ≥ 95 on dashboard and login, no layout shift between pages.

---

### Phase 2 — Expand the data model & indicators (domain depth)

**Goal:** cover the challenge's health risks, not only sleep and heart rate. See **Appendix A** for the indicator catalogue.

Tasks
1. Migrations + models for: `baseline_profiles`, `alert_rules`, `alerts`, `alert_evidence`, `followups`, `mission_events`, `research_sources`, `import_batches`, `symptom_logs`, `care_assignments` (if not done), and extend `metric_definitions` (category/body system, direction-of-concern, scale description, plausibility bounds, freshness hours).
2. Extend **daily check-in** with: mood (1–5, 5 = best), stress (1–5, 5 = highest), social connectedness/isolation (1–5), symptom checklist (tags, see Appendix A), planned vs completed exercise (aerobic / resistive minutes). Keep it completable in ~60 seconds (progressive sections, sensible defaults, large touch targets).
3. **Vitals entry** form for: resting HR, blood pressure (systolic/diastolic), body temperature, SpO₂, body mass. Validate with plausibility bounds from `metric_definitions` (data-quality check, not medical thresholds).
4. **Simulated environment feeds** (clearly labelled `simulation`): cabin CO₂, simulated radiation dosimeter (µSv/day). Optional but high-impact.
5. **CSV import:** template download, preview, row-by-row validation, all-or-nothing transaction, duplicate handling, `import_batches` result page. Columns: `crew_code,metric_code,value,unit,observed_at,source`.
6. **Baselines:** `baseline_profiles` computed from a fixed **pre-mission/reference period** (e.g. first 7 seeded days). Never recompute a baseline using the same data being evaluated. Show baseline on charts.
7. Update seeders: richer, deterministic data for all indicators and all three scenarios (+ a 4th: sustained HR shift + exercise-countermeasure gap).

Done when: all indicators can be entered/imported, stored with unit + source + timestamp, and appear on the dashboard with freshness; invalid values are rejected with clear messages.

---

### Phase 3 — Explainable alert engine (the core "evaluate" feature)

**Goal:** turn data into transparent, reproducible alerts.

Tasks
1. Create `App\Services\AlertEvaluationService` + one small class per rule (`SleepReductionWithFatigueRule`, `RestingHeartRateShiftRule`, …) implementing a `RuleInterface`. Use PHP Enums for `AlertStatus` and severity.
2. Implement the **starter rules in Appendix B** (all parameters stored in `alert_rules.parameters`, versioned, labelled *experimental assumption*).
3. Each evaluation: loads eligible data → checks **freshness and minimum sample size** → compares to the **individual baseline** → returns status + **evidence snapshot** (which readings, dates, baseline, rule version) → creates or updates the alert **without duplicates** → keeps history.
4. Triggers: after check-in saved, reading saved, CSV import, and via `php artisan orbitcare:evaluate {--crew=}` (used by the scenario player).
5. Behaviour rules: missing data → `insufficient_data` (not `no_flag`); isolated odd reading ≠ trend; same input → same result (idempotent); corrected data re-evaluates; an unresolved alert is **never silently cleared** when numbers improve (it stays open until acknowledged/closed with a note).
6. Explanation text templates, e.g.: *"Your sleep was 5.1 h on average over the last 3 days versus your baseline of 7.2 h (−2.1 h), and your fatigue rose from 2 to 4. This is an experimental rule (v1). Review the readings and complete a follow-up."*
7. **Tests** for: stable → no alert; sustained change → alert with correct explanation; boundary values; insufficient samples; stale data; idempotent re-run; corrected data; no duplicate alerts; permissions.

Done when: seeded OC-02 produces an explained `review` (and later `priority_review`) alert, OC-01 produces none, OC-03 shows `insufficient_data`, and tests prove it.

---

### Phase 4 — Astronaut experience: evaluate & act

**Goal:** the full journey *record → review → understand → act*, beautifully.

Tasks
1. **Dashboard v2:**
   - Top: greeting + mission day + check-in prompt.
   - **Body-system readiness map** (inline SVG human silhouette with 6 clickable regions: Cardiovascular, Sleep & circadian, Musculoskeletal, Behavioural, Immune/General, Radiation/Environment). Each shows status badge (text+icon+colour) and the indicators behind it. Click → drill-down panel. (Inspired by the body-diagram monitor on the challenge page.)
   - Metric cards with **delta vs baseline**, sparkline, freshness, source.
   - Active alerts panel (severity, plain-language reason, "Review" button).
   - **Data completeness ring** (what % of expected inputs were recorded) — not a health score.
   - Recent follow-ups and upcoming "due" check-ins.
2. **Trends page:** date-range filter (7/14/30/mission), multi-indicator charts with baseline band, alert + mission-event markers, **table view toggle**, CSV download of the visible range.
3. **Alert detail page:** what changed, evidence table, baseline, rule + version, "experimental" notice, data-quality limits, **status workflow** `open → acknowledged → under_review → closed` (closing requires a note), full timeline.
4. **Follow-up actions** (record only, no treatment advice): rechecked measurement, repeated check-in, requested medical-officer review, logged observation, followed an onboard protocol (free text + protocol name). Each follow-up is timestamped and attributed.
5. **Guided self-assessment** ("Evaluate my status"): a short, non-diagnostic checklist wizard per body system that pulls in recent data, shows what is and isn't flagged, and ends with *recommended next step categories* (re-measure, contact medical officer, note and monitor). Never medication or diagnosis.
6. **Notifications:** in-app bell + toast for new alerts and overdue check-ins (no email/SMS needed).
7. Empty/loading/error states on every page.

Done when: an astronaut can go from a new alert to a documented, closed follow-up without losing the original evidence, and every screen explains itself.

---

### Phase 5 — Crew Medical Officer & Admin consoles

Tasks
1. **Medical officer — Crew overview:** assigned-crew table/cards with outstanding alerts, last check-in, stale/missing-data indicator, filters (needs review / insufficient data / all), sorted by *review need* — **not** by an invented health score.
2. **Crew member detail:** authorised summary (same body-system map + trends + alerts + follow-ups), add a medical-officer **review note**, request a re-check, close/reopen alerts with note. Every action audited.
3. **Follow-up queue** across crew with filters.
4. **Admin console:** users (create, edit role, activate/deactivate — never hard delete), missions (create/edit, start/end dates), crew profiles, medical-officer assignments, **alert-rule parameters editor** (versioned; changing a rule creates a new version), metric definitions viewer, audit-log viewer with filters. Admin has **no access to health data pages** (verify with tests).
5. Policies + tests: astronaut A vs B, officer assigned vs unassigned, admin blocked from health records, deactivated users blocked immediately.

Done when: each role has a useful, distinct home screen and tests prove the permission boundaries.

---

### Phase 6 — Autonomy & communications (our differentiator)

**Goal:** show the thing that matters on long missions — the crew must be able to act when Earth is far away.

Tasks
1. **Earth-link simulator** (admin/demo control + status chip in top bar): `Online`, `Delayed (configurable one-way minutes)`, `Blackout`. The chip is visible on every page.
2. **Outbound queue ("Earth sync package"):** summaries/alerts/follow-ups marked "share with Earth" are queued while the link is delayed/down and show *queued / sent / acknowledged* with simulated delay. Generate a downloadable summary bundle (JSON + printable PDF view) for ground support. Make clear what leaves the vehicle (privacy/consent notice).
3. **Autonomy panel:** when the link is down, the dashboard highlights "Onboard decision support" — guided self-assessment and local protocols remain fully functional. Messaging: *"Operating autonomously — Earth link unavailable since …"*.
4. **Run on a local network:** document `php artisan serve --host=0.0.0.0` / XAMPP LAN access so other laptops/phones act as crew devices without internet. Provide a one-page "onboard server" guide.
5. **Stretch (only if time permits):** installable **PWA** — manifest, service worker caching the app shell, offline check-in saved to IndexedDB and synced on reconnect with server-side validation and conflict handling (last submit wins + audit entry). Mark clearly as optional; do not let it destabilise the core.

Done when: toggling the Earth-link state visibly changes the UI, queued items sync with a simulated delay, and the app keeps working with Wi-Fi off (on the local network).

---

### Phase 7 — Mission simulation & demo mode (reliable presentation)

**Goal:** judges should see the whole story in 4 minutes, repeatably.

Tasks
1. **`ORBITCARE_DEMO_MODE`** env flag. Demo controls exist only when it's `true` and the environment is not production; reset can never touch a non-demo DB.
2. **Mission clock & scenario player** (admin/demo page): "Jump to mission day N", **play/fast-forward** (e.g. 1 day per 2 seconds) that inserts the scripted data day by day and runs `orbitcare:evaluate`, so alerts appear live on screen. Scenarios: *Stable*, *Sleep loss + rising fatigue*, *Sustained HR shift*, *Missing/stale data*, *Exercise countermeasure gap*, *Simulated radiation-event day*, *Earth link blackout*.
3. **One-click reset** to the seeded baseline state (confirmation modal) + `php artisan orbitcare:demo-reset`.
4. **Guided demo tour:** a lightweight step-by-step overlay (bundle a small library locally, or write minimal JS) that walks through: stable → change appears → explanation → follow-up → Earth link down → still working.
5. **Fixed date anchor** option so seeded data never goes stale on presentation day.
6. A `DEMO_SCRIPT.md` with the exact click path and talking points (see Appendix D).

Done when: from a reset, anyone can run the full story in under 4 minutes with no manual data entry.

---

### Phase 8 — Reports, research & provenance

Tasks
1. **Printable health summary** (print CSS → "Save as PDF"): crew code, mission, date range, indicator summaries, completeness, alerts + follow-ups, source labels, generation time (UTC), prototype limitations.
2. **CSV exports** of readings, check-ins, alerts (same permissions as on-screen).
3. **Research & limitations page:** each `research_sources` entry shows title, publisher, direct URL, indicator supported, careful finding summary, how we use it, limitation, access date, and a "verified by human" marker. Suggested starting points to **verify and add only if they really apply**: NASA Human Research Program (HRP) and its Human Research Roadmap risks; NASA Life Sciences Data Archive / Life Sciences Portal; NASA Open Science Data Repository (OSDR); NASA "Fundamentals of Human Health" pages; the challenge's own Resources tab. Do **not** invent citations.
4. **Data provenance statement** page: what is imported research data, synthetic demo data, manual entry, and team assumption.
5. **"How alerts work" page** in plain language + rule catalogue with versions and the *experimental* label.
6. **Glossary/Help** drawer (what each indicator and scale means, 1–5 scales explained).

Done when: someone unfamiliar can tell evidence from simulation from assumption, and every number in the UI can be traced.

---

### Phase 9 — Quality, security, accessibility, performance

Tasks
1. **Tests:** feature tests for auth, policies, check-in uniqueness/correction, imports + rollback, every alert rule boundary, insufficient data, idempotency, follow-up workflow, export permissions, demo-mode guards. Aim for the core logic to be well covered; run in CI-style with `php artisan test`.
2. **Security pass:** Form Requests everywhere, mass-assignment (`$fillable`) audit, CSRF, login throttling, session hardening, security headers, no sensitive data in logs, `APP_DEBUG=false` in production, IDOR checks on every route, upload limits.
3. **Accessibility:** keyboard navigation, visible focus, labels/aria on forms and charts, table alternatives, `prefers-reduced-motion`, colour-blind-safe (text + icon + colour), contrast AA in both themes.
4. **Performance:** eager loading (no N+1), DB indexes, cache heavy aggregates per request, Vite production build, small JS.
5. **Responsive checks:** phone, tablet, laptop; long names/notes; slow network; double-click submits.
6. **Code quality:** Laravel Pint, consistent naming, remove dead code, docblocks on services and rules.

Done when: tests green, no known permission holes, Lighthouse a11y ≥ 95 and performance reasonable on key pages.

---

### Phase 10 — Deployment & submission package

Tasks
1. **Deployment guide** for our server (PHP version/extensions, MySQL DB + restricted user, `composer install --no-dev`, `npm run build`, `.env` production values, `APP_KEY`, document root = `public/`, writable `storage/` and `bootstrap/cache`, HTTPS, `APP_DEBUG=false`, migrate + seed demo data only, smoke test from another device). Include **backup/rollback** steps.
2. **Local fallback** kit: prepared laptop (XAMPP or `artisan serve`), compiled assets, tested from a second device on LAN.
3. **Professional README.md** (rewrite): hero screenshot, problem, solution, features, architecture diagram (Mermaid), data model, how alerts work, tech stack, install steps, demo accounts, demo script, tests, limitations, roadmap, credits.
4. **Screenshots** (light/dark, desktop/mobile) saved in `docs/screenshots/`.
5. **`docs/`** folder: architecture, alert-rule catalogue, provenance statement, NASA source register, test summary, known limitations, team contributions.
6. **Pitch support:** 7-slide outline (Appendix D), 2–3 minute backup video script, project description text (short + long).
7. Final **freeze checklist**: `migrate:fresh --seed` works, tests green, demo script rehearsed twice, both hosted and local versions pass the story.

Reminder: confirm the official 2026 rules on advance project work, reused code, AI assistance and submission format with the organisers before submitting.

Done when: another developer can install from the README in 15 minutes and another teammate can present from `DEMO_SCRIPT.md`.

---

## 5. Appendices

### Appendix A — Indicator catalogue (body-system grouping)

| Body system | Indicators | Type / unit | Notes |
|---|---|---|---|
| Cardiovascular | `resting_heart_rate` (bpm), `blood_pressure_systolic/diastolic` (mmHg), optional `hrv_rmssd` (ms) | measured / manual | Compare to **personal baseline**, not population norms. |
| Sleep & circadian | `sleep_duration` (h), `sleep_quality` (1–5, 5 best) | check-in | Add bedtime/wake time if time permits. |
| Musculoskeletal (countermeasures) | `exercise_aerobic_min`, `exercise_resistive_min`, planned vs completed, back-pain symptom tag | check-in | Frame as **countermeasure compliance**, never as "bone density". |
| Behavioural health | `mood` (1–5, 5 best), `stress` (1–5, 5 highest), `social_connectedness` (1–5), `fatigue` (1–5, 5 most) | check-in | Isolation and confinement are named challenge risks. Wording must be neutral and non-diagnostic. |
| Immune / general | `body_temperature` (°C), symptom tags: headache, nausea, congestion, skin irritation, vision change, back pain | manual / tags | Tags are self-report only. |
| Radiation / environment | `radiation_dose_sim` (µSv/day), `cabin_co2_sim` (ppm) | **simulation only** | Always labelled simulation; never imply real measurements. |
| Body mass / hydration | `body_mass` (kg), optional `hydration_l` | manual | Optional. |

Every metric stores: code, label, unit, scale description, direction of concern, plausibility bounds (data-quality only), freshness hours.

### Appendix B — Starter alert rules (all *experimental assumptions*, parameters configurable and versioned)

| Code | Inputs | Trigger (default parameters) | Result |
|---|---|---|---|
| `sleep_fatigue_v1` | sleep duration, fatigue | Mean sleep of last 3 days ≤ baseline − 1.5 h **and** mean fatigue of last 3 days ≥ baseline + 1 | `review`; `priority_review` if it persists ≥ 5 days and fatigue ≥ 4 |
| `rhr_shift_v1` | resting HR | Mean of last 3 days ≥ baseline + 8 bpm | `review`; ≥ baseline + 15 bpm → `priority_review` |
| `exercise_gap_v1` | exercise planned vs completed | Completed < 50% of planned on ≥ 4 of last 7 days | `review` |
| `behavioural_trend_v1` | mood, stress, connectedness | Mood ≤ baseline − 2 (or stress ≥ baseline + 2) for ≥ 4 of last 5 days | `review` (suggest contacting medical officer; no diagnosis) |
| `data_gap_v1` | any check-in | No check-in for ≥ 2 days, or fewer than the minimum samples | `insufficient_data` |
| `radiation_budget_sim_v1` | simulated dose | Cumulative simulated dose > configured fraction of mission planning budget | `review` (**simulation, illustrative only**) |

Each rule documents: input indicators, minimum samples, baseline reference, evaluation window, missing-data behaviour, trigger, severity, explanation template, evidence/assumption note, version.

### Appendix C — Styling quick reference

- Day theme: bg `#F8FAFC`, card `#FFFFFF`, text `#0F172A`, secondary `#475569`, border `#E2E8F0`.
- Night theme: bg `#0B1220`, card `#111A2E`, text `#E5E7EB`, secondary `#94A3B8`, border `#1E293B`.
- Spacing scale 4/8/12/16/24/32 px; card radius 12 px; buttons ≥ 44 px high; body 16 px; metric values 30–36 px (mono, tabular numerals).
- Motion: ≤ 200 ms, functional only; respect `prefers-reduced-motion`.
- Avoid: glow effects, heavy gradients, busy backgrounds behind text, rainbow charts, tiny text.
- Every status = **icon + text + colour**.

### Appendix D — Demo script & pitch outline (4 minutes)

1. **Problem (20 s):** long missions, delayed Earth contact, astronauts must spot changes themselves.
2. **Stable crew member (30 s):** dashboard, body-system map, baseline deltas, provenance labels.
3. **Change appears (60 s):** scenario player fast-forwards OC-02; alert appears with plain-language explanation and evidence.
4. **Act (45 s):** acknowledge, guided self-assessment, record follow-up, close with note.
5. **Medical officer view (30 s):** assigned-crew overview, review note.
6. **Autonomy (30 s):** switch Earth link to Blackout; app keeps working on the local network; sync package queued.
7. **Evidence & honesty (25 s):** research page, provenance, "experimental, not a medical device".

Slides: Problem → Solution → Live demo → How alerts work → Autonomy → Evidence & limitations → Roadmap/Team.

---

## 6. Working agreement for Claude

- Start with **Phase 0** and tell me when it is done before starting Phase 1.
- If a phase is too big, split it and say so; never leave the app half-broken — keep `main` demo-able at every commit.
- When something is ambiguous (e.g. which source supports which indicator), **ask me rather than inventing**.
- After each phase give me: (1) summary of changes, (2) commands to run, (3) pages to check in the browser, (4) anything I must verify manually (especially NASA sources).