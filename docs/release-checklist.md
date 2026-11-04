# Presentation release checklist

## Before freezing

- [ ] All intended changes are committed and pushed; record the commit hash.
- [ ] `composer install`, `npm ci`, `npm run build`, and `php artisan test` pass on a clean checkout.
- [ ] Research/provenance and limitations pages are current.
- [ ] Demo scenarios were reset and each expected outcome was checked.
- [ ] A current database backup exists and its restore command was reviewed.

## Environment

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, and a unique `APP_KEY` are set.
- [ ] HTTPS works and the web root points only to `public`.
- [ ] `ORBITCARE_DEMO_MODE=false` on production.
- [ ] Database uses a restricted account; `.env` and backups are not public.
- [ ] Administrator credentials are unique and not the demo default.
- [ ] Login, check-in, reading, alert/follow-up, report/PDF, CSV export, and sign-out were tested by role.
- [ ] Astronaut-to-astronaut and unassigned-medical-officer access attempts were denied.

## Local fallback

- [ ] XAMPP, MySQL, source, `vendor`, `node_modules`, and compiled `public/build` are available locally.
- [ ] The fallback database is separate from presentation/production data.
- [ ] Demo mode is enabled only for that fallback database.
- [ ] The application was opened from another device on the local network.
