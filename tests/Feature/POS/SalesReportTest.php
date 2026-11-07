<?php

namespace Tests\Feature\POS;

use App\Livewire\SalesReport;
use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_requires_authentication(): void
    {
        $this->get('/management/reports/sales')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_sales_report_page(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        $this->actingAs($admin, 'web')
            ->get('/management/reports/sales')
            ->assertOk()
            ->assertSee('Sales Report')
            ->assertSee('Daily')
            ->assertSee('Weekly')
            ->assertSee('Monthly');
    }

    public function test_livewire_sales_report_calculates_aggregations_and_breakdowns(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        $customer = PosCustomer::query()->create([
            'name' => 'John Doe',
            'phone' => '0912345678',
            'created_by' => $admin->id,
        ]);

        $category = ProductCategory::query()->create([
            'code' => 'CAT-01',
            'name' => 'Beverages',
            'created_by' => $admin->id,
        ]);

        $subcategory = ProductSubcategory::query()->create([
            'code' => 'SUB-01',
            'name' => 'Coffee',
            'category_id' => $category->id,
        ]);

        $product = Product::query()->create([
            'code' => 'ITEM-01',
            'name' => 'Arabica Coffee',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'created_by' => $admin->id,
        ]);

        $unit = ProductUnit::query()->create([
            'product_id' => $product->id,
            'code' => 'UNIT-01',
            'original_code' => 'SKU-01',
            'name' => 'Pack',
            'current_quantity' => 100,
            'reorder_quantity' => 10,
            'normal_sale_price' => 5000,
            'whole_sale_price' => 4500,
            'order_price' => 4800,
            'purchase_price' => 4000,
        ]);

        // Sale 1: 2026-09-01
        $sale1 = Sale::query()->create([
            'voucher_code' => 'V-001',
            'customer_id' => $customer->id,
            'sale_by' => $admin->id,
            'total_quantity' => 2,
            'total_price' => 10000,
            'paid_amount' => 8000,
            'credit_amount' => 2000,
            'sale_date' => '2026-09-01',
            'status' => 'completed',
        ]);
        SaleItem::query()->create([
            'sale_id' => $sale1->id,
            'product_unit_id' => $unit->id,
            'quantity' => 2,
            'price' => 5000,
        ]);

        // Sale 2: 2026-09-05
        $sale2 = Sale::query()->create([
            'voucher_code' => 'V-002',
            'customer_id' => $customer->id,
            'sale_by' => $admin->id,
            'total_quantity' => 3,
            'total_price' => 15000,
            'paid_amount' => 15000,
            'credit_amount' => 0,
            'sale_date' => '2026-09-05',
            'status' => 'completed',
        ]);
        SaleItem::query()->create([
            'sale_id' => $sale2->id,
            'product_unit_id' => $unit->id,
            'quantity' => 3,
            'price' => 5000,
        ]);

        $this->actingAs($admin, 'web');

        Livewire::test(SalesReport::class, ['from' => '2026-09-01', 'to' => '2026-09-30'])
            ->assertSet('periodMode', 'daily')
            ->assertSee('25,000') // Total Sales
            ->assertSee('23,000') // Paid
            ->assertSee('2,000')  // Credit
            ->set('from', '2026-09-01')
            ->set('to', '2026-09-02')
            ->assertSee('10,000')
            ->assertDontSee('15,000')
            ->set('from', '2026-09-04')
            ->set('to', '2026-09-06')
            ->assertSee('15,000')
            ->assertDontSee('10,000')
            ->set('from', '2026-09-01')
            ->set('to', '2026-09-30')
            ->call('setActiveTab', 'products')
            ->assertSee('Arabica Coffee')
            ->call('setActiveTab', 'breakdown')
            ->call('setPeriodMode', 'weekly')
            ->assertSet('periodMode', 'weekly')
            ->assertSee('Weekly Breakdown')
            ->call('setPeriodMode', 'monthly')
            ->assertSet('periodMode', 'monthly')
            ->assertSee('September 2026');
    }

    public function test_livewire_sales_report_can_export_csv(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        Sale::query()->create([
            'voucher_code' => 'V-001',
            'sale_by' => $admin->id,
            'total_quantity' => 1,
            'total_price' => 5000,
            'paid_amount' => 5000,
            'credit_amount' => 0,
            'sale_date' => '2026-09-10',
            'status' => 'completed',
        ]);

        $this->actingAs($admin, 'web');

        $response = Livewire::test(SalesReport::class, ['from' => '2026-09-01', 'to' => '2026-09-30'])
            ->call('exportCsv');

        $response->assertFileDownloaded();
    }

    public function test_livewire_sales_report_filters_by_cashier_and_customer(): void
    {
        $cashier1 = User::query()->create(['name' => 'Cashier One', 'user_code' => 'C-01', 'email' => 'c1@example.com', 'password' => 'secret', 'prohibition_flag' => false]);
        $cashier2 = User::query()->create(['name' => 'Cashier Two', 'user_code' => 'C-02', 'email' => 'c2@example.com', 'password' => 'secret', 'prohibition_flag' => false]);
        $customer1 = PosCustomer::query()->create(['name' => 'Customer Alpha', 'phone' => '09111', 'created_by' => $cashier1->id]);
        $customer2 = PosCustomer::query()->create(['name' => 'Customer Beta', 'phone' => '09222', 'created_by' => $cashier1->id]);

        Sale::query()->create([
            'voucher_code' => 'V-C1',
            'sale_by' => $cashier1->id,
            'customer_id' => $customer1->id,
            'total_quantity' => 1,
            'total_price' => 7000,
            'paid_amount' => 7000,
            'credit_amount' => 0,
            'sale_date' => '2026-09-15',
            'status' => 'completed',
        ]);

        Sale::query()->create([
            'voucher_code' => 'V-C2',
            'sale_by' => $cashier2->id,
            'customer_id' => $customer2->id,
            'total_quantity' => 1,
            'total_price' => 3000,
            'paid_amount' => 3000,
            'credit_amount' => 0,
            'sale_date' => '2026-09-16',
            'status' => 'completed',
        ]);

        $this->actingAs($cashier1, 'web');

        Livewire::test(SalesReport::class, ['from' => '2026-09-01', 'to' => '2026-09-30'])
            ->assertSee('10,000')
            ->set('sellerId', (string) $cashier1->id)
            ->assertSee('7,000')
            ->set('sellerId', (string) $cashier2->id)
            ->assertSee('3,000')
            ->set('sellerId', '')
            ->set('customerId', (string) $customer1->id)
            ->assertSee('7,000')
            ->set('customerId', (string) $customer2->id)
            ->assertSee('3,000');
    }
}
