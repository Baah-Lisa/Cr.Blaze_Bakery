# Cr. Blaze Bakery

A PHP/MySQL bakery ordering application with customer checkout and a protected administrator area.

## Features

- Product browsing with stock-aware cart controls
- CSRF-protected cart and checkout actions
- Cash on Delivery orders
- Optional server-side Paystack Mobile Money initialization and verification
- Order confirmation and phone-protected order tracking
- Administrator login, product management, stock management, and order status updates
- Secure session cookies, prepared SQL statements, upload validation, and protected configuration files
- Railway deployment support through the included Dockerfile

## Local setup

1. Create a MySQL database named `blaze_bakery`.
2. Import the existing schema.
3. Run `database_migration_stage3.sql`, `database_migration_stage4.sql`, and `database_migration_stage5.sql` once.
4. Configure the `BLAZE_*` values from `.env.example` in the PHP/Apache environment.
5. Open `admin/create_admin.php` locally to create the first administrator.

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for Railway deployment, database migration, HTTPS, backups, persistent image storage, and first-administrator setup instructions.

Do not commit `.env` files, database passwords, payment keys, administrator passwords, or production database exports.

## Payments

Cash on Delivery works without a payment provider. Mobile Money remains pending until a configured provider verifies the payment server-side. See [PAYMENTS.md](PAYMENTS.md).
