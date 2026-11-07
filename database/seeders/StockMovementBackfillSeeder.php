<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockMovementBackfillSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('stock_movements')->exists()) return;
        $now = Carbon::now(); $userId = DB::table('users')->where('user_code', 'POS-001')->value('id');
        foreach (DB::table('purchase_items')->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')->select('purchase_items.*')->get() as $item) DB::table('stock_movements')->insert(['product_unit_id' => $item->product_unit_id, 'user_id' => $userId, 'direction' => 'IN', 'quantity' => $item->quantity, 'quantity_before' => 0, 'quantity_after' => $item->quantity, 'reference_type' => 'purchase', 'reference_id' => $item->purchase_id, 'note' => 'Backfilled purchase movement', 'created_at' => $now, 'updated_at' => $now]);
        foreach (DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sales.status', 'completed')->select('sale_items.*')->get() as $item) DB::table('stock_movements')->insert(['product_unit_id' => $item->product_unit_id, 'user_id' => $userId, 'direction' => 'OUT', 'quantity' => $item->quantity, 'quantity_before' => $item->quantity, 'quantity_after' => 0, 'reference_type' => 'sale', 'reference_id' => $item->sale_id, 'note' => 'Backfilled sale movement', 'created_at' => $now, 'updated_at' => $now]);
    }
}
