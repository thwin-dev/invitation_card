# Product and inventory workflows

## Catalog

- Products: create/edit products and upload images in a Livewire modal. Choose a category, its subcategory, and an optional brand.
- Brands: create, rename, and activate/deactivate brands.
- Units / variants: give each sellable variant a unit name, size, color, SKU, barcode, cost, retail/wholesale prices, and reorder threshold. Each variant has its own stock balance.
- Generate SKU and barcode: generates a unique SKU and a checksum-valid EAN-13 number for internal use. Barcode opens a printable label. Supplier codes remain supported for scanning; only valid EAN-13 codes have a printable EAN-13 label.
- Bundles: add component units and quantities in the unit editor. In Inventory operations, use Assemble bundle to consume components and add finished combo stock atomically. Sell finished bundles through the existing sales screen. Nested bundles are rejected.

## Inventory

Existing stock belongs to Main. Existing sales, orders, purchases, prices, and stock reports continue to use Main. Other branches hold separate stock; transfer stock to Main before selling it. The branch inventory screen shows each branch's balance and refreshes every 15 seconds, with immediate updates after its own actions.

Create branches under Inventory → Branches. Under Inventory operations, select a branch and use Stock action:

- Stock in/out: receive or issue a quantity with a required reason.
- Adjust balance / Physical stock count: enter the new total, including zero. The saved operation includes the previous count and variance. If the balance changes while the form is open, reopen and recount. Counts with no variance are also recorded.
- Branch transfer: remove stock from the source and add it to the destination in one transaction. Batch, serial, and expiry details travel with the stock.
- Damaged / expired / lost: write off a quantity and record the reason.
- Batch tracking: requires a batch number for receipts; expiry is optional.
- Serial tracking: receive one item per operation with a unique serial. A transfer preserves its identity. Serial numbers cannot be registered twice.

Stock-out selects the earliest non-expired tracked stock first, then legacy untracked Main stock. Explicit batch/serial selection is available in Inventory operations. Expired stock cannot be sold or used as bundle components; it can be transferred, counted, or written off. Existing stock has no invented batch or serial metadata; tracking applies to new receipts.

Use the alert filter for low stock, zero stock, expired batches, or batches expiring within 30 days. Stock movements show the user, branch, batch/serial, reason, and balances. Recent operations show physical counts and their variances.

## Purchase receiving

Purchases are received immediately as in the existing workflow. Open each cart line's Batch, serial and expiry section before saving tracked goods. Serialized receipts have quantity one. Purchase edits preserve entered tracking data; a tracked purchase cannot be reversed after its stock has been consumed or transferred until that stock is restored. Partial purchase-order receiving remains outside this feature scope.

## Local setup

Use PHP 8.3 or newer (the project dependencies require it):

```sh
php artisan migrate
php artisan storage:link
npm run build -- --configLoader runner
php artisan test
```

The runner option avoids the existing root-owned Vite temporary directory. Do not use another project's Vite server for this application. Built assets require no development server.
