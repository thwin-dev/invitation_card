# POS Feature Roadmap

This document is the reference roadmap for the modern Sales POS system we want to build. Read this before planning or implementing POS modules so feature work stays aligned with the full product direction.

## 1. Sales Screen

- Search products by name, SKU, or barcode.
- Barcode scanner support.
- Add products to cart.
- Change quantity and price with permission.
- Apply item or whole-order discounts.
- Hold and resume an order.
- Print
- Process returns, refunds, and exchanges.

## 2. Product Management

- Products, categories, brands, and units.
- Product variants such as size and color.
- SKU and barcode generation.
- Cost and selling prices.
- Wholesale and retail prices.
- Stock alert quantity.
- Product images.
- Bundles or combo products.

## 3. Inventory Management

- Stock-in and stock-out.
- Stock adjustments.
- Purchase receiving.
- Stock transfers between branches.
- Damaged, expired, or lost stock recording.
- Real-time stock balance.
- Low-stock and out-of-stock alerts.
- Batch, serial number, and expiry-date tracking.
- Physical stock count.

## 4. Customer and Supplier Management

- Customer profiles and purchase history.
- Credit limits and outstanding balances.
- Loyalty points and membership levels.
- Supplier profiles and purchase history.
- Supplier payable balances.

## 5. Purchasing

- Purchase orders.
- Receive products partially or completely.
- Purchase returns.
- Purchase expenses.
- Supplier payments.
- Cost-price updates.
- Accounts payable.

## 6. Cash Register and Accounting

- Open and close cashier shifts.
- Opening cash balance.
- Cash-in and cash-out.
- Expected versus actual cash.
- Record shortages and overages.
- Daily income and expenses.
- Customer receivables.
- Supplier payables.
- Basic profit and loss calculation.

## 7. Reports

- Daily, weekly, and monthly sales.
- Sales by product, category, cashier, and branch.
- Gross profit.
- Payment-method breakdown.
- Inventory valuation.
- Low-stock products.
- Best-selling and slow-moving products.
- Purchase and supplier reports.
- Customer debt and supplier payable reports.
- Refund and discount reports.
- Export to Excel, CSV, or PDF.

## 8. Users and Security

- Admin, manager, cashier, and stock-controller roles.
- Permission-based access.
- Manager approval for discounts, refunds, and price changes.
- Activity and audit logs.
- Secure login and password reset.
- Automatic backup.

## 9. Multi-branch Features

- Multiple shops and warehouses.
- Branch-specific prices and stock.
- Stock transfers.
- Central dashboard.
- Branch-level reports.
- Different receipt and tax settings per branch.

## 10. Offline Mode

For Laravel and React POS, offline mode is highly useful:

- Continue selling when the internet is unavailable.
- Store products and pending sales locally.
- Print receipts offline.
- Synchronize sales when the connection returns.
- Prevent duplicate transactions.
- Display synchronization status and errors.

## Recommended Development Priority

### Phase 1: Essential POS

- Sales screen.
- Products.
- Categories.
- Stock.
- Customers.
- Cash/card payments.
- Receipts.
- Users.
- Permissions.
- Daily reports.

### Phase 2: Business Operations

- Purchasing.
- Suppliers.
- Expenses.
- Returns.
- Credit sales.
- Shift management.
- Stock transfers.
- Profit reports.

### Phase 3: Advanced

- Offline synchronization.
- Multi-branch management.
- Loyalty points.
- Advanced accounting.
- Dashboards.
- Integrations.

### Users and security delivery status

The management interface now has database-backed roles and module permissions. `config/permission_list.php` defines the available actions for each module; `RolePermissionSeeder` creates only actions marked `true` and removes permissions disabled or removed from the config. It creates Admin, Manager, Cashier, and Stock Controller roles and maps the existing Owner, Sales, and Store Keeper role names. Management routes and Livewire write actions check the matching configured permission. Import workflows are not present in the current modules, so import permissions are disabled until those workflows are implemented.

Activity and audit logs are available to employees with the dedicated permission. Authenticated changes across users, roles, products, categories, units, sales, orders, customers, purchases, suppliers, inventory, branches, and expenses record the actor, timestamp, route, subject, and changed field values. Sign-ins, sign-outs, password changes, role permission changes, and current CSV exports are also recorded. Passwords, tokens, and profile photo paths are excluded. The Blade audit screen supports module, action, employee, date, and text filters plus CSV export; Admin receives full access and new Manager roles receive view/export access.

Automatic backups are available from the Blade security screen. Daily jobs create AES-256 encrypted archives of the SQLite, MySQL, or MariaDB database and public uploaded files in private local storage. The screen supports manual backups, history, checksum-verified downloads, retention cleanup, and restores from a stored or uploaded archive. Restore verifies encryption, database checksums, required backup-history and migration tables, and the migration fingerprint; SQLite restores also verify database integrity and schema. Stored archives are checksum-verified before restore. Restore creates a pre-restore backup, enables maintenance mode while replacing data, records the restore in the audit log, and signs out the current session. MySQL and MariaDB restores use the pre-restore archive to roll the database back if an import fails. The dashboard shows the latest successful backup and next scheduled run to users with backup view permission. Admin receives restore and delete access; Manager can view, create, and download backups.

Backup settings are controlled by `config/backup.php` and the `BACKUP_*` environment variables. By default, backups run daily at 02:00 in the application timezone and are retained for 30 days. Set a stable `BACKUP_ENCRYPTION_KEY` to keep backups restorable if `APP_KEY` changes; if left empty, the app derives the archive password from `APP_KEY`. Keep this key private and available to the application during restores. The scheduler must be running (`php artisan schedule:work` locally or a once-per-minute `php artisan schedule:run` cron entry in production). SQLite needs a file-based database. MySQL and MariaDB need compatible `mysqldump` and `mysql` command-line clients configured with `MYSQLDUMP_BINARY` and `MYSQL_CLIENT_BINARY`. All drivers need PHP Zip with AES-256 support. Run `php artisan migrate` and `php artisan db:seed --class=RolePermissionSeeder` before opening the backup screen.

### Dashboard delivery status

The Blade operations dashboard is implemented with Livewire filters and reflects the current POS data model:

- Filter completed sales by shop and date range, and stock alerts by shop; use today, last 7 days, this month, or last month presets for the sales period.
- Review sales, transaction count, units sold, average sale, amounts paid on those sales, outstanding credit, customer repayments, purchases, and operating expenses.
- View a daily sales trend and shop-by-shop sales, collection, and credit totals.
- Track low and out-of-stock units, expiring and expired stock lots, customer receivables, supplier payables, and open orders.
- Review recent sales, purchases, expenses, stock movements, and orders, with shortcuts to common POS actions.
- See the browser's local offline sales queue and the number of offline sales already synchronized for the selected period.

Purchases, expenses, and supplier payables do not carry a shop identifier yet, so those amounts are shown across all shops. Pending offline sales are stored in the current browser's IndexedDB; the dashboard can show that browser's queue, while synchronized sales are counted from the server. Payment-method reporting, cashier shift summaries, refund reporting, and reliable gross profit remain future work until those workflows record the required data.

### Branch discount delivery status

Branch discount rules are managed under Sales > Discount rules in a Livewire table with create/edit modals, branch/search/status filters, and active toggles. Each rule can be assigned to one or more shops and uses the same discount value in every selected shop. Admin and Manager receive the new discount-management permissions when `RolePermissionSeeder` runs. Cashiers see the best eligible active discount applied automatically to each item at the selected shop; if multiple rules match, the highest discount amount wins, with the lowest rule ID breaking ties. Fixed discounts that exceed the line amount are skipped. Rules can apply to all units or one product unit and use a percentage (up to 100%) or a fixed amount per unit. Switching shops clears the cart; held sales revalidate rules on checkout.

The shared sale calculator rounds each line in currency minor units, rejects discounts above the line amount, and saves the gross subtotal, item discount totals, and discounted sale total. Payment and credit use the discounted total. Sale items retain the applied rule name/type/value and discount amount so rule changes do not change past receipts. Editing an existing line retains its saved rule rate when the same rule and product are kept; selecting a different rule validates it against the shop's active rules. Receipts, sale details, and sales CSV include discounts. Reports > Discounts filters completed discounted sales by date and branch and groups monetary summaries by branch and receipt currency. Held/cancelled sales are excluded.

The existing Main-shop offline catalog caches eligible discount rules. Offline receipts retain discount snapshots; synchronization recalculates on the server and rejects stale or ineligible rules, leaving affected sales in the pending queue for review. Refresh the offline catalog after changing rules. Order-level discounts, taxes, and manager approval overrides are separate future workflows.

Run `php artisan migrate` and `php artisan db:seed --class=RolePermissionSeeder` after updating. Existing sales are backfilled with their original total as subtotal and zero discount. No promotions are enabled automatically.

## Myanmar POS Notes

- Use MMK currency as the primary currency.
- Support cash-heavy payment workflows.
- Prioritize customer credit/debt tracking.
- Support Burmese receipt content.
- Record KBZPay, WavePay, AYA Pay, and other local mobile payment methods.
- Design workflows that tolerate unstable internet.
