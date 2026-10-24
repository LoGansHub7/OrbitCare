# OrbitCare — Phase 1 (Foundation + first complete journey)

Login → daily check-in → dashboard, with manual heart-rate readings, three seeded fictional astronauts and 14 days of **synthetic** data.

## Install (Laravel 12, PHP 8.2+, MySQL, Node 18+)

```bash
composer create-project laravel/laravel orbitcare
cd orbitcare
```

1. Extract this zip into the `orbitcare` folder and **allow overwriting** (replaces `routes/web.php`, `vite.config.js`, `app/Models/User.php`, `AppServiceProvider.php`, `DatabaseSeeder.php`).
2. Create an empty MySQL database called `orbitcare`.
3. Edit `.env`:
   ```
   APP_NAME=OrbitCare
   APP_ENV=local
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=orbitcare
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Install bundled front-end packages and build:
   ```bash
   npm install bootstrap bootstrap-icons chart.js @fontsource-variable/inter
   npm run build        # or: npm run dev
   ```
5. Migrate and seed, then run:
   ```bash
   php artisan migrate:fresh --seed
   php artisan serve
   ```
   Open http://127.0.0.1:8000. Share on your LAN with `php artisan serve --host=0.0.0.0`.

Demo accounts (password `orbitcare-demo`): `astro1@orbitcare.test` (stable), `astro2@orbitcare.test` (sleep down, fatigue up), `astro3@orbitcare.test` (stale data), `medic@orbitcare.test`, `admin@orbitcare.test`.

Run tests: `php artisan test` (needs the PHP sqlite extension, or point phpunit.xml at a MySQL test database).

**Before every demo run `php artisan migrate:fresh --seed`** — seeded dates are relative to today, so old data would otherwise go stale.

## What's in Phase 1
- Session login/logout, throttled, deactivated users blocked, server-side role middleware (`EnsureRole`)
- Tables: users (+role), missions, crew_profiles, metric_definitions, health_readings, daily_checkins (unique per crew/day), audit_events
- Daily check-in with explicit scales; resubmitting corrects the entry and writes an audit event
- Manual resting-HR readings with plausibility bounds (data quality only, not medical thresholds)
- Dashboard: 4 metric cards with value/unit/time/source/freshness, HR + sleep + fatigue charts (gaps shown as gaps), 7/14-day filter, accessible data table
- Source labels (Simulation / Manual) and "stale / no data" states instead of looking healthy
- All assets bundled locally (no CDN) so it works offline
- Astronauts only ever query their own crew profile — no record IDs in URLs

## Not yet built (next phases)
Alert rules + engine, alert detail/follow-ups, medical-officer crew overview, admin/assignments, CSV import, reports, research page, scenario reset tooling.

## Notes
- "Alerts" panel is a placeholder; "no flag" will never be shown as "healthy".
- Times are stored/displayed in UTC (labelled).
- Verify the 2026 Space Apps rules on advance work before using this as the competition submission.
