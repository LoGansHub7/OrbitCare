# OrbitCare local demo script

1. Set `ORBITCARE_DEMO_MODE=true` and `ORBITCARE_DEMO_DATABASE=orbitcare` in the local `.env` file. Do not enable this in production.
2. Run `php artisan orbitcare:demo-reset`. It refuses to run unless demo mode is enabled and the active database exactly matches the configured demo database.
3. Sign in as `admin@orbitcare.test` with password `orbitcare-demo`, then open **Admin → Demo mission control**.
4. Run a scenario and follow the crew-record link. The stable, trend, stale, exercise, radiation, and Earth-link cases are all fictional simulations.
5. Inspect source labels, evidence snapshots, rule versions, the printable report, and the CSV export. Explain that alerts prompt review and are not diagnoses.

The seed date is relative to the day of reset. Use `ORBITCARE_DEMO_ANCHOR_DATE` only as a documented future extension; it currently does not alter records.
