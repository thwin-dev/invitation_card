<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductInventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $ownerId = DB::table('users')->where('user_code', 'POS-001')->value('id')
                ?? DB::table('users')->orderBy('id')->value('id');

            $brandIds = [];
            foreach (['PVO Basics', 'PVO Select'] as $brand) {
                DB::table('product_brands')->updateOrInsert(['name' => $brand], ['is_active' => true, 'updated_at' => $now, 'created_at' => $now]);
                $brandIds[$brand] = (int) DB::table('product_brands')->where('name', $brand)->value('id');
            }

            DB::table('inventory_branches')->updateOrInsert(['name' => 'North Warehouse'], ['is_main' => false, 'updated_at' => $now, 'created_at' => $now]);
            $mainBranchId = (int) DB::table('inventory_branches')->where('is_main', true)->value('id');
            $northBranchId = (int) DB::table('inventory_branches')->where('name', 'North Warehouse')->value('id');

            $categoryCode = 'DEMO-APPAREL';
            DB::table('product_categories')->updateOrInsert(['code' => $categoryCode], ['name' => 'Demo Apparel', 'created_by' => $ownerId, 'updated_at' => $now, 'created_at' => $now]);
            $categoryId = (int) DB::table('product_categories')->where('code', $categoryCode)->value('id');
            $subcategoryCode = 'DEMO-TSHIRTS';
            DB::table('product_subcategories')->updateOrInsert(['code' => $subcategoryCode], ['name' => 'Demo T-shirts', 'category_id' => $categoryId, 'updated_at' => $now, 'created_at' => $now]);
            $subcategoryId = (int) DB::table('product_subcategories')->where('code', $subcategoryCode)->value('id');

            $productIds = [];
            foreach ([
                ['DEMO-PROD-TSHIRT', 'Demo Cotton T-shirt', $brandIds['PVO Basics']],
                ['DEMO-PROD-POLO', 'Demo Polo Shirt', $brandIds['PVO Select']],
            ] as [$code, $name, $brandId]) {
                DB::table('products')->updateOrInsert(['code' => $code], [
                    'name' => $name, 'created_by' => $ownerId, 'category_id' => $categoryId,
                    'subcategory_id' => $subcategoryId, 'brand_id' => $brandId,
                    'customer_visible' => true, 'updated_at' => $now, 'created_at' => $now,
                ]);
                $productIds[$code] = (int) DB::table('products')->where('code', $code)->value('id');
            }

            $unitIds = [];
            $units = [
                ['DEMO-SKU-TS-M-NVY', 'DEMO-TS-M-NVY', 'Medium Navy', $productIds['DEMO-PROD-TSHIRT'], 'M', 'Navy', 'batch', 24, 8, 4500, 6500],
                ['DEMO-SKU-TS-L-NVY', 'DEMO-TS-L-NVY', 'Large Navy', $productIds['DEMO-PROD-TSHIRT'], 'L', 'Navy', 'none', 18, 5, 4500, 6500],
                ['DEMO-SKU-POLO-M-BLK', 'DEMO-POLO-M-BLK', 'Medium Black', $productIds['DEMO-PROD-POLO'], 'M', 'Black', 'serial', 2, 1, 9000, 14000],
                ['DEMO-SKU-TS-3PK', 'DEMO-TS-3PK', 'Three T-shirt bundle', $productIds['DEMO-PROD-TSHIRT'], null, null, 'none', 0, 2, 12000, 17500],
            ];

            foreach ($units as [$sku, $barcodePrefix, $name, $productId, $size, $color, $tracking, $mainQty, $northQty, $cost, $price]) {
                $barcode = $this->ean13($sku);
                DB::table('product_units')->updateOrInsert(['code' => $sku], [
                    'product_id' => $productId, 'original_code' => $barcode, 'name' => $name,
                    'current_quantity' => $mainQty, 'reorder_quantity' => 3,
                    'normal_sale_price' => $price, 'whole_sale_price' => $price - 500,
                    'order_price' => $price, 'purchase_price' => $cost,
                    'size' => $size, 'color' => $color, 'tracking' => $tracking,
                    'updated_at' => $now, 'created_at' => $now,
                ]);
                $unitId = (int) DB::table('product_units')->where('code', $sku)->value('id');
                $unitIds[$sku] = $unitId;

                if ($mainQty > 0) {
                    $this->seedLotAndMovement($unitId, $mainBranchId, 'DEMO-MAIN-'.$sku, $mainQty, $tracking, $now);
                }
                if ($northQty > 0) {
                    $this->seedLotAndMovement($unitId, $northBranchId, 'DEMO-NORTH-'.$sku, $northQty, 'none', $now);
                }
            }

            $bundleId = $unitIds['DEMO-SKU-TS-3PK'];
            DB::table('bundle_components')->where('bundle_unit_id', $bundleId)->delete();
            DB::table('bundle_components')->updateOrInsert(
                ['bundle_unit_id' => $bundleId, 'component_unit_id' => $unitIds['DEMO-SKU-TS-M-NVY']],
                ['quantity' => 2]
            );
            DB::table('bundle_components')->updateOrInsert(
                ['bundle_unit_id' => $bundleId, 'component_unit_id' => $unitIds['DEMO-SKU-TS-L-NVY']],
                ['quantity' => 1]
            );
        });
    }

    private function seedLotAndMovement(int $unitId, int $branchId, string $batch, int $quantity, string $tracking, $now): void
    {
        if ($tracking === 'serial') {
            for ($serialIndex = 1; $serialIndex <= $quantity; $serialIndex++) {
                $serial = 'DEMO-SERIAL-'.$unitId.'-'.$branchId.'-'.$serialIndex;
                DB::table('inventory_lots')->updateOrInsert(
                    ['serial_number' => $serial],
                    [
                        'product_unit_id' => $unitId, 'branch_id' => $branchId,
                        'batch_number' => null, 'expires_at' => null, 'quantity' => 1,
                        'updated_at' => $now, 'created_at' => $now,
                    ]
                );
                $lotId = (int) DB::table('inventory_lots')->where('serial_number', $serial)->value('id');
                $this->seedMovement($unitId, $branchId, $lotId, 1, $now);
            }

            return;
        }

        $lotAttributes = [
            'product_unit_id' => $unitId,
            'branch_id' => $branchId,
            'batch_number' => $tracking === 'serial' ? null : $batch,
            'serial_number' => null,
            'expires_at' => $tracking === 'batch' ? $now->copy()->addYear()->toDateString() : null,
            'quantity' => $quantity,
            'updated_at' => $now,
            'created_at' => $now,
        ];
        DB::table('inventory_lots')->updateOrInsert(
            ['product_unit_id' => $unitId, 'branch_id' => $branchId, 'batch_number' => $lotAttributes['batch_number']],
            $lotAttributes
        );
        $lotId = (int) DB::table('inventory_lots')->where('product_unit_id', $unitId)
            ->where('branch_id', $branchId)->where('batch_number', $lotAttributes['batch_number'])->value('id');

        DB::table('stock_movements')->updateOrInsert(
            ['reference_type' => 'seed', 'reference_id' => $lotId, 'product_unit_id' => $unitId, 'branch_id' => $branchId],
            [
                'user_id' => DB::table('users')->where('user_code', 'POS-001')->value('id') ?? DB::table('users')->orderBy('id')->value('id'),
                'direction' => 'IN', 'quantity' => $quantity, 'quantity_before' => 0, 'quantity_after' => $quantity,
                'lot_id' => $lotId, 'note' => 'Seeded demo inventory', 'updated_at' => $now, 'created_at' => $now,
            ]
        );
    }

    private function ean13(string $prefix): string
    {
        $base = '20'.str_pad(sprintf('%u', crc32($prefix)), 10, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($index = 0; $index < 12; $index++) {
            $sum += (int) $base[$index] * ($index % 2 === 0 ? 1 : 3);
        }

        return $base.(string) ((10 - ($sum % 10)) % 10);
    }

    private function seedMovement(int $unitId, int $branchId, int $lotId, int $quantity, $now): void
    {
        DB::table('stock_movements')->updateOrInsert(
            ['reference_type' => 'seed', 'reference_id' => $lotId, 'product_unit_id' => $unitId, 'branch_id' => $branchId],
            [
                'user_id' => DB::table('users')->where('user_code', 'POS-001')->value('id') ?? DB::table('users')->orderBy('id')->value('id'),
                'direction' => 'IN', 'quantity' => $quantity, 'quantity_before' => 0, 'quantity_after' => $quantity,
                'lot_id' => $lotId, 'note' => 'Seeded demo inventory', 'updated_at' => $now, 'created_at' => $now,
            ]
        );
    }
}
