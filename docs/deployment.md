# Deployment and rollback guide

This prepares a presentation release; it does not replace host-specific security, backup, or privacy requirements.

## Before deployment

1. Confirm PHP 8.2+, Composer, MySQL, HTTPS, and the extensions listed in [SETUP.md](../SETUP.md).
2. Create a dedicated MySQL database and restricted application user; never use root.
3. Release the source plus `composer.lock` and `package-lock.json`; never copy a local `.env`.
4. Back up before every migration:

   ```bash
   mysqldump --single-transaction --routines --triggers -u orbitcare_app -p orbitcare_production > orbitcare-before-release.sql
   ```

## Production configuration

Create server `.env` from `.env.example` with unique values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.org
LOG_LEVEL=warning
DB_DATABASE=orbitcare_production
DB_USERNAME=orbitcare_app
DB_PASSWORD=use-a-unique-secret
SESSION_SECURE_COOKIE=true
ORBITCARE_DEMO_MODE=false
```

Generate a unique `APP_KEY` on the server. Serve Laravel from `public` only; do not expose `.env`, `storage`, or `vendor`.

## Release sequence

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan key:generate --force  # new installation only
php artisan migrate --force
php artisan optimize
```

Set writable ownership for `storage` and `bootstrap/cache` according to the web-server account. Configure HTTPS, then verify HTTP redirects to HTTPS. Never run `orbitcare:demo-reset` in production.

## Post-release checks

- Verify `/health`, HTTPS, login/logout, check-ins, readings, alerts, follow-ups, reports, exports, and role boundaries.
- Confirm desktop/phone layout and locally compiled assets work without Vite.
- Confirm `APP_DEBUG=false`, `ORBITCARE_DEMO_MODE=false`, and unique administrator credentials.
- Record the release commit and backup filename.

## Rollback

1. Enable maintenance mode: `php artisan down --render="errors::503"`.
2. Restore the prior application release and compiled assets.
3. If data rollback is necessary, restore the verified backup rather than guessing at destructive migration downgrades:

   ```bash
   mysql -u orbitcare_app -p orbitcare_production < orbitcare-before-release.sql
   ```

4. Run `php artisan optimize:clear`, verify health/login, then `php artisan up`.

For a local presentation fallback, use the prepared XAMPP copy, compiled assets, and a separate MySQL demo database. Test it from another device on the local network first.
