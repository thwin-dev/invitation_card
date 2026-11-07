<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoPosSeeder extends Seeder
{
    public function run(): void
    {
        // DemoPosSeeder is included in DatabaseSeeder, so make repeat `db:seed` safe.
        if (DB::table('products')->where('code', 'ITEM-001')->exists()) {
            return;
        }

        $now = Carbon::now();
        $owner = DB::table('users')->updateOrInsert(['user_code' => 'POS-001'], [
            'name' => 'POS Owner', 'email' => 'owner@example.com', 'password' => Hash::make('password123'),
            'role' => 'Owner', 'prohibition_flag' => false, 'photo_path' => null, 'updated_at' => $now, 'created_at' => $now,
        ]);
        $ownerId = (int) DB::table('users')->where('user_code', 'POS-001')->value('id');

        foreach ([['POS-002', 'Sales Staff', 'sales@example.com', 'Sales'], ['POS-003', 'Store Keeper', 'store@example.com', 'Store Keeper']] as [$code, $name, $email, $role]) {
            DB::table('users')->updateOrInsert(['user_code' => $code], ['name' => $name, 'email' => $email, 'password' => Hash::make('password123'), 'role' => $role, 'prohibition_flag' => false, 'updated_at' => $now, 'created_at' => $now]);
        }

        $categories = [];
        foreach (['Beverages', 'Snacks', 'Household', 'Personal Care'] as $index => $name) {
            $categories[$name] = DB::table('product_categories')->insertGetId(['code' => 'CAT-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT), 'name' => $name, 'created_by' => $ownerId, 'created_at' => $now, 'updated_at' => $now]);
        }
        $subcategories = [];
        foreach ([['Coffee', 'Beverages'], ['Tea', 'Beverages'], ['Biscuits', 'Snacks'], ['Cleaning', 'Household'], ['Bath', 'Personal Care']] as $index => [$name, $category]) {
            $subcategories[$name] = DB::table('product_subcategories')->insertGetId(['code' => 'SUB-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT), 'name' => $name, 'category_id' => $categories[$category], 'created_at' => $now, 'updated_at' => $now]);
        }

        $catalog = [
            ['Arabica Coffee', 'Beverages', 'Coffee', 250, 4500, 5000], ['Green Tea', 'Beverages', 'Tea', 180, 3500, 4000],
            ['Butter Cookies', 'Snacks', 'Biscuits', 120, 2800, 3500], ['Dishwashing Liquid', 'Household', 'Cleaning', 80, 4200, 5000],
            ['Aloe Vera Soap', 'Personal Care', 'Bath', 150, 1800, 2500], ['Mineral Water', 'Beverages', 'Tea', 200, 700, 1000],
        ];
        $units = [];
        foreach ($catalog as $index => [$name, $category, $subcategory, $stock, $purchase, $sale]) {
            $productId = DB::table('products')->insertGetId(['code' => 'ITEM-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT), 'name' => $name, 'created_by' => $ownerId, 'category_id' => $categories[$category], 'subcategory_id' => $subcategories[$subcategory], 'customer_visible' => true, 'photo_path' => 'images/products/product-'.($index + 1).'.svg', 'created_at' => $now, 'updated_at' => $now]);
            $unitId = DB::table('product_units')->insertGetId(['product_id' => $productId, 'code' => 'UNIT-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT), 'original_code' => 'SKU-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT), 'name' => 'Piece', 'current_quantity' => $stock, 'reorder_quantity' => 20, 'normal_sale_price' => $sale, 'whole_sale_price' => $sale - 300, 'order_price' => $sale, 'purchase_price' => $purchase, 'created_at' => $now, 'updated_at' => $now]);
            $units[] = ['id' => $unitId, 'purchase' => $purchase, 'sale' => $sale];
        }
        DB::table('product_unit_relations')->insert(['product_id' => DB::table('products')->where('code', 'ITEM-001')->value('id'), 'from_unit_id' => $units[0]['id'], 'to_unit_id' => $units[0]['id'], 'quantity' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $customers = [];
        foreach ([['Aung Aung', '0912345678', true], ['Su Su', '0923456789', true], ['Mg Mg', '0934567890', false], ['Golden Mart', '0945678901', true]] as [$name, $phone, $credit]) {
            $customers[] = DB::table('pos_customers')->insertGetId(['name' => $name, 'phone' => $phone, 'address' => 'Yangon, Myanmar', 'allow_credit' => $credit, 'customer_level' => $credit ? 2 : 1, 'created_by' => $ownerId, 'created_at' => $now, 'updated_at' => $now]);
        }

        $purchaseId = DB::table('purchases')->insertGetId(['supplier_name' => 'Demo Wholesale Supplier', 'total_quantity' => 60, 'total_price' => 174000, 'purchase_date' => $now->copy()->subDays(5)->toDateString(), 'purchase_by' => $ownerId, 'created_at' => $now, 'updated_at' => $now]);
        foreach (array_slice($units, 0, 3) as $unit) DB::table('purchase_items')->insert(['purchase_id' => $purchaseId, 'product_unit_id' => $unit['id'], 'quantity' => 20, 'price' => $unit['purchase'], 'created_at' => $now, 'updated_at' => $now]);

        foreach ([['SALE-0001', $customers[0], $units[0], 2, 10000, 10000], ['SALE-0002', $customers[1], $units[2], 3, 10500, 5000]] as [$voucher, $customerId, $unit, $qty, $total, $paid]) {
            $saleId = DB::table('sales')->insertGetId(['voucher_code' => $voucher, 'customer_id' => $customerId, 'sale_by' => $ownerId, 'total_quantity' => $qty, 'total_price' => $total, 'paid_amount' => $paid, 'credit_amount' => $total - $paid, 'sale_date' => $now->copy()->subDays(2)->toDateString(), 'status' => 'completed', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('sale_items')->insert(['sale_id' => $saleId, 'product_unit_id' => $unit['id'], 'quantity' => $qty, 'price' => $total / $qty, 'created_at' => $now, 'updated_at' => $now]);
            if ($total > $paid) DB::table('customer_credit_records')->insert(['customer_id' => $customerId, 'sale_id' => $saleId, 'amount' => $total - $paid, 'created_at' => $now, 'updated_at' => $now]);
        }

        $orderId = DB::table('pos_orders')->insertGetId(['order_number' => 'ORD-0001', 'customer_id' => $customers[3], 'address' => 'No. 12, Bahan Township, Yangon', 'phone' => '0945678901', 'order_date' => $now->copy()->subDay(), 'total_quantity' => 5, 'estimated_price' => 25000, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('pos_order_items')->insert(['order_id' => $orderId, 'product_unit_id' => $units[1]['id'], 'quantity' => 5, 'estimated_price' => 5000, 'created_at' => $now, 'updated_at' => $now]);
        foreach ([['Rent', 'Monthly shop rent', 300000], ['Utilities', 'Electricity and water', 45000], ['Delivery', 'Local delivery expense', 15000]] as [$type, $title, $amount]) DB::table('pos_expenses')->insert(['type' => $type, 'period' => $now->format('Y-m'), 'expense_date' => $now->copy()->subDays(3)->toDateString(), 'title' => $title, 'description' => 'Demo expense', 'amount' => $amount, 'is_profit' => false, 'created_by' => $ownerId, 'created_at' => $now, 'updated_at' => $now]);

        foreach ([['POS Basics', 'Learn the basics of point of sale operations.'], ['Inventory Management', 'Manage products, stock and purchasing efficiently.'], ['Customer Service', 'Build better customer relationships at the counter.']] as $index => [$title, $description]) DB::table('courses')->insert(['title' => $title, 'slug' => str($title)->slug(), 'category' => 'Business', 'level' => 'beginner', 'price_type' => 'free', 'price_mmk' => 0, 'short_description' => $description, 'description' => $description, 'duration_minutes' => 90, 'status' => 'published', 'sort_order' => $index + 1, 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
    }
}
