# OrbitCare

**Astronaut health monitoring and self-care support for long-duration space missions.**
Built for the NASA Space Apps Challenge 2026: *Create Health Monitoring Software for Astronauts on Space Missions*.

> OrbitCare is decision support, not diagnosis. It supports the mission medical team and does not replace it. All data in this prototype is simulated.

## The problem

On missions far from Earth, messages to doctors can be delayed by many minutes or lost entirely, crews are small, and health signals (sleep, heart rate, fatigue, exercise, mood, radiation, cabin air) are scattered. OrbitCare brings them together, explains what they mean, and tells the crew member what to do next and when to escalate, even offline.

## Features

- **Personal dashboard:** resting heart rate, sleep, fatigue and exercise with baseline comparison, 7/14-day views, and a body-system readiness map.
- **Five Hazards framework:** radiation, isolation and confinement, distance from Earth, gravity fields, and hostile/closed environments, each with its own monitoring page.
- **Rules-based alerts:** transparent rules with four outcomes (no flag, review, priority review, insufficient data), explained on the *How alerts work* page.
- **Daily check-in and readings:** quick entry with source labels (manual, CSV import, simulation).
- **ORBIT assistant:** rule-based, works offline, chats stored on the device and synced when the link returns.
- **Body Map:** clickable 2D human figure with status per region, vitals and sparklines, linked hazards, self-care steps and escalation guidance.
- **Reports and CSV export** scoped by role.
- **Research and limits, risk register, evidence library and glossary** tracing the system back to NASA and CSA sources.
- **Day/Night theme** and an Earth link status indicator.

## Roles

| Role | Access |
|---|---|
| Astronaut | Own records, reports and exports only |
| Medical officer | Reports and exports for assigned crew members only |
| Administrator | Manages accounts and assignments; no automatic access to health records |

## Tech stack

- **Back end:** Laravel 12 (PHP), Eloquent, service classes (`AlertEvaluationService`, `BodyMapService`)
- **Database:** MySQL
- **Front end:** Blade, Bootstrap 5, Bootstrap Icons, vanilla JavaScript, Chart.js
- **Body Map:** inline SVG with CSS animation (works offline, no model files)
- **Testing:** Laravel test framework (56 tests, 229 assertions at the last run)
- **Local environment:** XAMPP (Apache + MySQL)

## Getting started

Requirements: PHP 8.2+, Composer, MySQL.

```bash
git clone <your-repository-url> OrbitCare
cd OrbitCare
composer install
cp .env.example .env
php artisan key:generate
```

Create an empty MySQL database (for example `orbitcare`), set `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`, then:

```bash
php artisan migrate --seed
php artisan serve
```

Under XAMPP, place the project in `htdocs` and open `http://localhost/OrbitCare/public`. If your layout uses Vite assets, also run `npm install && npm run build`. Demo accounts are created by the seeders (see `database/seeders`).

Run the tests:

```bash
php artisan test
```

## How status works

`AlertEvaluationService` is the single source of truth for thresholds and trends. Other pages, including the Body Map, only translate its output:

| Rules engine | Displayed as |
|---|---|
| no flag | Nominal |
| insufficient data | Monitoring |
| review | Review |
| priority review | Priority review |

## Project structure (key parts)

```
app/Services/AlertEvaluationService.php   rules engine
app/Services/BodyMapService.php           Body Map view model
app/Http/Controllers/BodyMapController.php
app/Models/BodyRegion*.php                regions, metrics, hazards, guidance, incidents
resources/views/body-map/                 Blade views and SVG figure
public/js/bodymap.js                      interaction and panel logic
public/css/bodymap.css                    figure styles and animation
```

## Limitations

- Simulated data only; no real astronaut or clinical data.
- Thresholds and guidance are team assumptions and need clinical review.
- The assistant is rule-based and covers a fixed set of topics.
- Not hardened for real deployment (encryption, audit logging, compliance).

## Roadmap

Mission-day slider, symptom-guided flow, scenario demos (for example a solar particle event), real sensor integration, flight-surgeon review of rules, full admin workspace, and offline app packaging.

## Credits

Team: add names here. Sources: NASA Human Research Program (Five Hazards of Human Spaceflight, Risks of Human Spaceflight) and Canadian Space Agency radiation monitoring material.

## License

Add your license here.
