# OrbitCare

OrbitCare is a Laravel + MySQL prototype for astronauts to record health indicators, review trends, understand transparent experimental alerts, and document follow-up. It is **not** a medical device, diagnostic system, or source of treatment advice.

## Included

- Role-scoped astronaut, medical-officer, and administrator workspaces.
- Daily check-ins, manual readings, trends, provenance and freshness labels.
- Explainable experimental alerts with evidence snapshots and follow-up history.
- Assigned-crew review, printable summaries, and authorised CSV export.
- Fictional, repeatable local demo scenarios and a protected reset.

All demonstration records are synthetic. NASA sources provide indicator context only; they do not validate thresholds, individual conclusions, or diagnoses.

## Quick start

See [SETUP.md](SETUP.md) for local/XAMPP installation and [DEMO_SCRIPT.md](DEMO_SCRIPT.md) for the guided demo.

```bash
composer install
npm install
npm run build
php artisan key:generate
php artisan migrate --seed
php artisan test
```

## Documentation

- [Local setup](SETUP.md)
- [Demo script](DEMO_SCRIPT.md)
- [Deployment and rollback guide](docs/deployment.md)
- [Presentation release checklist](docs/release-checklist.md)
- [Research register and provenance](docs/research-register.md)
- [Team working agreement](docs/team-working-agreement.md)
- [Development phases](phases.md)

After seeding, all fictional accounts use `orbitcare-demo`: `astro1@orbitcare.test`, `astro2@orbitcare.test`, `astro3@orbitcare.test`, `medic@orbitcare.test`, and `admin@orbitcare.test`.

Never enable `ORBITCARE_DEMO_MODE` in production. It enables protected reset and scenario tools solely for an explicitly configured local demo database.
