# Product and inventory feature check guide

Use this guide to manually check the product and inventory features in the Laravel management screens. Work in a local or test database, not a live store database. The steps that receive, issue, transfer, or assemble stock change inventory balances.

## Before you start

1. Use PHP 8.4 or newer and install the project dependencies.
2. Run migrations and seed demo records:

   ```sh
   /opt/homebrew/opt/php@8.4/bin/php artisan migrate --seed
   ```

   The demo inventory seeder adds **Demo Cotton T-shirt**, **Demo Polo Shirt**, two brands, a North Warehouse branch, size/color variants, and a three-shirt bundle. It can also be run separately:

   ```sh
   /opt/homebrew/opt/php@8.4/bin/php artisan db:seed --class=ProductInventoryDemoSeeder
   ```

3. Sign in and open the **Inventory** section in the management sidebar. If the demo products are not visible, confirm the migrations completed and the seeder ran without errors.

## Product management checks

### Products, categories, brands, and units

**Where:** Management → Inventory → Products, Categories, Brands, and Units.

1. Create a category and a subcategory beneath it.
2. Create a brand, then create a product using that category, subcategory, and brand.
3. Try selecting a subcategory from another category. The product should fail validation.
4. Search for the new product and filter the list by category, subcategory, and brand.
5. Rename the brand and toggle its active status. Confirm the list updates and an inactive brand is unavailable when creating a product.
6. Open the product’s Units / variants page. Add two units with different sizes and colors, then confirm each appears as its own row.

**Pass when:** records save, validation prevents mismatched category/subcategory pairs, filters return the expected records, and variants remain separate.

### SKU, barcode, images, and prices

**Where:** Management → Inventory → Products and Units / variants.

1. Create a product unit and use the generated SKU and barcode. Save it, then create another unit and confirm the generated identifiers are different.
2. Open the unit’s barcode page and print the label. Scan it with a scanner or scanner keyboard input; confirm the matching unit is found. EAN-13 labels require a valid 13-digit barcode.
3. Set purchase cost, retail price, wholesale price, order price, and reorder quantity. Save and reopen the unit to confirm the values persist.
4. Upload a product image, then edit the product to replace it. Confirm the latest image appears in the product list. Try an unsupported file or a file over the size limit and confirm validation rejects it.

**Pass when:** codes are unique, a valid barcode resolves to the right unit, prices persist, the image can be replaced, and invalid uploads are rejected.

### Bundles / combo products

**Where:** Units / variants to configure the bundle; Management → Inventory → Inventory operations to assemble it.

1. Edit the demo **Three T-shirt bundle** unit and confirm its recipe includes two Medium Navy shirts and one Large Navy shirt.
2. In Inventory operations, choose Main, find the bundle, and record an **Assemble bundle** operation for one bundle with a reason.
3. Check the Main balances. Medium Navy should decrease by two, Large Navy by one, and Three T-shirt bundle should increase by one.
4. Try assembling more bundles than available components permit. The operation should fail and none of the balances should change.
5. Try configuring a unit as its own component or create a nested bundle recipe. Validation should reject it.

**Pass when:** valid assembly changes all component and bundle balances atomically; invalid assembly changes none.

## Inventory management checks

### Stock in, stock out, and stock adjustments

**Where:** Management → Inventory → Inventory operations. Check results in Stock movements.

1. Select Main and choose a product unit with stock. Record stock in with a quantity and reason. Confirm its balance increases.
2. Record stock out with a smaller quantity and reason. Confirm its balance decreases.
3. Try to issue more than the available balance. Confirm the operation is rejected and the balance is unchanged.
4. Use Adjust balance or Physical stock count to enter a new total. Try a lower count, a higher count, and zero. Confirm each balance equals the entered total.
5. Reopen a count form, change the stock elsewhere, then submit the old count. Confirm the stale count is rejected and asks you to reopen/recount.
6. Submit a count equal to the current balance. Confirm the operation is recorded even though variance is zero.

**Pass when:** balances match the operations, reasons appear in history, insufficient stock is rejected, and stale counts cannot overwrite newer stock.

### Branches and transfers

**Where:** Management → Inventory → Branches and Inventory operations.

1. Confirm Main and North Warehouse are present. Create another branch and confirm it appears in the branch selector.
2. In Inventory operations, select Main and transfer a quantity of a stocked unit to North Warehouse.
3. Select each branch in turn. Confirm the source decreased by the transferred quantity and the destination increased by the same quantity.
4. Transfer the stock back to Main and confirm the balances reverse.
5. Try a transfer with the same source and destination, or more than the source balance. Confirm validation prevents it.

**Pass when:** transfers preserve total inventory, show movements for both branches, and cannot overdraw stock. Sales currently use Main; transfer stock to Main before checking sale behavior.

### Damaged, expired, and lost stock

**Where:** Inventory operations; use the branch and lot filters to inspect the result.

1. Record damaged, expired, and lost operations for available stock, each with a reason.
2. Confirm each operation reduces the selected branch’s balance and appears in Stock movements with the matching operation type and reason.
3. Try writing off more stock than is available. Confirm the operation fails without changing stock.

**Pass when:** each write-off is recorded under the correct type and leaves the expected remaining balance.

### Batch, serial number, and expiry-date tracking

**Where:** Set the unit’s tracking mode under Units / variants. Receive or issue tracked stock in Inventory operations. Purchases are available under Management → Purchasing → New Purchase.

1. Set a unit to **Batch** tracking. Receive stock with a batch number and expiry date. Confirm the lot appears in the selected branch’s lot list with the entered details.
2. Try a batch receipt without a batch number. Confirm it is rejected.
3. Set a unit to **Serial** tracking. Receive one item with a serial number. Confirm a second receipt using the same serial is rejected. Receive a second item with a different serial and confirm both are listed separately.
4. Try receiving quantity greater than one for a serial-tracked unit. Confirm it is rejected; serialized items must be received one at a time.
5. Transfer a batch or serial-tracked item. Confirm its batch/expiry information or serial identity is present at the destination.
6. Create a batch with an expiry date in the past. Confirm it appears under the expired filter and cannot be used for normal stock out. Use an expired write-off to remove it.
7. Create a batch expiring within 30 days and confirm it appears under the expiring filter.

**Pass when:** required tracking data is enforced, serials are unique, tracking details survive transfers, and expired stock is excluded from normal issue operations.

### Live balances, alerts, and movement history

**Where:** Management → Inventory → Inventory operations, Stock, and Stock movements.

1. Change stock in Inventory operations and confirm the table reflects the result immediately after save.
2. Select another branch and confirm balances change to that branch’s stock. The workspace also refreshes periodically while open.
3. Set a unit’s reorder quantity above its balance. Confirm it appears under Low stock. Set its balance to zero and confirm it appears under Out of stock.
4. Open Stock movements. Search for the test unit and inspect direction, quantity before/after, user, branch, reason, and batch/serial information.
5. In Inventory operations, inspect recent operations and confirm physical counts (including zero-variance counts) are listed.

**Pass when:** branch balances and alert filters agree with the movements and recent operation records.

## Purchase receiving check

**Where:** Management → Purchasing → New Purchase.

1. Add an untracked unit to a purchase, save the purchase, and confirm Main stock and purchase cost update.
2. Add a batch-tracked unit and open the line’s Batch, serial and expiry section. Enter batch and expiry details and save. Confirm the purchase and lot retain those details.
3. Repeat with a serial-tracked unit using quantity one and a unique serial.
4. Edit a tracked purchase and confirm its tracking fields remain available. Try deleting or editing a purchase after its tracked stock has been used or transferred; confirm the change is rejected unless the stock is restored.

**Pass when:** received quantities and costs update, tracking details are visible on the purchase, and unsafe tracked-stock reversals are blocked. Purchases are received immediately; partial receiving is not currently supported.

## Automated checks

Run the focused product/inventory feature tests with the project PHP binary:

```sh
/opt/homebrew/opt/php@8.4/bin/php artisan test --filter=ProductInventoryTest
```

The feature tests cover screen authentication/rendering, product validation, brand/branch management, variants and barcodes, inventory operations, duplicate submissions, batch/serial handling, bundle assembly, and tracked purchase behavior.

## Record your results

For each section, note **Pass**, **Fail**, or **Not checked**. For a failure, record the screen, product/unit code, branch, steps taken, expected result, actual result, and any displayed validation message. Avoid using real customer or production inventory data while following this guide.
