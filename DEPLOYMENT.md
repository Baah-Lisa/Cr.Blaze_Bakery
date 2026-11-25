# Cr. Blaze Bakery deployment guide

## 1. Configure the environment

Copy the values from `.env.example` into your hosting provider's server environment. Do not upload a real `.env` file into the public website folder.

Required values:

- `BLAZE_DB_HOST`
- `BLAZE_DB_USER`
- `BLAZE_DB_PASSWORD`
- `BLAZE_DB_NAME`
- `BLAZE_DB_PORT` (usually `3306` for MySQL)
- `BLAZE_DELIVERY_COMMUNITY`
- `BLAZE_ADMIN_SETUP_TOKEN` (temporary first-admin setup only; remove immediately after use)

The application reads these values in `db.php`.

## Railway deployment

Railway can deploy this project using the included `Dockerfile`. Create a Railway web service from the repository; Railway detects the root `Dockerfile` automatically. Add a MySQL service to the same Railway project and connect its generated variables to the web service.

Set these web-service variables in Railway:

- `BLAZE_DB_HOST` = the MySQL service host
- `BLAZE_DB_USER` = the MySQL service user
- `BLAZE_DB_PASSWORD` = the MySQL service password
- `BLAZE_DB_NAME` = the MySQL database name
- `BLAZE_DB_PORT` = the MySQL service port, usually `3306`
- `BLAZE_DELIVERY_COMMUNITY` = the real delivery community
- `BLAZE_PUBLIC_URL` = the deployed HTTPS Railway domain

Set the Railway healthcheck path to `/health.php`. The Docker entrypoint maps Apache to Railway's injected `PORT` value.

Attach a persistent Railway volume mounted at `/var/www/html/images` because administrator-uploaded product images must survive redeployments. Keep database backups enabled for the MySQL service.

After the first deployment, import the schema and run migrations stage 3, 4, and 5 against the Railway MySQL service. Do not expose phpMyAdmin publicly.

## 2. Import the database

1. Create the `blaze_bakery` database.
2. Import the existing database export.
3. Run `database_migration_stage3.sql` once.
4. Run `database_migration_stage4.sql` once.
5. Run `database_migration_stage5.sql` once.
6. Confirm that `orders` contains the payment and reference columns.
7. Confirm that the `admin_users` table exists.

Back up the database before every migration. The migrations do not drop existing tables.

## 3. Create the first administrator

On local XAMPP, open:

`http://localhost/Cr.blaze_bakery/admin/create_admin.php`

Use a unique password of at least 12 characters. The setup page disables itself after the first account is created.

For production, set a long random `BLAZE_ADMIN_SETUP_TOKEN` server environment value temporarily, open `/admin/create_admin.php` over HTTPS, enter the token and create the first account, then remove the environment value immediately. The setup page is unavailable again after the first account exists.

## 4. Configure delivery

Set `BLAZE_DELIVERY_COMMUNITY` to the exact community where delivery is offered. Do not leave the example value in production.

## 5. Configure payment

Mobile Money is not considered paid when an order form is submitted. Configure a legitimate provider such as Paystack or Hubtel using server-side secret environment variables, then implement server-side verification or signed webhooks before setting `payment_status` to `paid`.

Never request or store a customer's MoMo PIN.

## 6. Apache and HTTPS

- Keep `.htaccess` enabled.
- Use HTTPS in production.
- Set secure cookies once HTTPS is active.
- Keep SQL files, environment files, configuration files, and `includes/` outside public access.
- Disable directory listing.

## 7. Backups

Back up:

- The `blaze_bakery` database.
- Product images in `images/`.
- Application files and environment configuration stored outside the public directory.

Test restoring a backup before relying on it.

## 8. Final checks

Before launch, test:

- Product browsing and cart changes.
- Checkout validation and stock handling.
- COD order creation.
- Pending MoMo orders.
- Order tracking with the correct phone number.
- Admin login, logout, product management, and order statuses.
- HTTPS, backups, CSRF protection, and invalid upload rejection.
