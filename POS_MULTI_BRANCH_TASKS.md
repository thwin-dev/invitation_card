# POS multi-branch task checklist

Scope follows `POS_FEATURE_ROADMAP.md` Phase 3. Tax configuration is deferred per the user's instruction.

- [x] **Multiple shops and warehouses** — Manage locations at Inventory → Shops and warehouses. Each location has a type and active status. The existing Main location stays fixed because legacy stock quantities belong to it.
- [x] **Branch-specific prices and stock** — Active shops can override retail and wholesale prices; blank prices inherit the product unit defaults. Inventory balances are managed per shop or warehouse.
- [x] **Stock transfers** — Transfer stock between locations from Inventory → Inventory operations / transfers.
- [ ] **Central dashboard** — Deferred; another developer is handling this work.
- [x] **Branch-level reports** — Finance → Reports → Branches filters completed sales by shop and date range, with quantity, sales, collected, and credit totals. The old Branch reports URL redirects to this tab.
- [x] **Receipt settings per branch** — Configure branch phone, address, header, and footer. New sales retain a snapshot for printing. Tax settings are deferred.

Offline checkout remains Main-shop-only because its cached catalog and sync flow are Main-shop based.

## Local development demo data

Added with `php artisan tinker` to the local database:

- `POS Multi-branch Demo Shop` and `POS Multi-branch Demo Warehouse`, with sample receipt text on the shop.
- Arabica Coffee (Piece) has demo shop overrides of MMK 5,200 retail and MMK 4,800 wholesale.
- Received 20 units into the demo warehouse, transferred 5 to the demo shop, and recorded one completed demo sale (`MB-DEMO-001`) for MMK 5,200. Current demo balances are 15 in the warehouse and 4 in the shop; Main stock remains unchanged.

## Verification

- PHP and JavaScript syntax checks passed.
- Blade view compilation and branch route listing passed.
- The multi-branch migration was applied to the configured development database.
- Last full existing suite run before the dashboard was deferred: 68 passed, 2 failed. `test_counting_unit_can_be_created_updated_and_deleted_from_blade` and `test_stock_dashboard_updates_quantity_reorder_and_prices` expect direct quantity edits through unit/stock forms; the current controllers send those edits to Inventory operations. These two failures are outside the multi-branch changes.
- No dedicated tests were added for the new branch workflows; checks covered PHP/JavaScript syntax, Blade compilation, routes, and the existing suite.
