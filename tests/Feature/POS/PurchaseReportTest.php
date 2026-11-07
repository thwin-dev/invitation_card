<?php

namespace Tests\Feature\POS;

use App\Livewire\PurchaseReport;
use App\Models\User;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Purchase\Models\PosSupplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Models\SupplierPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_report_requires_authentication(): void
    {
        $this->get('/management/reports/purchases')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_purchase_report_page(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        $this->actingAs($admin, 'web')
            ->get('/management/reports/purchases')
            ->assertOk()
            ->assertSee('Purchase &amp; Supplier Report', false)
            ->assertSee('Daily')
            ->assertSee('Weekly')
            ->assertSee('Monthly')
            ->assertSee('Supplier Summary')
            ->assertSee('Purchased Products')
            ->assertSee('Purchase Bills');
    }

    public function test_livewire_purchase_report_calculates_aggregations_and_breakdowns(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        $supplier = PosSupplier::query()->create([
            'name' => 'Golden Coffee Importers',
            'company_name' => 'Golden Trading Ltd',
            'phone' => '09987654321',
            'address' => 'Yangon',
            'created_by' => $admin->id,
        ]);

        $category = ProductCategory::query()->create([
            'code' => 'CAT-01',
            'name' => 'Beverages',
            'created_by' => $admin->id,
        ]);

        $subcategory = ProductSubcategory::query()->create([
            'code' => 'SUB-01',
            'name' => 'Coffee Beans',
            'category_id' => $category->id,
        ]);

        $product = Product::query()->create([
            'code' => 'ITEM-01',
            'name' => 'Highland Arabica Beans',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'created_by' => $admin->id,
        ]);

        $unit = ProductUnit::query()->create([
            'product_id' => $product->id,
            'code' => 'UNIT-01',
            'original_code' => 'SKU-01',
            'name' => 'Sack (10kg)',
            'current_quantity' => 50,
            'reorder_quantity' => 10,
            'normal_sale_price' => 50000,
            'whole_sale_price' => 45000,
            'order_price' => 48000,
            'purchase_price' => 35000,
        ]);

        // Purchase 1: 2026-09-02 (Total 70,000, Paid 50,000, Payable 20,000)
        $purchase1 = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'total_quantity' => 2,
            'total_price' => 70000,
            'paid_amount' => 50000,
            'payable_amount' => 20000,
            'payment_status' => 'partial',
            'purchase_date' => '2026-09-02',
            'purchase_by' => $admin->id,
        ]);
        PurchaseItem::query()->create([
            'purchase_id' => $purchase1->id,
            'product_unit_id' => $unit->id,
            'quantity' => 2,
            'price' => 35000,
        ]);

        // Purchase 2: 2026-09-15 (Total 105,000, Paid 105,000, Payable 0)
        $purchase2 = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'total_quantity' => 3,
            'total_price' => 105000,
            'paid_amount' => 105000,
            'payable_amount' => 0,
            'payment_status' => 'paid',
            'purchase_date' => '2026-09-15',
            'purchase_by' => $admin->id,
        ]);
        PurchaseItem::query()->create([
            'purchase_id' => $purchase2->id,
            'product_unit_id' => $unit->id,
            'quantity' => 3,
            'price' => 35000,
        ]);

        // Disbursed supplier payment: 2026-09-20
        SupplierPayment::query()->create([
            'supplier_id' => $supplier->id,
            'purchase_id' => $purchase1->id,
            'amount' => 10000,
            'payment_date' => '2026-09-20',
            'payment_method' => 'cash',
            'paid_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'web');

        Livewire::test(PurchaseReport::class, ['from' => '2026-09-01', 'to' => '2026-09-30'])
            ->assertSet('periodMode', 'daily')
            ->assertSee('175,000') // Total purchases (70k + 105k)
            ->assertSee('155,000') // Total paid (50k + 105k)
            ->assertSee('20,000')  // Total payable
            ->assertSee('10,000')  // Disbursed payments
            ->set('from', '2026-09-01')
            ->set('to', '2026-09-05')
            ->assertSee('70,000')
            ->assertDontSee('105,000')
            ->set('from', '2026-09-10')
            ->set('to', '2026-09-20')
            ->assertSee('105,000')
            ->assertDontSee('70,000')
            ->set('from', '2026-09-01')
            ->set('to', '2026-09-30')
            ->call('setActiveTab', 'suppliers')
            ->assertSee('Golden Coffee Importers')
            ->call('setActiveTab', 'products')
            ->assertSee('Highland Arabica Beans')
            ->assertSee('Sack (10kg)')
            ->call('setActiveTab', 'invoices')
            ->assertSee('#PO-' . str_pad((string) $purchase1->id, 5, '0', STR_PAD_LEFT))
            ->assertSee('#PO-' . str_pad((string) $purchase2->id, 5, '0', STR_PAD_LEFT))
            ->call('setActiveTab', 'breakdown')
            ->call('setPeriodMode', 'weekly')
            ->assertSet('periodMode', 'weekly')
            ->assertSee('Weekly Breakdown')
            ->call('setPeriodMode', 'monthly')
            ->assertSet('periodMode', 'monthly')
            ->assertSee('September 2026');
    }

    public function test_livewire_purchase_report_filters_by_supplier_status_and_purchaser(): void
    {
        $staff1 = User::query()->create(['name' => 'Buyer Alice', 'user_code' => 'B-01', 'email' => 'b1@example.com', 'password' => 'secret', 'prohibition_flag' => false]);
        $staff2 = User::query()->create(['name' => 'Buyer Bob', 'user_code' => 'B-02', 'email' => 'b2@example.com', 'password' => 'secret', 'prohibition_flag' => false]);

        $supplierA = PosSupplier::query()->create(['name' => 'Supplier Alpha', 'created_by' => $staff1->id]);
        $supplierB = PosSupplier::query()->create(['name' => 'Supplier Beta', 'created_by' => $staff1->id]);

        Purchase::query()->create([
            'supplier_id' => $supplierA->id,
            'supplier_name' => $supplierA->name,
            'purchase_by' => $staff1->id,
            'total_quantity' => 5,
            'total_price' => 40000,
            'paid_amount' => 40000,
            'payable_amount' => 0,
            'payment_status' => 'paid',
            'purchase_date' => '2026-09-10',
        ]);

        Purchase::query()->create([
            'supplier_id' => $supplierB->id,
            'supplier_name' => $supplierB->name,
            'purchase_by' => $staff2->id,
            'total_quantity' => 10,
            'total_price' => 80000,
            'paid_amount' => 0,
            'payable_amount' => 80000,
            'payment_status' => 'unpaid',
            'purchase_date' => '2026-09-12',
        ]);

        $this->actingAs($staff1, 'web');

        Livewire::test(PurchaseReport::class, ['from' => '2026-09-01', 'to' => '2026-09-30'])
            ->assertSee('120,000') // Combined
            ->set('supplierId', (string) $supplierA->id)
            ->assertSee('40,000')
            ->assertDontSee('80,000')
            ->set('supplierId', (string) $supplierB->id)
            ->assertSee('80,000')
            ->assertDontSee('40,000')
            ->set('supplierId', '')
            ->set('paymentStatus', 'unpaid')
            ->assertSee('80,000')
            ->assertDontSee('40,000')
            ->set('paymentStatus', 'paid')
            ->assertSee('40,000')
            ->assertDontSee('80,000')
            ->set('paymentStatus', '')
            ->set('purchaserId', (string) $staff1->id)
            ->assertSee('40,000')
            ->assertDontSee('80,000')
            ->set('purchaserId', (string) $staff2->id)
            ->assertSee('80,000')
            ->assertDontSee('40,000');
    }

    public function test_livewire_purchase_report_can_export_csv(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        $supplier = PosSupplier::query()->create(['name' => 'Supplier Alpha', 'created_by' => $admin->id]);

        Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'purchase_by' => $admin->id,
            'total_quantity' => 5,
            'total_price' => 25000,
            'paid_amount' => 25000,
            'payable_amount' => 0,
            'payment_status' => 'paid',
            'purchase_date' => '2026-09-10',
        ]);

        $this->actingAs($admin, 'web');

        $test = Livewire::test(PurchaseReport::class, ['from' => '2026-09-01', 'to' => '2026-09-30']);

        // Test breakdown CSV export
        $test->call('exportCsv')->assertFileDownloaded();

        // Test suppliers CSV export
        $test->call('setActiveTab', 'suppliers')
            ->call('exportCsv')
            ->assertFileDownloaded();

        // Test products CSV export
        $test->call('setActiveTab', 'products')
            ->call('exportCsv')
            ->assertFileDownloaded();

        // Test invoices CSV export
        $test->call('setActiveTab', 'invoices')
            ->call('exportCsv')
            ->assertFileDownloaded();
    }
}
