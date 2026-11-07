<?php

namespace Tests\Feature\POS;

use App\Livewire\CustomerTable;
use App\Livewire\PurchaseScreen;
use App\Livewire\SaleScreen;
use App\Livewire\SupplierTable;
use App\Models\User;
use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Purchase\Models\PosSupplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\SupplierPayment;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCreditAndSupplierTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $email = 'pos@example.com'): User
    {
        return User::query()->create([
            'name' => 'POS Manager',
            'user_code' => 'USR-'.uniqid(),
            'email' => $email,
            'password' => bcrypt('password'),
            'prohibition_flag' => false,
        ]);
    }

    private function productUnit(User $user, int $stock = 20, float $price = 1000): ProductUnit
    {
        $category = ProductCategory::query()->create(['name' => 'Cat-'.uniqid(), 'created_by' => $user->id]);
        $subcategory = ProductSubcategory::query()->create(['name' => 'Sub-'.uniqid(), 'category_id' => $category->id]);
        $product = Product::query()->create([
            'code' => 'PROD-'.uniqid(),
            'name' => 'Item-'.uniqid(),
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'created_by' => $user->id,
        ]);

        return ProductUnit::query()->create([
            'product_id' => $product->id,
            'name' => 'Piece',
            'current_quantity' => $stock,
            'reorder_quantity' => 2,
            'normal_sale_price' => $price,
            'whole_sale_price' => $price * 0.9,
            'order_price' => $price,
            'purchase_price' => $price * 0.7,
        ]);
    }

    public function test_customer_membership_levels_and_credit_limit_creation_and_update(): void
    {
        $user = $this->createUser();

        // Invalid customer level rejected
        $this->actingAs($user, 'web')
            ->post('/management/customers', [
                'name' => 'Invalid Level Customer',
                'customer_level' => 4,
                'allow_credit' => true,
                'credit_limit' => 50000,
            ])
            ->assertSessionHasErrors(['customer_level']);

        // Valid customer level 2 with credit limit
        $this->actingAs($user, 'web')
            ->post('/management/customers', [
                'name' => 'Silver VIP Customer',
                'customer_level' => 2,
                'allow_credit' => true,
                'credit_limit' => 200000,
            ])
            ->assertRedirect('/management/customers');

        $customer = PosCustomer::query()->where('name', 'Silver VIP Customer')->firstOrFail();
        $this->assertSame(2, $customer->customer_level);
        $this->assertTrue($customer->allow_credit);
        $this->assertEquals(200000.0, (float) $customer->credit_limit);
        $this->assertEquals(200000.0, $customer->availableCredit());
        $this->assertEquals(0.0, $customer->outstandingDebt());

        // Livewire CustomerTable creation with Level 3
        Livewire::actingAs($user)
            ->test(CustomerTable::class)
            ->set('createName', 'Gold VIP Customer')
            ->set('createCustomerLevel', 3)
            ->set('createAllowCredit', true)
            ->set('createCreditLimit', '500000')
            ->call('createCustomer')
            ->assertDispatched('toast');

        $goldCustomer = PosCustomer::query()->where('name', 'Gold VIP Customer')->firstOrFail();
        $this->assertSame(3, $goldCustomer->customer_level);
        $this->assertEquals(500000.0, (float) $goldCustomer->credit_limit);
    }

    public function test_sale_credit_limit_enforcement_rejects_exceeded_amount(): void
    {
        $user = $this->createUser();
        $unit = $this->productUnit($user, 100, 10000);

        $customer = PosCustomer::query()->create([
            'name' => 'Credit Customer',
            'customer_level' => 1,
            'allow_credit' => true,
            'credit_limit' => 50000,
            'created_by' => $user->id,
        ]);

        // Attempting to buy on credit for 60,000 (exceeding 50,000 limit) -> 422
        $response = $this->actingAs($user, 'web')
            ->post('/management/sales', [
                'customer_id' => $customer->id,
                'sale_date' => '2026-09-14',
                'paid_amount' => 0,
                'unit' => [$unit->id],
                'qty' => [6], // 6 * 10,000 = 60,000 total, 60,000 credit
                'price' => [10000],
            ]);

        $response->assertStatus(422);

        // Buying with partial payment so credit is 40,000 (within 50,000 limit) -> Succeeds
        $this->actingAs($user, 'web')
            ->post('/management/sales', [
                'customer_id' => $customer->id,
                'sale_date' => '2026-09-14',
                'paid_amount' => 20000,
                'unit' => [$unit->id],
                'qty' => [6], // 60,000 total - 20,000 paid = 40,000 credit
                'price' => [10000],
            ])
            ->assertRedirect();

        $this->assertEquals(40000.0, $customer->fresh()->outstandingDebt());
        $this->assertEquals(10000.0, $customer->fresh()->availableCredit());

        // A second sale for credit of 20,000 would result in 40,000 + 20,000 = 60,000 > 50,000 limit -> 422
        $this->actingAs($user, 'web')
            ->post('/management/sales', [
                'customer_id' => $customer->id,
                'sale_date' => '2026-09-14',
                'paid_amount' => 0,
                'unit' => [$unit->id],
                'qty' => [2], // 20,000 credit
                'price' => [10000],
            ])
            ->assertStatus(422);
    }

    public function test_sale_screen_livewire_credit_limit_enforcement(): void
    {
        $user = $this->createUser();
        $unit = $this->productUnit($user, 50, 20000);

        $customer = PosCustomer::query()->create([
            'name' => 'POS Credit Customer',
            'customer_level' => 1,
            'allow_credit' => true,
            'credit_limit' => 30000,
            'created_by' => $user->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(SaleScreen::class)
            ->set('customerId', (string) $customer->id)
            ->call('addUnit', $unit->id)
            ->call('addUnit', $unit->id) // 2 items = 40,000 total
            ->set('paidAmount', '0') // credit = 40,000 > 30,000 limit
            ->call('completeSale')
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains($params['message'] ?? '', 'Credit limit exceeded');
            });

        $this->assertSame(0, Sale::query()->count());

        // Now pay 15,000 tendered (credit = 25,000 <= 30,000 limit) -> Passes
        $component->set('paidAmount', '15000')
            ->call('completeSale')
            ->assertRedirect();

        $this->assertEquals(25000.0, $customer->fresh()->outstandingDebt());
    }

    public function test_supplier_crud_and_livewire_workflow(): void
    {
        $user = $this->createUser();

        Livewire::actingAs($user)
            ->test(SupplierTable::class)
            ->assertSee('No suppliers found.')
            ->set('createName', 'Ko Win Supplier')
            ->set('createCompany', 'Golden Yangon Distro')
            ->set('createPhone', '0999988877')
            ->set('createAddress', 'Bayintnaung Warehouse')
            ->call('createSupplier')
            ->assertDispatched('toast')
            ->assertSee('Ko Win Supplier')
            ->assertSee('Golden Yangon Distro')
            ->assertDontSee('No suppliers found.');

        $supplier = PosSupplier::query()->where('name', 'Ko Win Supplier')->firstOrFail();
        $this->assertSame('Golden Yangon Distro', $supplier->company_name);
        $this->assertSame('0999988877', $supplier->phone);

        // Edit supplier
        Livewire::actingAs($user)
            ->test(SupplierTable::class)
            ->call('editSupplier', $supplier->id)
            ->set('editCompany', 'Golden Yangon Logistics')
            ->call('updateSupplier')
            ->assertDispatched('toast');

        $this->assertSame('Golden Yangon Logistics', $supplier->fresh()->company_name);
    }

    public function test_purchase_with_supplier_payable_balance_and_repayment(): void
    {
        $user = $this->createUser();
        $unit = $this->productUnit($user, 10, 500);

        $supplier = PosSupplier::query()->create([
            'name' => 'U Ba Supplies',
            'company_name' => 'UB Trading',
            'created_by' => $user->id,
        ]);

        // Record purchase with partial payment
        // 10 units at 500 = 5,000 total. Paid 2,000 -> Payable 3,000
        $this->actingAs($user, 'web')
            ->post('/management/purchases', [
                'supplier_id' => $supplier->id,
                'purchase_date' => '2026-09-14',
                'paid_amount' => 2000,
                'unit' => [$unit->id],
                'qty' => [10],
                'price' => [500],
            ])
            ->assertRedirect('/management/purchases');

        $purchase = Purchase::query()->where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertEquals(5000.0, (float) $purchase->total_price);
        $this->assertEquals(2000.0, (float) $purchase->paid_amount);
        $this->assertEquals(3000.0, (float) $purchase->payable_amount);
        $this->assertSame('partial', $purchase->payment_status);

        $this->assertEquals(5000.0, $supplier->totalPurchased());
        $this->assertEquals(2000.0, $supplier->totalPaid());
        $this->assertEquals(3000.0, $supplier->outstandingPayable());

        // Record supplier payment to clear the payable balance
        $this->actingAs($user, 'web')
            ->post('/management/suppliers/'.$supplier->id.'/payments', [
                'purchase_id' => $purchase->id,
                'amount' => 3000,
                'payment_date' => '2026-09-15',
                'payment_method' => 'bank',
                'note' => 'KBZ pay clear',
            ])
            ->assertRedirect();

        $this->assertEquals(0.0, (float) $purchase->fresh()->payable_amount);
        $this->assertEquals(5000.0, (float) $purchase->fresh()->paid_amount);
        $this->assertSame('paid', $purchase->fresh()->payment_status);
        $this->assertEquals(0.0, $supplier->fresh()->outstandingPayable());

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id,
            'purchase_id' => $purchase->id,
            'amount' => 3000,
            'payment_method' => 'bank',
        ]);
    }

    public function test_supplier_general_payment_allocates_across_open_purchases_fifo(): void
    {
        $user = $this->createUser();
        $unit = $this->productUnit($user, 20, 1000);

        $supplier = PosSupplier::query()->create([
            'name' => 'General Supplier',
            'company_name' => 'GS Corp',
            'created_by' => $user->id,
        ]);

        // Purchase 1: 5,000 total, 73 paid -> 4,927 payable
        $p1 = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'purchase_date' => '2026-10-14',
            'total_quantity' => 5,
            'total_price' => 5000,
            'paid_amount' => 73,
            'payable_amount' => 4927,
            'payment_status' => 'partial',
            'purchase_by' => $user->id,
        ]);

        // Submit general payment without purchase_id (Amount = 927)
        $this->actingAs($user, 'web')
            ->post('/management/suppliers/'.$supplier->id.'/payments', [
                'purchase_id' => null,
                'amount' => 927,
                'payment_date' => '2026-09-19',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        // Check that purchase #1 payable was reduced from 4927 to 4000, paid increased from 73 to 1000
        $p1->refresh();
        $this->assertEquals(4000.0, (float) $p1->payable_amount);
        $this->assertEquals(1000.0, (float) $p1->paid_amount);
        $this->assertSame('partial', $p1->payment_status);
        $this->assertEquals(4000.0, $supplier->fresh()->outstandingPayable());

        // Submit another general payment to clear remaining 4000
        $this->actingAs($user, 'web')
            ->post('/management/suppliers/'.$supplier->id.'/payments', [
                'purchase_id' => null,
                'amount' => 4000,
                'payment_date' => '2026-09-19',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $p1->refresh();
        $this->assertEquals(0.0, (float) $p1->payable_amount);
        $this->assertEquals(5000.0, (float) $p1->paid_amount);
        $this->assertSame('paid', $p1->payment_status);
        $this->assertEquals(0.0, $supplier->fresh()->outstandingPayable());
    }

    public function test_purchase_screen_livewire_workflow(): void
    {
        $user = $this->createUser();
        $unit = $this->productUnit($user, 5, 2000); // 5 in stock, unit cost 1400 (70% of 2000)

        $supplier = PosSupplier::query()->create([
            'name' => 'Distro Supplier',
            'company_name' => 'Distro Wholesale',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(PurchaseScreen::class, ['selectedSupplierId' => $supplier->id])
            ->assertSee('Distro Supplier')
            ->call('addUnit', $unit->id)
            ->set('cart.'.$unit->id.'.qty', 10)
            ->set('cart.'.$unit->id.'.price', '1500')
            ->set('paidAmount', '5000') // total = 10 * 1500 = 15,000, paid = 5,000 -> payable = 10,000
            ->set('paymentDueDate', '2026-10-30')
            ->call('savePurchase')
            ->assertRedirect(route('management.purchases.index'));

        $purchase = Purchase::query()->where('supplier_id', $supplier->id)->latest('id')->firstOrFail();
        $this->assertEquals(15000.0, (float) $purchase->total_price);
        $this->assertEquals(5000.0, (float) $purchase->paid_amount);
        $this->assertEquals(10000.0, (float) $purchase->payable_amount);
        $this->assertSame('partial', $purchase->payment_status);
        $this->assertEquals('2026-10-30', $purchase->payment_due_date->format('Y-m-d'));

        // Stock quantity incremented from 5 to 15, purchase_price updated to 1500
        $unit->refresh();
        $this->assertSame(15, $unit->current_quantity);
        $this->assertEquals(1500.0, (float) $unit->purchase_price);
    }
}
