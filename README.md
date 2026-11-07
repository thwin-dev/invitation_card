# Sale POS

Sale POS is a Laravel-based point-of-sale and store management application. Its primary interface is built with Blade and Livewire, with management routes grouped by business module.

## Features

- **Sales:** product search and barcode entry, cart checkout, customer payments and credit, receipts, and offline sale synchronization.
- **Products:** products, categories, subcategories, brands, product units and size/color variants, SKU and EAN-13 barcode generation, product images, retail/wholesale/cost prices, reorder levels, and bundles.
- **Inventory:** stock receiving and issuing, adjustments and physical counts, transfers between branches, damaged/expired/lost write-offs, stock movement history, low/out-of-stock filters, and batch/serial/expiry tracking.
- **Purchasing:** purchase receiving, supplier records, payments, purchase history, and CSV export.
- **Store operations:** customers and credit records, orders, expenses, financial reporting, and user management.

Existing stock and the current sales and purchasing workflows use the Main branch. Other branches have separate inventory balances; transfer stock to Main before selling it. Purchases are received immediately; partial purchase-order receiving is not currently supported. See [PRODUCT_INVENTORY_GUIDE.md](PRODUCT_INVENTORY_GUIDE.md) for workflow details and [PRODUCT_INVENTORY_CHECKLIST.md](PRODUCT_INVENTORY_CHECKLIST.md) for manual feature checks.

## Requirements

- PHP 8.3 or newer with the extensions required by Laravel and the installed Composer packages.
- Composer.
- Node.js and npm.
- A supported database. The example environment is configured for SQLite; MySQL settings are also shown in `.env.example`.

## Local setup

1. Install PHP and JavaScript dependencies:

   ```sh
   composer install
   npm ci
   ```

2. Create the environment file and application key:

   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

3. Configure `.env` for your database. For SQLite, create the file if it does not exist:

   ```sh
   touch database/database.sqlite
   ```

   Then set `DB_CONNECTION=sqlite`. For MySQL, set `DB_CONNECTION=mysql` and provide the host, port, database name, username, and password.

4. Run database migrations and seed demo records:

   ```sh
   php artisan migrate --seed
   ```

   The seeders add sample POS data and product/inventory examples. On a fresh local seed, the demo owner is `owner@example.com` with password `password123`; change or remove demo credentials before exposing the application to other users. To seed only the new product and inventory examples after migrations:

   ```sh
   php artisan db:seed --class=ProductInventoryDemoSeeder
   ```

5. Create the public storage link and build frontend assets:

   ```sh
   php artisan storage:link
   npm run build
   ```

6. Start the Laravel development server:

   ```sh
   php artisan serve
   ```

   Open the URL printed by Artisan and sign in to access the management screens. For frontend hot reload during development, run `npm run dev` in a separate terminal.

## Tests

Run the full suite:

```sh
php artisan test
```

Run the product and inventory feature tests only:

```sh
php artisan test --filter=ProductInventoryTest
```

## Project structure

- `app/Livewire/` contains interactive management tables and workflows.
- `app/Modules/` contains business module controllers, models, services, and support code.
- `resources/views/` contains Blade management and Livewire views.
- `routes/modules/` contains authenticated management routes grouped by module; they are loaded from `routes/web.php` inside the `management` route group.
- `database/migrations/` and `database/seeders/` define the schema and local/demo data.
- `POS_FEATURE_ROADMAP.md` records the longer-term product direction.

Management features should be implemented in the Blade/Livewire interface first. API work is deferred unless explicitly requested.

## Development helpers

The Composer `dev` script starts the Laravel server, queue listener, log viewer, and Vite development server together:

```sh
composer run dev
```

Use the [Laravel documentation](https://laravel.com/docs) for framework-specific configuration and development guidance.
