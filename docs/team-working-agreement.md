# OrbitCare team working agreement and interface contract

**Phase:** 3  
**Last updated:** 2026-10-30

This document is the shared contract for changes that cross modules. It applies to every contributor before opening a pull request or merging directly by team agreement.

## Repository workflow

- Keep `main` runnable. Create one focused branch per change using `feature/<area>-<short-description>`, `fix/<area>-<short-description>`, or `docs/<short-description>`.
- Update from the shared branch before starting work: `git pull origin main`.
- Before publishing, run the relevant checks (`php artisan test` and/or `npm run build`) and review `git status`.
- Do not commit `.env`, database dumps, secrets, or generated `public/build` assets unless the team explicitly changes that policy.
- The integration owner reviews changes to `routes/web.php`, shared layouts/components, migrations, seeders, `composer.*`, `package*.json`, and this contract before merge.

## Local installation agreement

Requirements: PHP 8.2+, Composer, Node 18+, npm, and MySQL. MariaDB is not the agreed development target.

1. Copy `.env.example` to `.env`, set the MySQL connection for a local `orbitcare` database, and run `php artisan key:generate`.
2. Run `composer install` and `npm install`.
3. Run `php artisan migrate:fresh --seed` and `npm run build`.
4. Start the app with `php artisan serve`; open the address it prints.

The deterministic demonstration data is relative to today. Reset it only in a local demo database with `php artisan migrate:fresh --seed`.

## Naming and data contract

| Area | Required convention |
| --- | --- |
| Database tables/columns and request field names | `snake_case` |
| PHP classes | `PascalCase` |
| PHP methods, variables, routes, Blade props | `camelCase` where idiomatic; route names use dot notation |
| Dates submitted by forms/imports | ISO `YYYY-MM-DD` |
| Stored timestamps | UTC, stored in Laravel `datetime` columns |
| Displayed timestamps | Explicitly label UTC until user timezone support exists |
| Numeric decimals | Use a period as the decimal separator; do not localise stored values |
| Source labels | `manual`, `csv_import`, `simulation` |

### Metric definitions

| Code | Label | Canonical unit | Authoritative storage |
| --- | --- | --- |
| `resting_heart_rate` | Resting heart rate | `bpm` | `health_readings` |
| `sleep_duration` | Sleep duration | `hours` | `daily_checkins.sleep_hours` |
| `sleep_quality` | Sleep quality | `score` (1–5; 5 is best) | `daily_checkins.sleep_quality` |
| `fatigue_rating` | Fatigue rating | `score` (1–5; 5 is most fatigued) | `daily_checkins.fatigue_rating` |
| `exercise_duration` | Exercise duration | `min` | `daily_checkins.exercise_minutes` |

Do not duplicate daily check-in values into `health_readings`. Plausibility bounds are data-quality validation only; they are not clinical or alert thresholds.

### Status vocabulary

| Code | Meaning | Never imply |
| --- | --- | --- |
| `no_flag` | No implemented rule triggered. | A person is healthy or safe. |
| `review` | An experimental rule requests review. | A diagnosis or urgent medical conclusion. |
| `priority_review` | An experimental rule requests priority review. | A confirmed emergency or diagnosis. |
| `insufficient_data` | The rule cannot be evaluated with available/fresh data. | No concern exists. |
| `fresh`, `stale`, `no_data` | Data-freshness/display states, not health states. | A medical assessment. |

## Current route contract

| Method | Path | Route name | Access | Owner/module |
| --- | --- | --- | --- | --- |
| GET | `/health` | `health` | Public; no sensitive details | Foundation |
| GET | `/` | `home` | Public redirect | Foundation |
| GET/POST | `/login` | `login` | Guest only; POST throttled | Authentication |
| POST | `/logout` | `logout` | Authenticated | Authentication |
| GET | `/dashboard` | `dashboard` | Astronaut only | Dashboard |
| GET/POST | `/check-in` | `checkin.create` / `checkin.store` | Astronaut only | Daily check-in |
| GET/POST | `/readings` | `readings.index` / `readings.store` | Astronaut only | Readings |
| GET | `/alerts` | `alerts.index` | Astronaut only; own crew profile | Alert history |
| GET/POST | `/alerts/{alertId}` | `alerts.show` / `alerts.update` | Astronaut only; own alert | Alert review |
| GET | `/staff` | `staff.home` | Medical officer or administrator | Staff area |
| GET | `/staff/crew/{crewProfileId}` | `staff.crew.show` | Medical officer; assigned crew only | Crew review |
| GET | `/reports` | `reports.self` | Astronaut; own crew profile | Personal report |
| GET | `/staff/crew/{crewProfileId}/report` | `reports.assigned` | Medical officer; assigned crew only | Crew report |
| GET | `/exports/my-health-data.csv` | `exports.self` | Astronaut; own records | Personal export |
| GET | `/staff/crew/{crewProfileId}/health-data.csv` | `exports.assigned` | Medical officer; assigned crew only | Crew export |
| GET | `/research` | `research.index` | Authenticated | Research and limits |
| GET | `/chatbot/session` | `chatbot.session` | Active astronaut only | Assistant sync |
| GET | `/chatbot/snapshot` | `chatbot.snapshot` | Active astronaut; own records only | Assistant snapshot |
| POST | `/chatbot/messages/sync` | `chatbot.sync` | Active astronaut; own messages only | Assistant sync |

Future routes must have a named route, server-side authorisation, validation, and an owner/module entry here before frontend links are merged. Health-record queries must be scoped to the authenticated user or an explicitly authorised assignment; IDs in a URL never confer access.

Alert follow-up uses the sequence `open` → `acknowledged` → `under_review` → `closed`. Closing requires a note; each transition is retained in the follow-up timeline and audit log. Medical officers can review assigned crew only. Administrator access does not automatically grant health-record access.

## Shared-file ownership and review

Use these ownership areas until named team members are assigned in the task board.

| Area | Primary owner | Review required from |
| --- | --- | --- |
| Routes, middleware, shared layout, dependency manifests | Integration owner | Feature owner + integration owner |
| Migrations, models, seeders, database naming | Data/foundation owner | Integration owner |
| Check-ins, readings, imports | Data-collection owner | Data/foundation owner |
| Baselines, rules, alerts, follow-ups | Alert-engine owner | Research owner + integration owner |
| Dashboard, trends, crew views, reporting | Presentation owner | Authorisation owner |
| Research register, provenance, limitations | Research owner | Alert-engine owner |
| Shared styles and Blade components | Design-system owner | Integration owner |

The current GitHub project/task board must record the actual person assigned to each area, dependencies, and review status. Until then, no contributor should assume they are authorised to alter shared files without announcing it to the team.

## Change checklist

- [ ] The code uses the metric, source, status, date, and unit vocabulary above.
- [ ] All protected operations have server-side authorisation; hiding navigation is not access control.
- [ ] Migrations are additive and safe for an existing local database unless a coordinated reset is documented.
- [ ] Seed data is clearly synthetic and does not introduce protected or real health information.
- [ ] Routes, setup notes, and this contract have been updated when the public interface changes.
- [ ] The branch has passed appropriate tests/build checks and contains no `.env` or unrelated generated files.
