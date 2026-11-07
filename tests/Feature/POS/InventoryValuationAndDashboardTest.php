<?php

namespace Tests\Feature\POS;

use App\Livewire\InventoryValuation;
use App\Models\User;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryValuationAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);
    }

    public function test_guest_cannot_access_dashboard_or_inventory_valuation(): void
    {
        $this->get('/management')->assertRedirect('/login');
        $this->get('/management/reports/inventory-valuation')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard_and_inventory_valuation(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'web')
            ->get('/management')
            ->assertOk()
            ->assertSee('Operations &amp; Retail Dashboard', false)
            ->assertSee('Stock Valuation (Cost)')
            ->assertSee('Inventory Valuation');

        $this->actingAs($admin, 'web')
            ->get('/management/reports/inventory-valuation')
            ->assertOk()
            ->assertSee('Inventory Valuation &amp; Health', false)
            ->assertSee('Cost Valuation (Asset)')
            ->assertSee('Retail Valuation');
    }

    public function test_inventory_valuation_livewire_computes_accurate_kpi_and_margins(): void
    {
        $admin = $this->createAdmin();

        $cat1 = ProductCategory::query()->create([
            'name' => 'Beverages',
            'code' => 'CAT-BEV',
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        $sub1 = \App\Modules\ProductCategory\Models\ProductSubcategory::query()->create([
            'category_id' => $cat1->id,
            'name' => 'Hot Drinks',
            'code' => 'SUB-BEV',
            'is_active' => true,
        ]);

        $cat2 = ProductCategory::query()->create([
            'name' => 'Snacks',
            'code' => 'CAT-SNK',
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        $sub2 = \App\Modules\ProductCategory\Models\ProductSubcategory::query()->create([
            'category_id' => $cat2->id,
            'name' => 'Chips',
            'code' => 'SUB-SNK',
            'is_active' => true,
        ]);

        $prod1 = Product::query()->create([
            'name' => 'Coffee Can',
            'code' => 'PRD-COFFEE',
            'category_id' => $cat1->id,
            'subcategory_id' => $sub1->id,
            'created_by' => $admin->id,
            'customer_visible' => true,
        ]);

        $prod2 = Product::query()->create([
            'name' => 'Potato Chips',
            'code' => 'PRD-CHIPS',
            'category_id' => $cat2->id,
            'subcategory_id' => $sub2->id,
            'created_by' => $admin->id,
            'customer_visible' => true,
        ]);

        // Unit 1: qty=10, cost=100, retail=150 => costVal=1000, retailVal=1500, profit=500
        ProductUnit::query()->create([
            'product_id' => $prod1->id,
            'name' => 'Can',
            'code' => 'CAN-101',
            'current_quantity' => 10,
            'reorder_quantity' => 5,
            'purchase_price' => 100,
            'normal_sale_price' => 150,
            'whole_sale_price' => 130,
            'order_price' => 140,
        ]);

        // Unit 2: qty=20, cost=50, retail=80 => costVal=1000, retailVal=1600, profit=600
        ProductUnit::query()->create([
            'product_id' => $prod2->id,
            'name' => 'Bag',
            'code' => 'BAG-202',
            'current_quantity' => 20,
            'reorder_quantity' => 25, // low stock!
            'purchase_price' => 50,
            'normal_sale_price' => 80,
            'whole_sale_price' => 70,
            'order_price' => 75,
        ]);

        Livewire::actingAs($admin, 'web')
            ->test(InventoryValuation::class)
            ->assertSet('sortBy', 'valuation_cost')
            ->assertSee('Coffee Can')
            ->assertSee('Potato Chips')
            ->assertSee('Beverages')
            ->assertSee('Snacks')
            ->assertSee('2,000.00') // Total Cost Valuation (1000 + 1000)
            ->assertSee('3,100.00') // Total Retail Valuation (1500 + 1600)
            ->assertSee('1,100.00') // Potential Profit (500 + 600)
            ->assertSee('Low stock')
            ->call('exportCsv')
            ->assertFileDownloaded();
    }

    public function test_inventory_valuation_filters_by_category_and_status(): void
    {
        $admin = $this->createAdmin();

        $cat = ProductCategory::query()->create([
            'name' => 'Electronics',
            'code' => 'CAT-ELEC',
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        $sub = \App\Modules\ProductCategory\Models\ProductSubcategory::query()->create([
            'category_id' => $cat->id,
            'name' => 'Accessories',
            'code' => 'SUB-ACC',
            'is_active' => true,
        ]);

        $prod = Product::query()->create([
            'name' => 'USB Cable',
            'code' => 'PRD-USB',
            'category_id' => $cat->id,
            'subcategory_id' => $sub->id,
            'created_by' => $admin->id,
            'customer_visible' => true,
        ]);

        ProductUnit::query()->create([
            'product_id' => $prod->id,
            'name' => 'Piece',
            'code' => 'USB-001',
            'current_quantity' => 0, // out of stock
            'reorder_quantity' => 5,
            'purchase_price' => 10,
            'normal_sale_price' => 20,
            'whole_sale_price' => 15,
            'order_price' => 18,
        ]);

        Livewire::actingAs($admin, 'web')
            ->test(InventoryValuation::class)
            ->set('stockStatus', 'out_of_stock')
            ->assertSee('USB Cable')
            ->assertSee('Out of stock')
            ->set('stockStatus', 'in_stock')
            ->assertDontSee('USB Cable');
    }
}
