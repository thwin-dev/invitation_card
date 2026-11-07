# Old Sale POS To New Sale POS Migration Tasks

## Goal

Migrate all working features from the old Sale POS project into `new-sale-pos`.

Important rule: do not blindly copy old code as-is. Each feature must be adapted to the current `new-sale-pos` Laravel module style, architecture, naming, validation, resources, services, repositories, routes, middleware, permissions, and response patterns.

Current delivery direction: implement and verify Blade management screens first. API endpoints are deferred until the Blade POS workflows are working, unless explicitly requested.

## Project Context

- `new-sale-pos` currently starts from a course-selling/e-learning codebase.
- The current modules may be temporary/reference modules and can be removed later when POS migration is complete.
- Old Sale POS features should be migrated feature by feature.
- Keep the new project structure consistent and working after each migrated feature.
- Do not push code automatically.

## Phase 1: Prepare And Compare

- [x] Confirm the local path of the old Sale POS project: `/Users/air/Work/POS/old-sale-pos`.
- [ ] Compare Laravel/PHP versions between old and new projects.
- [ ] Compare `composer.json` dependencies.
- [ ] Compare `.env.example` requirements.
- [ ] Review old project routes.
- [ ] Review old project migrations and database tables.
- [ ] Review old project models and relationships.
- [ ] Review old project controllers, services, repositories, requests, and resources.
- [ ] Review old project frontend/admin UI files.
- [ ] Review old auth, guards, middleware, roles, and permissions.
- [x] Create an inventory of all old POS features before implementation.

### Compatibility Findings

- [x] Old POS uses PHP `^7.2`, Laravel `^6.2`, and Laravel Passport.
- [x] New project uses PHP `^8.3`, Laravel `^13.8`, and Laravel Sanctum.
- [ ] Do not copy old authentication, Passport configuration, or Laravel 6 controller patterns directly.
- [ ] Rewrite old behavior against the new Laravel 13 request, authentication, routing, resource, and service conventions.

### Confirmed Old POS Feature Inventory

- [ ] Authentication and user access.
- [ ] Admin dashboard and financial panel.
- [ ] Categories and subcategories.
- [ ] Items/products and item photos.
- [ ] Units, unit relations, and unit conversions.
- [ ] Customers, customer levels, and customer credit lists.
- [ ] Employees and admin management.
- [x] Map old employee access to the old POS `users` table; do not create a duplicate Admin identity module.
- [ ] Orders and order history.
- [x] Add Blade customer orders, order details, status filters, and delivery status workflow.
- [ ] Sales, sale history, vouchers, voucher details, and voucher summaries.
- [ ] Purchases and purchase history/details.
- [ ] Stock dashboard, stock counting, stock prices, and reorder management.
- [x] Add Blade stock dashboard with quantity, price, reorder, and stock-value views.
- [ ] Expenses.
- [ ] Localization and password change.
- [ ] Existing API login/customer endpoints.
- [ ] Existing reports and dashboard calculations embedded in old screens.

The old POS uses Laravel controllers and Blade views under `resources/views/{Admin,Inventory,Order,Purchase,Sale,Stock}`. These are reference sources only; rebuild them using the current module architecture instead of copying them as-is.

### Current Architecture Mapping

- [x] Remove the temporary Admin module and use old POS `users` authentication for Blade management.
- [ ] Extend `Payment` only where old sales payment behavior matches the current payment domain; do not merge unrelated payment concepts automatically.
- [ ] Evaluate `Student` as a possible customer foundation, but create a dedicated POS customer model if the business fields or workflows differ.
- [ ] Create POS modules for `Category`, `Product`, `Unit`, `Customer`, `Supplier`, `Purchase`, `Sale`, `Inventory`, `Expense`, and POS reports as required.
- [ ] Keep POS management routes under the current `v1/management` route and middleware conventions.
- [ ] Rebuild old Blade screens as the frontend style used by `new-sale-pos`; do not move old Blade templates directly into the new project.
- [ ] Preserve current API response, request validation, repository, service, resource, permission, and naming conventions.

## Phase 2: Migration Order

- [ ] Auth/admin compatibility: reuse the current Admin and Sanctum system.
- [x] Add Blade POS session login/logout using `user_code + email + password` and protect management routes with the `web` guard.
- [ ] Roles and permissions
- [ ] Settings
- [ ] Categories and subcategories
- [ ] Products/items
- [ ] Units, unit relations, and unit conversions
- [ ] Customers
- [x] Add Blade customer management screen with contact, level, and credit fields.
- [x] Confirm old POS has no standalone Supplier module; supplier is stored as `supplier_name` on purchases.
- [ ] Purchases
- [x] Add Blade purchase creation, history, details, and stock update workflow.
- [ ] Purchase returns if present
- [ ] Sales
- [x] Add Blade sales/voucher creation, history, details, and stock deduction workflow.
- [ ] Sale returns/refunds if present
- [x] Confirm old POS has no return/refund workflow.
- [x] Add Blade sale credit/payment fields and customer credit records.
- [ ] Payments beyond the old credit workflow
- [ ] Stock/inventory
- [ ] Expenses if present
- [x] Add Blade expense ledger with create, search, and totals.
- [ ] Reports
- [x] Add Blade financial report with date filters, sales, purchases, expenses, units sold, and net result.
- [x] Dashboard
- [x] Add Blade POS operations dashboard with sales, purchases, expenses, stock value, low stock, and recent sales.
- [x] Extend the dashboard with Livewire date/shop filters, sales summary and trend, branch comparison, stock/expiry alerts, customer receivables, supplier payables, activity, open orders, and offline sync status.
- [ ] User/admin management
- [ ] Any remaining old POS feature not listed above

### First Implementation Slice

- [ ] Define the POS product-domain schema without conflicting with course-selling tables.
- [x] Add Category module models, migrations, requests, resources, repository, service, controller, routes, and permissions.
- [x] Add Blade category management screen with search, create, update, and delete actions.
- [x] Add Subcategory support using the current module style.
- [x] Add Blade subcategory management screen with filtering, create, update, and delete actions.
- [x] Add Product/Item support using the old item fields and relationships as behavior reference.
- [x] Add Blade product/item management screen with filtering, visibility, create, update, and delete actions.
- [x] Add Blade product photo upload, replacement, and removal workflow.
- [x] Add Unit, unit relation, and unit conversion support.
- [x] Add Blade counting-unit management screen with quantities and POS prices.
- [x] Add Blade unit relation and stock conversion screens.
- [ ] Add focused feature tests for Blade CRUD flows, validation, relationships, permissions, and media fields.
- [ ] Add API endpoints and API tests later, after the Blade POS workflows are accepted.

## Phase 3: Per-Feature Migration Checklist

For each feature, follow this checklist:

- [ ] Study the old feature behavior and data model.
- [ ] Identify required tables, columns, indexes, and relationships.
- [ ] Check for conflicts with existing `new-sale-pos` tables or modules.
- [ ] Create or adapt migrations using current project conventions.
- [ ] Create the module under `app/Modules/{FeatureName}` when appropriate.
- [ ] Add or adapt Models.
- [ ] Add or adapt Requests for validation.
- [ ] Add or adapt Resources only when useful for shared backend/frontend data; API delivery is deferred.
- [ ] Add or adapt Repositories for query/data access logic.
- [ ] Add or adapt Services for business logic.
- [ ] Add or adapt Controllers.
- [ ] Add routes in the current route style.
- [ ] Add middleware and permissions in the current style.
- [ ] Add seeders/factories only when useful.
- [ ] Migrate Blade frontend/admin UI as part of each feature once backend behavior is stable.
- [ ] Test create, read, update, delete, and important business flows.
- [ ] Fix style/architecture issues before moving to the next feature.

## Phase 4: Database Migration Rules

- [ ] Do not overwrite existing migrations without checking conflicts.
- [ ] Carefully handle shared tables such as `users`, `admins`, `roles`, `permissions`, `payments`, and `settings`.
- [ ] Prefer adapting old schema into the current project style.
- [ ] Preserve important old POS data relationships.
- [ ] Keep migrations runnable from a fresh database.
- [ ] Avoid keeping duplicate tables for the same concept unless temporarily required during migration.

## Phase 5: Blade Frontend/Admin UI Migration

- [ ] Identify the old POS frontend stack.
- [ ] Identify the current `new-sale-pos` frontend/admin style.
- [ ] Rebuild or adapt screens using the current UI structure.
- [ ] Add sidebar/menu entries in the current pattern.
- [ ] Connect screens directly to the migrated Blade controllers/services first.
- [ ] Defer connecting screens to API routes until the Blade workflows are accepted.
- [ ] Check permissions and hidden/visible menu behavior.
- [ ] Test forms, tables, filters, search, pagination, and exports.

## Phase 6: Testing And Verification

- [x] Run backend and Blade feature tests; `php artisan test` passes 155 tests with 767 assertions.
- [ ] Run migrations from a fresh database.
- [x] Generate all migration SQL with `php artisan migrate --pretend`; ordering and foreign-key dependencies compile without applying changes.
- [x] Test Blade routes with feature tests; admin sign-in/logout and invalid/inactive-admin rejection, dashboard totals/stock value/low-stock display, stock quantity/reorder/price updates, financial report filtering/calculation, auth protection, category creation/delete protection/search, subcategory CRUD/delete protection/filtering, customer create/update/filtering, product create/update/delete/filtering, product photo upload/removal, counting-unit create/update/delete/filtering, sale stock deduction, sale history display, insufficient-stock rejection, credit-permission rejection, customer credit recording, purchase stock increase, purchase history display, purchase price updates, order creation, delivery status updates, order status filtering, expense ledger recording/search, and successful/insufficient-stock unit conversion pass.
- [x] Separate API route names under `api.management.*` so Blade route helpers resolve to the web workflows.
- [ ] Test API routes later when API delivery begins.
- [ ] Verify permission-protected routes.
- [ ] Verify stock calculations.
- [ ] Verify sale totals, discounts, taxes, payments, and returns.
- [ ] Verify reports match old POS expectations.
- [ ] Verify dashboard numbers.
- [ ] Check logs for runtime errors.

## Phase 7: Cleanup Current Course-Selling Modules

Only clean up after POS features are working.

- [x] Identify unused course-selling modules; keep `Course` as the single reference module.
- [x] Remove unused course/client API routes; API delivery remains deferred.
- [x] Remove unused course-selling and Admin migrations; retain base, Course reference, and POS migrations.
- [x] Remove unused course-selling models, controllers, services, repositories, requests, and resources except `Course`.
- [x] Remove unused permissions, permission middleware, configuration, and packages.
- [x] Remove unused frontend screens and menu items; POS Blade screens remain active.
- [ ] Remove unused config/settings.
- [ ] Update README and setup notes.

## Phase 8: Old POS Feature Parity Audit

- [x] Compare old POS routes, controllers, and Blade screens with the current POS modules.
- [x] Map the old dashboard, inventory, stock, sales, order, purchase, expense, and finance entry points.
- [x] Add current-style legacy route aliases for existing POS screens and write actions.
- [ ] Add customer detail, customer level, credit, and customer lookup workflows.
- [ ] Add employee/user list and employee detail workflows using the old POS `users` table.
- [ ] Add password change workflow for the signed-in POS user.
- [ ] Add sale search, voucher summary, voucher details, credit details, and item/unit lookup workflows.
- [ ] Add order history search and voucher detail workflows.
- [ ] Add stock count, stock price, reorder, and unit conversion screens matching old POS behavior.
- [ ] Add purchase details, financial filters, and report search workflows.
- [ ] Verify every migrated screen with Blade feature tests and browser checks.

## Final Definition Of Done

- [ ] All required old Sale POS features work inside `new-sale-pos`.
- [ ] Code follows the current `new-sale-pos` architecture and style.
- [ ] Fresh install/migration works.
- [ ] Main POS workflows are tested.
- [ ] Old course-selling modules are removed or intentionally kept.
- [ ] Documentation is updated.
- [ ] No code is pushed automatically.
