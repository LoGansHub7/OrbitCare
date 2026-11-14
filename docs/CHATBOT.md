# ORBIT assistant: phases 1–4

ORBIT is a local, experimental prototype, not a medical device or a substitute
for the mission medical team. The local intent engine supports the knowledge
topics in `public/chatbot/`, latest cached readings, and simple follow-up
context. Response cards (emergency, checklist, vitals) and the chat UI are
in place, but the medical content needs review by the mission medical team.

## User interface

- **Launcher:** a floating button on every page for astronauts, medical officers and
  admins (bottom-right) opens the chat panel. The dot shows Online or Offline. The badge
  counts triggered vitals rules. Staff accounts use local-only mode ("Local only on this
  device"). They never call the astronaut-only session, snapshot or sync endpoints.
- **Full page:** `/assistant` (sidebar link "ORBIT assistant") shows the same
  component in page mode. The floating launcher is hidden on that page.
- **Cards:** emergency (red, `role="alert"`), checklist (`- [ ]` and `- [x]`
  items), vitals (status Normal, Watch or Insufficient data, with a lazy-loaded
  Chart.js sparkline), and a flight-surgeon handoff card. The handoff card
  contains no personal data and has a copy button.
- **Voice:** the microphone uses the Web Speech API when the browser supports it.
  Speech recognition may need a connection in some browsers. Read-aloud is a
  toggle saved in `localStorage` (`orbit-speak-replies`).
- **Clear chat:** asks for confirmation and removes only the local IndexedDB
  history for the current astronaut. It does not touch the server.
- **Accessibility:** focus is trapped in the open panel and returned to the
  launcher on close. Esc closes the panel. Replies are announced through an
  ARIA live region. Touch targets are at least 44px. Reduced-motion users get
  no typing animation.
- **Theme:** the tokens are CSS variables in `public/chatbot/assistant.css`.
  The default is day. `[data-theme="night"]` overrides them.
- **Phone:** at 600px and below the panel is full-screen.

### Extending the UI

- Add a card: return a `card` object from `engine.js` (`type`, plus fields). Then
  add a renderer in `assistant.js` that builds the DOM with `textContent`, never
  `innerHTML`.
- Add a menu group: edit the categorised menu in `assistant.js`. Each phrase must
  route to a topic in the engine (test it with `node` before shipping).

### Routing notes

Generic advice questions ("How do I sleep better in orbit?", "How do I manage my
weight?") route to the matching topic. Personal metric routing applies only when
the question asks about the astronaut's own data (for example "my", "latest",
"why", "did I", or a follow-up). Metric words alone do not trigger it.

## Knowledge content

The browser loads three JSON files, which are cached for offline use:

- `intents.json`: meta replies (`greeting`, `help`).
- `health-knowledge.json`: `disclaimer`, `categories` (with the NASA HRP hazard
  each one maps to), and `topics`. Each topic has `id`, `category`, `title`,
  `phrases`, `answer`, `steps` (3–4 items), `escalate` and `source`.
- `procedures.json`: red-flag `procedures` with `triggers`, `steps`, `escalate`
  and `source`. A trigger matches only as whole words after normalization, and
  red flags are checked before any other routing.
- `faq.json`: `questions` mapped to topic ids for the "What can I ask?" menu.

To add a topic, add an entry to `health-knowledge.json` with a unique `id` and an
existing `category`, then add at least one FAQ question for it. To add a red-flag
procedure, add an entry to `procedures.json` with clear triggers. The tests in
`tests\js\orbit-knowledge.test.mjs` check structure, unique ids, FAQ references,
self-routing of each topic's first phrase, and trigger detection.

**Medical content review is required before any real use.** The content is
general, not clinically validated, and the source notes are general references
(for example NASA Human Research Program risk areas). The mission medical team
must verify every answer, step, escalation rule and procedure against the
approved onboard procedures.

## Architecture

- The authenticated layout includes the launcher and loads the files in
  `public/chatbot/`. The JSON files there are the knowledge base.
- `public/sw.js` caches the local assistant assets, JSON, icon, font and generic
  offline page. It uses network-first navigation and falls back to the offline
  page without caching authenticated HTML or API responses.
- IndexedDB stores an astronaut's messages, pending sync queue and latest health
  snapshot under their user ID. Messages use UUIDs for idempotent server sync.
- The astronaut-only session, snapshot and message-sync routes use Laravel's
  session authentication, role checks, CSRF protection and rate limiting. The
  server checks the queue owner against the current session.
- The browser tries Background Sync when supported and also retries on a
  connection or visibility change. A failed sync leaves messages in the queue.
- `public/chatbot/engine.js` normalizes input, extracts entities and uses
  weighted exact/fuzzy phrase matching with a confidence threshold. Ambiguous
  requests ask for clarification. Conversation context lasts until the page
  reloads; the cached snapshot contains latest observations, not history.
- Personal replies include source, observation time, freshness where available,
  and existing experimental trend-rule results. They do not use input plausibility
  bounds as clinical thresholds or label measurements medically normal.
- A basic emergency message directs the user to the mission medical team and
  mission protocol. It is not a validated triage system; no text-matching
  implementation should be relied on to identify every emergency.

Browser storage is not encrypted or protected against another person with
access to the same browser profile. Use a private device and clear browser site
data when the cached health data and conversations are no longer needed.
Clearing site data also deletes unsynced messages. The generic offline fallback
does not expose personal data and its conversations are temporary.

## Integration with OrbitCare (Phase 5)

- **Page-aware chips.** `PAGE_CHIPS` in `public/chatbot/assistant.js` maps route
  names (`dashboard`, `alerts.`, `readings.`, `checkin.`, `monitor.*`) to a greeting
  and suggested questions. The `data-page` attribute on `<x-orbit-assistant />`
  supplies the route name.
- **Proactive alerts.** Cached snapshot `rule_states` with value `triggered` set the
  launcher badge. Asking "explain my alerts" lists only those rules, and the reply
  says it is not a diagnosis. Wording comes from `RULE_LABELS` in `engine.js`.
- **Staff activity (`/staff/chatbot`).** Medical officers see question counts for the
  last 30 days, top topics, and emergency counts for assigned crew only. Message text
  is never read or displayed. Admins see aggregate totals only (crew count, questions,
  emergency prompts, top topics) with no names, rows or message text. Astronauts get 403.
  Implemented in `ChatbotOversightController`.
- **Saving.** Online messages sync to `chatbot_messages` by `(astronaut_id, client_id)`.
  Offline messages wait in the IndexedDB queue and are sent without duplicates.


## Quality and testing (Phase 6)

- Accuracy: `tests/js/orbit-accuracy.test.mjs` runs 55 sample questions, with a
  required pass rate of at least 90%. Current result: 100% (55/55). Add a case for
  every new intent.
- Run the JS suites with explicit file paths:
  `node --test tests/js/orbit-engine.test.mjs tests/js/orbit-knowledge.test.mjs tests/js/orbit-accuracy.test.mjs`.
- Run the PHP suite with `php artisan test --filter=Chatbot`.
- Service worker: any change to `public/chatbot/*.js` or JSON requires bumping `CACHE`
  in `public/sw.js` (currently `orbit-assistant-v9`). Otherwise clients keep the old
  cache.

Run `php artisan migrate` on the intended local development database; it applies
all pending migrations, not only the chatbot migration. Build the existing
frontend with `npm run build` if needed. No new package is required.

1. Open the application on `localhost` or HTTPS, sign in as an astronaut and
   wait for ORBIT to display **Online**. Visit once online so the service worker
   can install and precache the assistant.
2. Open ORBIT, then use browser developer tools to switch the network offline.
   The existing page can still display and store new assistant messages.
3. Navigate to another page while offline to see the generic fallback. It must
   not display authenticated health records.
4. Reconnect and return to an authenticated page. Pending messages are retried;
   examine IndexedDB's `queue` store and the `chatbot_messages` table to confirm
   synchronization. Background Sync support varies by browser.

The assistant service worker requires a secure context; browsers normally treat
`localhost` and `127.0.0.1` as secure for local development. Service workers
and their caches are specific to the origin and application path.

Run `node --test tests\js\orbit-engine.test.mjs tests\js\orbit-knowledge.test.mjs`
for the local engine and knowledge tests. Intent phrases and meta replies live in
`public/chatbot/intents.json`; topics live in `health-knowledge.json`. After
changing offline assets, increment the cache version in `public/sw.js`.
