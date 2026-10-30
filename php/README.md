# Palmar — PHP edition

Art placement tracker. Plain PHP 8, no framework.

## Files
- `index.php` — router + pages + API
- `config.php` — DB connection, constants, schema bootstrap
- `store.php` — placement CRUD
- `catalog.php` — fetches the live shop catalog from imperionism.com
- `uploads.php` — file upload helpers
- `schema.sql` — MySQL DDL for production
- `dev_router.php` — router for `php -S` local testing
- `.htaccess` — Apache rewrite rules for clean URLs

## Database
Development default: SQLite (`palmar.db`, auto-created on first run).
Production: MySQL/MariaDB — apply `schema.sql`, then point `DB_DSN` in `config.php`
at your MySQL DSN (e.g. `mysql:host=localhost;dbname=palmar`).

## Local run
```
php -S localhost:8000 dev_router.php
```

## Production (Apache)
Point the vhost at this directory; `.htaccess` handles routing.
Set `upload_max_filesize` / `post_max_size` >= 25M.
