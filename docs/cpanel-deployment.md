# cPanel deployment for `zasn.it.com`

The domain’s current document root is `/home/CPANEL_USER/public_html/zasn`. Laravel must not be installed directly there: only its public files should be web-accessible.

This release provides two upload files in `dist/`:

- `orbitcare-app-*.zip` — upload and extract to `/home/CPANEL_USER/orbitcare_app`.
- `orbitcare-public-*.zip` — upload and extract to `/home/CPANEL_USER/public_html/zasn` after backing up the old site.

The public archive contains a cPanel-specific `index.php` which loads the app from `/home/CPANEL_USER/orbitcare_app` without exposing the rest of Laravel.

## Safe replacement procedure

1. In cPanel **File Manager**, open `/home/CPANEL_USER/public_html/zasn`.
2. Select its current contents and use **Compress** to create `zasn-before-orbitcare.zip` in `/home/CPANEL_USER`. Download this backup. Do not delete it yet.
3. In `/home/CPANEL_USER`, upload `orbitcare-app-*.zip`, extract it, and rename the extracted `OrbitCare` folder to `orbitcare_app` if necessary.
4. Create a MySQL database, restricted database user, and grant that user only this database through **MySQL Database Wizard**. Record the cPanel-prefixed names, e.g. `account_orbitcare`.
5. In `/home/CPANEL_USER/orbitcare_app`, copy `.env.example` to `.env` and set production values. Do not upload your local `.env`.
6. Confirm the app installs before replacing the live document root by running the SSH commands below.
7. Only then remove the old files inside `/public_html/zasn` (not the folder itself), upload `orbitcare-public-*.zip`, and extract it there.
8. In cPanel **Domains**, enable Force HTTPS Redirect once a valid SSL certificate is active.

## `.env` values

```dotenv
APP_NAME=OrbitCare
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://zasn.it.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CPANELUSER_orbitcare
DB_USERNAME=CPANELUSER_orbitcare
DB_PASSWORD=use-a-long-unique-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync

ORBITCARE_DEMO_MODE=false
ORBITCARE_DEMO_DATABASE=orbitcare
```

Do not use demo credentials in production. Set `ORBITCARE_DEMO_MODE=false`; its reset function must never be enabled on the live site.

## SSH commands

Replace `CPANELUSER` with the actual cPanel username. Run these from `/home/CPANELUSER/orbitcare_app` after `.env` exists:

```bash
cd ~/orbitcare_app
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If Composer is available on the host, run this before Artisan commands to ensure the server matches the lock file:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

The compiled frontend assets are already in both archives; no Node/npm access is required on cPanel.

## Required permissions

Give the hosting account write access to these directories:

```bash
chmod -R ug+rwX ~/orbitcare_app/storage ~/orbitcare_app/bootstrap/cache
```

## First-production checks

1. Visit `https://zasn.it.com/health`; it should return an `ok` JSON response.
2. Visit `https://zasn.it.com/login` and test an administrator account with a unique password.
3. Confirm a check-in, report, CSV export, logout, and a denied cross-role URL request.
4. Confirm debug output is never displayed and `ORBITCARE_DEMO_MODE` remains false.

## Emergency rollback

If the new site fails, remove the new contents of `/public_html/zasn`, extract `zasn-before-orbitcare.zip` back into that folder, and restore the prior database backup if you changed its database. Keep the OrbitCare application folder until rollback is no longer needed.
