<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BulkDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Keep the large demo dataset from duplicating on repeated `db:seed` runs.
        if (DB::table('products')->where('code', 'ITEM-007')->exists()) {
            return;
        }

        mt_srand(20260911);
        $now = Carbon::now();
        $ownerId = (int) DB::table('users')->where('user_code', 'POS-001')->value('id');
        $categoryIds = DB::table('product_categories')->pluck('id')->values();
        $subcategoryIds = DB::table('product_subcategories')->pluck('id')->values();
        $unitIds = DB::table('product_units')->pluck('id')->values()->all();

        for ($i = 7; $i <= 106; $i++) {
            $categoryId = $categoryIds[($i - 7) % $categoryIds->count()];
            $subcategoryId = $subcategoryIds[($i - 7) % $subcategoryIds->count()];
            $productId = DB::table('products')->insertGetId(['code' => 'ITEM-'.str_pad($i, 3, '0', STR_PAD_LEFT), 'name' => 'Demo Product '.str_pad($i, 3, '0', STR_PAD_LEFT), 'created_by' => $ownerId, 'category_id' => $categoryId, 'subcategory_id' => $subcategoryId, 'customer_visible' => true, 'photo_path' => 'images/products/product-'.(($i - 1) % 6 + 1).'.svg', 'created_at' => $now, 'updated_at' => $now]);
            $purchase = mt_rand(500, 20000);
            $unitIds[] = DB::table('product_units')->insertGetId(['product_id' => $productId, 'code' => 'UNIT-'.str_pad($i, 3, '0', STR_PAD_LEFT), 'original_code' => 'SKU-'.str_pad($i, 4, '0', STR_PAD_LEFT), 'name' => 'Piece', 'current_quantity' => mt_rand(10, 300), 'reorder_quantity' => mt_rand(5, 30), 'normal_sale_price' => $purchase * 1.35, 'whole_sale_price' => $purchase * 1.2, 'order_price' => $purchase * 1.3, 'purchase_price' => $purchase, 'created_at' => $now, 'updated_at' => $now]);
        }
        for ($i = 5; $i <= 104; $i++) DB::table('pos_customers')->insert(['name' => 'Demo Customer '.str_pad($i, 3, '0', STR_PAD_LEFT), 'phone' => '09'.str_pad((string) (700000000 + $i), 9, '0', STR_PAD_LEFT), 'address' => 'Township '.(($i % 10) + 1).', Yangon', 'allow_credit' => $i % 3 !== 0, 'customer_level' => ($i % 3) + 1, 'created_by' => $ownerId, 'created_at' => $now, 'updated_at' => $now]);
        $customerIds = DB::table('pos_customers')->pluck('id')->values()->all();

        for ($i = 2; $i <= 61; $i++) {
            $date = $now->copy()->subDays(mt_rand(1, 90))->toDateString(); $totalQty = 0; $total = 0;
            $rows = [];
            foreach (array_rand($unitIds, mt_rand(2, 4)) as $unitIndex) { $qty = mt_rand(2, 25); $price = (int) DB::table('product_units')->where('id', $unitIds[$unitIndex])->value('purchase_price'); $totalQty += $qty; $total += $qty * $price; $rows[] = [$unitIds[$unitIndex], $qty, $price]; }
            $purchaseId = DB::table('purchases')->insertGetId(['supplier_name' => 'Supplier '.str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT), 'total_quantity' => $totalQty, 'total_price' => $total, 'purchase_date' => $date, 'purchase_by' => $ownerId, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($rows as [$unitId, $qty, $price]) DB::table('purchase_items')->insert(['purchase_id' => $purchaseId, 'product_unit_id' => $unitId, 'quantity' => $qty, 'price' => $price, 'created_at' => $now, 'updated_at' => $now]);
        }
        for ($i = 3; $i <= 152; $i++) {
            $date = $now->copy()->subDays(mt_rand(1, 90))->toDateString(); $customerId = $customerIds[array_rand($customerIds)]; $unitId = $unitIds[array_rand($unitIds)]; $qty = mt_rand(1, 8); $price = (float) DB::table('product_units')->where('id', $unitId)->value('normal_sale_price'); $total = $qty * $price; $credit = $i % 4 === 0 ? round($total * .35, 2) : 0; $saleId = DB::table('sales')->insertGetId(['voucher_code' => 'SALE-'.str_pad($i, 4, '0', STR_PAD_LEFT), 'customer_id' => $customerId, 'sale_by' => $ownerId, 'total_quantity' => $qty, 'total_price' => $total, 'paid_amount' => $total - $credit, 'credit_amount' => $credit, 'sale_date' => $date, 'status' => 'completed', 'created_at' => $now, 'updated_at' => $now]); DB::table('sale_items')->insert(['sale_id' => $saleId, 'product_unit_id' => $unitId, 'quantity' => $qty, 'price' => $price, 'created_at' => $now, 'updated_at' => $now]); if ($credit > 0) DB::table('customer_credit_records')->insert(['customer_id' => $customerId, 'sale_id' => $saleId, 'amount' => $credit, 'created_at' => $now, 'updated_at' => $now]);
        }
        for ($i = 2; $i <= 81; $i++) { $customerId = $customerIds[array_rand($customerIds)]; $unitId = $unitIds[array_rand($unitIds)]; $qty = mt_rand(1, 10); $price = (float) DB::table('product_units')->where('id', $unitId)->value('order_price'); DB::table('pos_orders')->insertGetId(['order_number' => 'ORD-'.str_pad($i, 4, '0', STR_PAD_LEFT), 'customer_id' => $customerId, 'address' => 'Demo delivery address '.($i), 'phone' => DB::table('pos_customers')->where('id', $customerId)->value('phone'), 'order_date' => $now->copy()->subDays(mt_rand(0, 30)), 'delivered_date' => $i % 3 === 0 ? $now->copy()->subDays(mt_rand(0, 10)) : null, 'total_quantity' => $qty, 'estimated_price' => $qty * $price, 'status' => ['pending', 'confirmed', 'delivered'][$i % 3], 'delivery_by' => $ownerId, 'created_at' => $now, 'updated_at' => $now]); $orderId = DB::getPdo()->lastInsertId(); DB::table('pos_order_items')->insert(['order_id' => $orderId, 'product_unit_id' => $unitId, 'quantity' => $qty, 'estimated_price' => $price, 'created_at' => $now, 'updated_at' => $now]); }
        for ($i = 4; $i <= 103; $i++) DB::table('pos_expenses')->insert(['type' => ['Rent', 'Utilities', 'Delivery', 'Supplies'][$i % 4], 'period' => $now->copy()->subDays($i % 90)->format('Y-m'), 'expense_date' => $now->copy()->subDays($i % 90)->toDateString(), 'title' => 'Demo expense '.str_pad($i, 3, '0', STR_PAD_LEFT), 'description' => 'Generated demo expense record', 'amount' => mt_rand(5000, 350000), 'is_profit' => false, 'created_by' => $ownerId, 'created_at' => $now, 'updated_at' => $now]);

        $courseIds = DB::table('courses')->pluck('id')->all();
        foreach ($courseIds as $courseId) { for ($i = 1; $i <= 5; $i++) DB::table('course_includes')->insert(['course_id' => $courseId, 'title' => 'Included lesson '.$i, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now]); for ($i = 1; $i <= 5; $i++) DB::table('course_learning_outcomes')->insert(['course_id' => $courseId, 'title' => 'Learning outcome '.$i, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now]); }
        foreach (array_slice($customerIds, 0, min(20, count($customerIds))) as $userId) foreach ($courseIds as $courseId) if (DB::table('users')->where('id', $userId)->exists()) DB::table('course_wishlists')->insertOrIgnore(['user_id' => $userId, 'course_id' => $courseId, 'created_at' => $now, 'updated_at' => $now]);
    }
}
