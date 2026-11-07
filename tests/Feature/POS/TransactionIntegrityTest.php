<?php

namespace Tests\Feature\POS;

use App\Models\User;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Order\Models\PosOrder;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function productUnit(User $user, int $stock = 10): ProductUnit
    {
        $category = ProductCategory::query()->create(['name' => 'Test category', 'created_by' => $user->id]);
        $subcategory = ProductSubcategory::query()->create(['name' => 'Test subcategory', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'TEST-'.uniqid(), 'name' => 'Test product', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $user->id]);
        return ProductUnit::query()->create(['product_id' => $product->id, 'name' => 'Piece', 'current_quantity' => $stock, 'reorder_quantity' => 1, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 100, 'purchase_price' => 70]);
    }

    public function test_sale_cancel_restores_stock_and_records_movement(): void
    {
        $user = User::query()->create(['name' => 'Tester', 'user_code' => 'tester-1', 'email' => 'tester1@example.com', 'password' => 'secret', 'prohibition_flag' => false]);
        $unit = $this->productUnit($user, 10);
        $sale = Sale::query()->create(['voucher_code' => 'TEST-SALE-1', 'sale_by' => $user->id, 'total_quantity' => 2, 'total_price' => 200, 'paid_amount' => 200, 'credit_amount' => 0, 'sale_date' => '2026-09-11', 'status' => 'completed']);
        $sale->items()->create(['product_unit_id' => $unit->id, 'quantity' => 2, 'price' => 100]);
        $unit->decrement('current_quantity', 2);
        $this->actingAs($user, 'web')->put('/management/sales/'.$sale->id.'/cancel')->assertRedirect('/management/sales/'.$sale->id);
        $this->assertSame(10, $unit->fresh()->current_quantity);
        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertDatabaseHas('stock_movements', ['reference_type' => 'sale', 'reference_id' => $sale->id, 'direction' => 'IN', 'quantity_before' => 8, 'quantity_after' => 10]);
    }

    public function test_purchase_delete_reduces_stock_and_records_movement(): void
    {
        $user = User::query()->create(['name' => 'Tester', 'user_code' => 'tester-2', 'email' => 'tester2@example.com', 'password' => 'secret', 'prohibition_flag' => false]);
        $unit = $this->productUnit($user, 15);
        $purchase = Purchase::query()->create(['supplier_name' => 'Test supplier', 'total_quantity' => 5, 'total_price' => 350, 'purchase_date' => '2026-09-11', 'purchase_by' => $user->id]);
        $purchase->items()->create(['product_unit_id' => $unit->id, 'quantity' => 5, 'price' => 70]);
        $this->actingAs($user, 'web')->delete('/management/purchases/'.$purchase->id)->assertRedirect('/management/purchases');
        $this->assertSame(10, $unit->fresh()->current_quantity);
        $this->assertDatabaseHas('stock_movements', ['reference_type' => 'purchase', 'reference_id' => $purchase->id, 'direction' => 'OUT', 'quantity_before' => 15, 'quantity_after' => 10]);
    }

    public function test_order_delivery_and_cancellation_restore_stock(): void
    {
        $user = User::query()->create(['name' => 'Tester', 'user_code' => 'tester-3', 'email' => 'tester3@example.com', 'password' => 'secret', 'prohibition_flag' => false]);
        $unit = $this->productUnit($user, 10);
        $customer = \App\Modules\Customer\Models\PosCustomer::query()->create(['name' => 'Order customer', 'created_by' => $user->id]);
        $order = PosOrder::query()->create(['order_number' => 'TEST-ORDER-1', 'customer_id' => $customer->id, 'address' => 'Test address', 'order_date' => '2026-09-11 10:00:00', 'total_quantity' => 3, 'estimated_price' => 300, 'status' => 'pending']);
        $order->items()->create(['product_unit_id' => $unit->id, 'quantity' => 3, 'estimated_price' => 100]);
        $this->actingAs($user, 'web')->put('/management/orders/'.$order->id.'/status', ['status' => 'delivered'])->assertRedirect();
        $this->assertSame(7, $unit->fresh()->current_quantity);
        $this->actingAs($user, 'web')->put('/management/orders/'.$order->id.'/status', ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(10, $unit->fresh()->current_quantity);
        $this->assertSame(2, StockMovement::query()->where('reference_type', 'order')->where('reference_id', $order->id)->count());
    }

    public function test_delete_held_sale_order_removes_held_sale_and_items(): void
    {
        $user = User::query()->create(['name' => 'Tester', 'user_code' => 'tester-4', 'email' => 'tester4@example.com', 'password' => 'secret', 'prohibition_flag' => false]);
        $unit = $this->productUnit($user, 10);
        $sale = Sale::query()->create([
            'voucher_code' => 'TEST-HELD-1',
            'sale_by' => $user->id,
            'total_quantity' => 2,
            'total_price' => 200,
            'paid_amount' => 0,
            'credit_amount' => 200,
            'sale_date' => '2026-09-14',
            'status' => 'held',
        ]);
        $sale->items()->create(['product_unit_id' => $unit->id, 'quantity' => 2, 'price' => 100]);

        \Livewire\Livewire::actingAs($user, 'web')
            ->test(\App\Livewire\SaleScreen::class)
            ->assertSeeHtml('wire:click="deleteHeldSale('.$sale->id.')"')
            ->assertSeeHtml('title="Delete order"')
            ->call('deleteHeldSale', $sale->id)
            ->assertDispatched('toast', message: 'Held order deleted.');

        $this->assertSoftDeleted('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
    }
}
