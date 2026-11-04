# Local setup (XAMPP / MySQL)

## Requirements

- PHP 8.2+, Composer, Node 18+, and MySQL/MariaDB running through XAMPP.
- Laravel PHP extensions including `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, and `zip`.

## Install

1. Clone into `C:\xampp\htdocs\OrbitCare` and create a database named `orbitcare`.
2. Copy `.env.example` to `.env`; set database credentials and this XAMPP URL:

   ```dotenv
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost/OrbitCare/public
   DB_CONNECTION=mysql
   DB_DATABASE=orbitcare
   DB_USERNAME=root
   DB_PASSWORD=
   ORBITCARE_DEMO_MODE=true
   ORBITCARE_DEMO_DATABASE=orbitcare
   ```

3. Install and build:

   ```bash
   composer install
   npm install
   npm run build
   php artisan key:generate
   php artisan migrate --seed
   ```

4. Open `http://localhost/OrbitCare/public`.

## Tests and demo data

```bash
php artisan test
php artisan orbitcare:demo-reset
```

The reset command only runs outside production, when demo mode is enabled, and when the active database exactly matches `ORBITCARE_DEMO_DATABASE`. It rebuilds that database with fictional records, so do not aim it at data you need to preserve.

All demo accounts use `orbitcare-demo`. If older local data lacks those accounts, check the database name and run the reset command.

## Troubleshooting

- Run `php artisan optimize:clear` after editing `.env`.
- Run `npm run build` and hard-refresh (`Ctrl+F5`) after asset changes.
- Stop Vite and remove a stale `public/hot` file if XAMPP looks for a development server.
- Confirm Apache and MySQL are running in XAMPP.
