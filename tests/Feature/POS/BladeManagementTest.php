<?php

namespace Tests\Feature\POS;

use App\Models\User;
use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Expense\Models\PosExpense;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\ProductUnitConversion;
use App\Modules\Inventory\Models\ProductUnitRelation;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Order\Models\PosOrder;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BladeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_area_requires_admin_authentication(): void
    {
        $this->get('/management')->assertRedirect('/login');
    }

    public function test_inactive_admin_and_invalid_password_cannot_sign_in(): void
    {
        User::query()->create(['name' => 'Inactive Admin', 'user_code' => 'inactive-admin', 'email' => 'inactive@example.com', 'password' => 'secret-password', 'prohibition_flag' => true]);
        User::query()->create(['name' => 'Active Admin', 'user_code' => 'active-admin', 'email' => 'active@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);

        $this->from('/login')->post('/login', ['user_code' => 'inactive-admin', 'email' => 'inactive@example.com', 'password' => 'secret-password'])->assertRedirect('/login')->assertSessionHasErrors('credentials');
        $this->from('/login')->post('/login', ['user_code' => 'active-admin', 'email' => 'active@example.com', 'password' => 'wrong-password'])->assertRedirect('/login')->assertSessionHasErrors('credentials');
        $this->assertGuest('web');
    }

    public function test_admin_can_logout_and_session_is_invalidated(): void
    {
        User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);

        $this->post('/login', ['user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password'])->assertRedirect('/management');
        $this->assertAuthenticated('web');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest('web');
        $this->get('/management')->assertRedirect('/login');
    }

    public function test_unit_conversion_is_rejected_when_source_stock_is_insufficient(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $from = ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'BAG', 'original_code' => 'BAG', 'name' => 'Bag', 'current_quantity' => 1, 'reorder_quantity' => 1, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);
        $to = ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'PACK', 'original_code' => 'PACK', 'name' => 'Pack', 'current_quantity' => 2, 'reorder_quantity' => 1, 'normal_sale_price' => 20, 'whole_sale_price' => 18, 'order_price' => 16, 'purchase_price' => 14]);
        ProductUnitRelation::query()->create(['product_id' => $product->id, 'from_unit_id' => $from->id, 'to_unit_id' => $to->id, 'quantity' => 10]);

        $this->actingAs($admin, 'web')->post('/management/product-unit-relations/convert', ['product_id' => $product->id, 'from_unit_id' => $from->id, 'to_unit_id' => $to->id, 'quantity' => 2])->assertStatus(422)->assertSee('Not enough quantity');
        $this->assertSame(1, $from->fresh()->current_quantity);
        $this->assertSame(2, $to->fresh()->current_quantity);
        $this->assertDatabaseCount('product_unit_conversions', 0);
    }

    public function test_order_history_can_be_filtered_by_status(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $customer = PosCustomer::query()->create(['name' => 'Customer One', 'phone' => '0912345678', 'created_by' => $admin->id]);
        PosOrder::query()->create(['order_number' => 'ORD-PENDING', 'customer_id' => $customer->id, 'address' => 'Main Street', 'order_date' => '2026-09-10 10:00:00', 'total_quantity' => 1, 'estimated_price' => 100, 'status' => 'pending']);
        PosOrder::query()->create(['order_number' => 'ORD-DELIVERED', 'customer_id' => $customer->id, 'address' => 'Main Street', 'order_date' => '2026-09-10 11:00:00', 'total_quantity' => 1, 'estimated_price' => 100, 'status' => 'delivered', 'delivery_by' => $admin->id, 'delivered_date' => '2026-09-11 12:00:00']);

        $this->actingAs($admin, 'web')->get('/management/orders?status=delivered')->assertOk()->assertSee('ORD-DELIVERED')->assertDontSee('ORD-PENDING');
    }

    public function test_expense_ledger_can_be_filtered_by_title_or_type(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        PosExpense::query()->create(['type' => 'Utilities', 'period' => 'September', 'expense_date' => '2026-09-10', 'title' => 'Electricity bill', 'amount' => 100, 'created_by' => $admin->id]);
        PosExpense::query()->create(['type' => 'Transport', 'period' => 'September', 'expense_date' => '2026-09-10', 'title' => 'Delivery fuel', 'amount' => 50, 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->get('/management/expenses?search=Electricity')->assertOk()->assertSee('Electricity bill')->assertDontSee('Delivery fuel');
        $this->actingAs($admin, 'web')->get('/management/expenses?search=Transport')->assertOk()->assertSee('Delivery fuel')->assertDontSee('Electricity bill');
    }

    public function test_product_list_can_be_filtered_by_category_and_search(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $otherCategory = ProductCategory::query()->create(['code' => 'CAT-02', 'name' => 'Drinks', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $otherSubcategory = ProductSubcategory::query()->create(['code' => 'SUB-02', 'name' => 'Cold Drinks', 'category_id' => $otherCategory->id]);
        Product::query()->create(['code' => 'RICE-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        Product::query()->create(['code' => 'COLA-01', 'name' => 'Cola', 'category_id' => $otherCategory->id, 'subcategory_id' => $otherSubcategory->id, 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->get('/management/products?category_id='.$category->id)->assertOk()->assertSee('Rice')->assertDontSee('Cola');
        $this->actingAs($admin, 'web')->get('/management/products?search=Cola')->assertOk()->assertSee('Cola')->assertDontSee('Rice');
    }

    public function test_customer_list_can_be_filtered_by_name_or_phone(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        PosCustomer::query()->create(['name' => 'Aung Aung', 'phone' => '0911111111', 'customer_level' => 1, 'created_by' => $admin->id]);
        PosCustomer::query()->create(['name' => 'Ma Ma', 'phone' => '0922222222', 'customer_level' => 1, 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->get('/management/customers?search=Aung')->assertOk()->assertSee('Aung Aung')->assertDontSee('Ma Ma');
        $this->actingAs($admin, 'web')->get('/management/customers?search=0922222222')->assertOk()->assertSee('Ma Ma')->assertDontSee('Aung Aung');
    }

    public function test_counting_unit_list_can_be_filtered_by_product(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $rice = Product::query()->create(['code' => 'RICE-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $oil = Product::query()->create(['code' => 'OIL-01', 'name' => 'Cooking Oil', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        ProductUnit::query()->create(['product_id' => $rice->id, 'code' => 'RICE-BAG', 'original_code' => 'RICE-BAG', 'name' => 'Rice Bag', 'current_quantity' => 5, 'reorder_quantity' => 1, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);
        ProductUnit::query()->create(['product_id' => $oil->id, 'code' => 'OIL-BOTTLE', 'original_code' => 'OIL-BOTTLE', 'name' => 'Oil Bottle', 'current_quantity' => 5, 'reorder_quantity' => 1, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);

        $this->actingAs($admin, 'web')->get('/management/product-units?product_id='.$rice->id)->assertOk()->assertSee('Rice Bag')->assertDontSee('Oil Bottle');
    }

    public function test_subcategory_list_can_be_filtered_by_category(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $groceries = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $drinks = ProductCategory::query()->create(['code' => 'CAT-02', 'name' => 'Drinks', 'created_by' => $admin->id]);
        ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $groceries->id]);
        ProductSubcategory::query()->create(['code' => 'SUB-02', 'name' => 'Cold Drinks', 'category_id' => $drinks->id]);

        $this->actingAs($admin, 'web')->get('/management/product-subcategories?category_id='.$groceries->id)->assertOk()->assertSee('Staples')->assertDontSee('Cold Drinks');
    }

    public function test_category_list_can_be_searched_by_name_or_code(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        ProductCategory::query()->create(['code' => 'GROC-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        ProductCategory::query()->create(['code' => 'DRINK-01', 'name' => 'Drinks', 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->get('/management/product-categories?search=Groceries')->assertOk()->assertSee('Groceries')->assertDontSee('Drinks');
        $this->actingAs($admin, 'web')->get('/management/product-categories?search=DRINK-01')->assertOk()->assertSee('Drinks')->assertDontSee('Groceries');
    }

    public function test_sale_history_displays_vouchers_and_customer_names(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $customer = PosCustomer::query()->create(['name' => 'Aung Aung', 'phone' => '0911111111', 'customer_level' => 1, 'created_by' => $admin->id]);
        Sale::query()->create(['voucher_code' => 'VOU-HISTORY-01', 'customer_id' => $customer->id, 'sale_by' => $admin->id, 'total_quantity' => 2, 'total_price' => 300, 'paid_amount' => 300, 'credit_amount' => 0, 'sale_date' => '2026-09-10']);

        $this->actingAs($admin, 'web')->get('/management/sales')->assertOk()->assertSee('VOU-HISTORY-01')->assertSee('Aung Aung')->assertSee('300.00');
    }

    public function test_purchase_history_displays_supplier_and_totals(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        Purchase::query()->create(['supplier_name' => 'Main Supplier', 'total_quantity' => 5, 'total_price' => 375, 'purchase_date' => '2026-09-10', 'purchase_by' => $admin->id]);

        $this->actingAs($admin, 'web')->get('/management/purchases')->assertOk()->assertSee('Main Supplier')->assertSee('375.00')->assertSee('5');
    }

    public function test_admin_can_sign_in_to_the_blade_management_area(): void
    {
        User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        $this->post('/login', [
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
        ])->assertRedirect('/management');

        $this->get('/management')->assertOk()->assertSee('Operations dashboard');
    }

    public function test_authenticated_admin_can_create_a_product_category_from_blade(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        $this->actingAs($admin, 'web')
            ->post('/management/product-categories', ['code' => 'CAT-01', 'name' => 'Dry Goods'])
            ->assertRedirect('/management/product-categories');

        $this->assertDatabaseHas('product_categories', ['code' => 'CAT-01', 'name' => 'Dry Goods', 'created_by' => $admin->id]);
        $this->assertInstanceOf(ProductCategory::class, ProductCategory::query()->first());
    }

    public function test_sale_workflow_reduces_stock_and_records_customer_credit(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);
        $customer = PosCustomer::query()->create([
            'name' => 'Walk-in Customer',
            'phone' => '0912345678',
            'allow_credit' => true,
            'customer_level' => 1,
            'created_by' => $admin->id,
        ]);
        $category = ProductCategory::query()->create([
            'code' => 'CAT-01',
            'name' => 'Groceries',
            'created_by' => $admin->id,
        ]);
        $subcategory = ProductSubcategory::query()->create([
            'code' => 'SUB-01',
            'name' => 'Staples',
            'category_id' => $category->id,
        ]);
        $product = Product::query()->create([
            'code' => 'ITEM-01',
            'name' => 'Rice',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'customer_visible' => true,
            'created_by' => $admin->id,
        ]);
        $unit = ProductUnit::query()->create([
            'product_id' => $product->id,
            'code' => 'UNIT-01',
            'original_code' => 'UNIT-01',
            'name' => 'Bag',
            'current_quantity' => 10,
            'reorder_quantity' => 2,
            'normal_sale_price' => 100,
            'whole_sale_price' => 90,
            'order_price' => 80,
            'purchase_price' => 70,
        ]);

        $response = $this->actingAs($admin, 'web')->post('/management/sales', [
            'customer_id' => $customer->id,
            'sale_date' => '2026-09-10',
            'paid_amount' => 50,
            'repayment_date' => '2026-09-20',
            'unit' => [$unit->id],
            'qty' => [2],
            'price' => [100],
        ]);

        $sale = Sale::query()->firstOrFail();
        $response->assertRedirect('/management/sales/'.$sale->id);
        $this->assertSame(8, $unit->fresh()->current_quantity);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total_quantity' => 2, 'total_price' => 200, 'paid_amount' => 50, 'credit_amount' => 150]);
        $this->assertDatabaseHas('customer_credit_records', ['customer_id' => $customer->id, 'sale_id' => $sale->id, 'amount' => 150]);
        $this->assertInstanceOf(CustomerCreditRecord::class, CustomerCreditRecord::query()->first());
    }

    public function test_purchase_workflow_increases_stock_and_updates_purchase_price(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create([
            'code' => 'ITEM-01',
            'name' => 'Rice',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'customer_visible' => true,
            'created_by' => $admin->id,
        ]);
        $unit = ProductUnit::query()->create([
            'product_id' => $product->id,
            'code' => 'UNIT-01',
            'original_code' => 'UNIT-01',
            'name' => 'Bag',
            'current_quantity' => 3,
            'reorder_quantity' => 2,
            'normal_sale_price' => 100,
            'whole_sale_price' => 90,
            'order_price' => 80,
            'purchase_price' => 70,
        ]);

        $response = $this->actingAs($admin, 'web')->post('/management/purchases', [
            'supplier_name' => 'Main Supplier',
            'purchase_date' => '2026-09-10',
            'unit' => [$unit->id],
            'qty' => [7],
            'price' => [75],
        ]);

        $purchase = Purchase::query()->firstOrFail();
        $response->assertRedirect('/management/purchases');
        $this->assertSame(10, $unit->fresh()->current_quantity);
        $this->assertSame('75.00', $unit->fresh()->purchase_price);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'supplier_name' => 'Main Supplier', 'total_quantity' => 7, 'total_price' => 525]);
        $this->assertDatabaseHas('purchase_items', ['purchase_id' => $purchase->id, 'product_unit_id' => $unit->id, 'quantity' => 7, 'price' => 75]);
    }

    public function test_order_workflow_creates_items_and_records_delivery_admin(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);
        $customer = PosCustomer::query()->create(['name' => 'Customer One', 'phone' => '0912345678', 'created_by' => $admin->id]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $unit = ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'UNIT-01', 'original_code' => 'UNIT-01', 'name' => 'Bag', 'current_quantity' => 10, 'reorder_quantity' => 2, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);

        $response = $this->actingAs($admin, 'web')->post('/management/orders', [
            'customer_id' => $customer->id,
            'address' => 'No. 1 Main Street',
            'phone' => '0912345678',
            'order_date' => '2026-09-10 10:00:00',
            'unit' => [$unit->id],
            'qty' => [2],
            'price' => [100],
        ]);

        $order = PosOrder::query()->firstOrFail();
        $response->assertRedirect('/management/orders/'.$order->id);
        $this->assertDatabaseHas('pos_orders', ['id' => $order->id, 'customer_id' => $customer->id, 'total_quantity' => 2, 'estimated_price' => 200, 'status' => 'pending']);
        $this->assertDatabaseHas('pos_order_items', ['order_id' => $order->id, 'product_unit_id' => $unit->id, 'quantity' => 2]);

        $this->actingAs($admin, 'web')->put('/management/orders/'.$order->id.'/status', ['status' => 'delivered', 'delivered_date' => '2026-09-11 15:00:00'])->assertRedirect();
        $this->assertDatabaseHas('pos_orders', ['id' => $order->id, 'status' => 'delivered', 'delivery_by' => $admin->id]);
    }

    public function test_expense_workflow_records_ledger_entry_for_authenticated_admin(): void
    {
        $admin = User::query()->create([
            'name' => 'POS Admin',
            'user_code' => 'pos-admin',
            'email' => 'pos-admin@example.com',
            'password' => 'secret-password',
            'prohibition_flag' => false,
        ]);

        $this->actingAs($admin, 'web')->post('/management/expenses', [
            'type' => 'Utilities',
            'period' => 'September 2026',
            'expense_date' => '2026-09-10',
            'title' => 'Electricity bill',
            'description' => 'Shop electricity',
            'amount' => 125.50,
        ])->assertRedirect('/management/expenses');

        $expense = PosExpense::query()->firstOrFail();
        $this->assertDatabaseHas('pos_expenses', ['id' => $expense->id, 'type' => 'Utilities', 'title' => 'Electricity bill', 'amount' => 125.50, 'is_profit' => false, 'created_by' => $admin->id]);
        $this->actingAs($admin, 'web')->get('/management/expenses')->assertOk()->assertSee('Electricity bill')->assertSee('125.50');
    }

    public function test_unit_conversion_moves_stock_and_records_conversion_snapshot(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $from = ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'BAG', 'original_code' => 'BAG', 'name' => 'Bag', 'current_quantity' => 5, 'reorder_quantity' => 1, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);
        $to = ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'PACK', 'original_code' => 'PACK', 'name' => 'Pack', 'current_quantity' => 2, 'reorder_quantity' => 1, 'normal_sale_price' => 20, 'whole_sale_price' => 18, 'order_price' => 16, 'purchase_price' => 14]);
        ProductUnitRelation::query()->create(['product_id' => $product->id, 'from_unit_id' => $from->id, 'to_unit_id' => $to->id, 'quantity' => 10]);

        $this->actingAs($admin, 'web')->post('/management/product-unit-relations/convert', [
            'product_id' => $product->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'quantity' => 2,
        ])->assertRedirect('/management/product-unit-relations?product_id='.$product->id);

        $this->assertSame(3, $from->fresh()->current_quantity);
        $this->assertSame(22, $to->fresh()->current_quantity);
        $this->assertDatabaseHas('product_unit_conversions', ['product_id' => $product->id, 'from_unit_id' => $from->id, 'from_unit_quantity' => 3, 'to_unit_id' => $to->id, 'to_unit_quantity' => 22]);
        $this->assertInstanceOf(ProductUnitConversion::class, ProductUnitConversion::query()->first());
    }

    public function test_sale_is_rejected_when_stock_is_insufficient(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $unit = ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'UNIT-01', 'original_code' => 'UNIT-01', 'name' => 'Bag', 'current_quantity' => 1, 'reorder_quantity' => 1, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);

        $this->actingAs($admin, 'web')->post('/management/sales', [
            'sale_date' => '2026-09-10',
            'paid_amount' => 200,
            'unit' => [$unit->id],
            'qty' => [2],
            'price' => [100],
        ])->assertStatus(422)->assertSee('Not enough stock');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(1, $unit->fresh()->current_quantity);
    }

    public function test_credit_sale_is_rejected_for_customer_without_credit_permission(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $customer = PosCustomer::query()->create(['name' => 'Cash Customer', 'phone' => '0912345678', 'allow_credit' => false, 'created_by' => $admin->id]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $unit = ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'UNIT-01', 'original_code' => 'UNIT-01', 'name' => 'Bag', 'current_quantity' => 5, 'reorder_quantity' => 1, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);

        $this->actingAs($admin, 'web')->post('/management/sales', [
            'customer_id' => $customer->id,
            'sale_date' => '2026-09-10',
            'paid_amount' => 0,
            'unit' => [$unit->id],
            'qty' => [1],
            'price' => [100],
        ])->assertStatus(422)->assertSee('not allowed to buy on credit');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(5, $unit->fresh()->current_quantity);
    }

    public function test_product_photo_can_be_uploaded_and_removed_from_blade(): void
    {
        Storage::fake('public');
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);

        $response = $this->actingAs($admin, 'web')->post('/management/products/'.$product->id.'/photo', [
            'photo' => UploadedFile::fake()->image('rice.jpg'),
        ]);

        $product->refresh();
        $response->assertRedirect('/management/products/'.$product->id.'/photo');
        $this->assertNotNull($product->photo_path);
        Storage::disk('public')->assertExists($product->photo_path);

        $this->actingAs($admin, 'web')->delete('/management/products/'.$product->id.'/photo')->assertRedirect('/management/products/'.$product->id.'/photo');
        $this->assertNull($product->fresh()->photo_path);
        Storage::disk('public')->assertMissing($product->photo_path);
    }

    public function test_customer_can_be_created_and_updated_from_blade(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);

        $this->actingAs($admin, 'web')->post('/management/customers', [
            'name' => 'Retail Customer',
            'phone' => '0912345678',
            'address' => 'Main Street',
            'allow_credit' => '1',
            'customer_level' => 2,
        ])->assertRedirect('/management/customers');

        $customer = PosCustomer::query()->firstOrFail();
        $this->assertDatabaseHas('pos_customers', ['id' => $customer->id, 'name' => 'Retail Customer', 'allow_credit' => true, 'customer_level' => 2, 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->put('/management/customers/'.$customer->id, [
            'name' => 'Updated Customer',
            'phone' => '0999999999',
            'address' => 'Updated Address',
            'customer_level' => 3,
        ])->assertRedirect('/management/customers');

        $this->assertDatabaseHas('pos_customers', ['id' => $customer->id, 'name' => 'Updated Customer', 'phone' => '0999999999', 'allow_credit' => false, 'customer_level' => 3]);
    }

    public function test_product_can_be_created_updated_and_deleted_from_blade(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);

        $this->actingAs($admin, 'web')->post('/management/products', [
            'code' => 'ITEM-01',
            'name' => 'Rice',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'customer_visible' => '1',
        ])->assertRedirect('/management/products');

        $product = Product::query()->firstOrFail();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'code' => 'ITEM-01', 'name' => 'Rice', 'customer_visible' => true, 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->put('/management/products/'.$product->id, [
            'code' => 'ITEM-UPDATED',
            'name' => 'Premium Rice',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
        ])->assertRedirect('/management/products');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'code' => 'ITEM-UPDATED', 'name' => 'Premium Rice', 'customer_visible' => false]);

        $this->actingAs($admin, 'web')->delete('/management/products/'.$product->id)->assertRedirect('/management/products');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_counting_unit_can_be_created_updated_and_deleted_from_blade(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $unitData = ['product_id' => $product->id, 'name' => 'Bag', 'code' => 'BAG', 'original_code' => 'BAG', 'current_quantity' => 10, 'reorder_quantity' => 2, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70];

        $this->actingAs($admin, 'web')->post('/management/product-units', $unitData)->assertRedirect('/management/product-units');
        $unit = ProductUnit::query()->firstOrFail();
        $this->assertDatabaseHas('product_units', ['id' => $unit->id, 'product_id' => $product->id, 'name' => 'Bag', 'current_quantity' => 10, 'normal_sale_price' => 100]);

        $this->actingAs($admin, 'web')->put('/management/product-units/'.$unit->id, [...$unitData, 'name' => 'Large Bag', 'current_quantity' => 12, 'normal_sale_price' => 120])->assertRedirect('/management/product-units');
        $this->assertDatabaseHas('product_units', ['id' => $unit->id, 'name' => 'Large Bag', 'current_quantity' => 12, 'normal_sale_price' => 120]);

        $this->actingAs($admin, 'web')->delete('/management/product-units/'.$unit->id)->assertRedirect('/management/product-units');
        $this->assertSoftDeleted('product_units', ['id' => $unit->id]);
    }

    public function test_subcategory_crud_and_relationship_delete_protection_work_from_blade(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->post('/management/product-subcategories', ['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id])->assertRedirect('/management/product-subcategories');
        $subcategory = ProductSubcategory::query()->firstOrFail();
        $this->assertDatabaseHas('product_subcategories', ['id' => $subcategory->id, 'name' => 'Staples', 'category_id' => $category->id]);

        $this->actingAs($admin, 'web')->put('/management/product-subcategories/'.$subcategory->id, ['code' => 'SUB-UPDATED', 'name' => 'Premium Staples', 'category_id' => $category->id])->assertRedirect('/management/product-subcategories');
        $this->assertDatabaseHas('product_subcategories', ['id' => $subcategory->id, 'code' => 'SUB-UPDATED', 'name' => 'Premium Staples']);

        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $this->actingAs($admin, 'web')->delete('/management/product-subcategories/'.$subcategory->id)->assertStatus(422)->assertSee('Move products before deleting');
        $this->assertDatabaseHas('product_subcategories', ['id' => $subcategory->id]);
        $emptySubcategory = ProductSubcategory::query()->create(['code' => 'SUB-02', 'name' => 'Empty', 'category_id' => $category->id]);
        $this->actingAs($admin, 'web')->delete('/management/product-subcategories/'.$emptySubcategory->id)->assertRedirect('/management/product-subcategories');
        $this->assertDatabaseMissing('product_subcategories', ['id' => $emptySubcategory->id]);
    }

    public function test_financial_report_filters_and_calculates_period_totals(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        Sale::query()->create(['voucher_code' => 'VOU-01', 'sale_by' => $admin->id, 'total_quantity' => 3, 'total_price' => 500, 'paid_amount' => 500, 'credit_amount' => 0, 'sale_date' => '2026-09-10']);
        Sale::query()->create(['voucher_code' => 'VOU-02', 'sale_by' => $admin->id, 'total_quantity' => 1, 'total_price' => 900, 'paid_amount' => 900, 'credit_amount' => 0, 'sale_date' => '2026-08-31']);
        Purchase::query()->create(['supplier_name' => 'Supplier', 'total_quantity' => 4, 'total_price' => 200, 'purchase_date' => '2026-09-10', 'purchase_by' => $admin->id]);
        PosExpense::query()->create(['type' => 'Utilities', 'period' => 'September', 'expense_date' => '2026-09-10', 'title' => 'Electricity', 'amount' => 50, 'is_profit' => false, 'created_by' => $admin->id]);
        PosExpense::query()->create(['type' => 'Utilities', 'period' => 'August', 'expense_date' => '2026-08-31', 'title' => 'Old electricity', 'amount' => 75, 'is_profit' => false, 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->get('/management/reports/financial?from=2026-09-01&to=2026-09-30')
            ->assertOk()
            ->assertSee('500.00')
            ->assertSee('200.00')
            ->assertSee('50.00')
            ->assertSee('250.00')
            ->assertSee('VOU-01')
            ->assertDontSee('VOU-02');
    }

    public function test_operations_dashboard_displays_totals_stock_value_and_low_stock_items(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'UNIT-01', 'original_code' => 'UNIT-01', 'name' => 'Bag', 'current_quantity' => 3, 'reorder_quantity' => 5, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);
        Sale::query()->create(['voucher_code' => 'VOU-01', 'sale_by' => $admin->id, 'total_quantity' => 2, 'total_price' => 300, 'paid_amount' => 300, 'credit_amount' => 0, 'sale_date' => '2026-09-10']);
        Purchase::query()->create(['supplier_name' => 'Supplier', 'total_quantity' => 4, 'total_price' => 200, 'purchase_date' => '2026-09-10', 'purchase_by' => $admin->id]);
        PosExpense::query()->create(['type' => 'Utilities', 'period' => 'September', 'expense_date' => '2026-09-10', 'title' => 'Electricity', 'amount' => 50, 'is_profit' => false, 'created_by' => $admin->id]);

        $this->actingAs($admin, 'web')->get('/management')
            ->assertOk()
            ->assertSee('300.00')
            ->assertSee('200.00')
            ->assertSee('50.00')
            ->assertSee('210.00')
            ->assertSee('Rice')
            ->assertSee('Low stock');
    }

    public function test_category_delete_is_protected_when_subcategories_exist(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);

        $this->actingAs($admin, 'web')->delete('/management/product-categories/'.$category->id)
            ->assertStatus(422)
            ->assertSee('Delete subcategories before deleting this category');
        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);

        $emptyCategory = ProductCategory::query()->create(['code' => 'CAT-02', 'name' => 'Empty', 'created_by' => $admin->id]);
        $this->actingAs($admin, 'web')->delete('/management/product-categories/'.$emptyCategory->id)->assertRedirect('/management/product-categories');
        $this->assertSoftDeleted('product_categories', ['id' => $emptyCategory->id]);
    }

    public function test_stock_dashboard_updates_quantity_reorder_and_prices(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'prohibition_flag' => false]);
        $category = ProductCategory::query()->create(['code' => 'CAT-01', 'name' => 'Groceries', 'created_by' => $admin->id]);
        $subcategory = ProductSubcategory::query()->create(['code' => 'SUB-01', 'name' => 'Staples', 'category_id' => $category->id]);
        $product = Product::query()->create(['code' => 'ITEM-01', 'name' => 'Rice', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $admin->id]);
        $unit = ProductUnit::query()->create(['product_id' => $product->id, 'code' => 'UNIT-01', 'original_code' => 'UNIT-01', 'name' => 'Bag', 'current_quantity' => 3, 'reorder_quantity' => 5, 'normal_sale_price' => 100, 'whole_sale_price' => 90, 'order_price' => 80, 'purchase_price' => 70]);

        $this->actingAs($admin, 'web')->put('/management/stock/'.$unit->id, [
            'current_quantity' => 20,
            'reorder_quantity' => 4,
            'purchase_price' => 75,
            'normal_sale_price' => 110,
            'whole_sale_price' => 95,
            'order_price' => 85,
        ])->assertRedirect('/management/stock');

        $this->assertDatabaseHas('product_units', ['id' => $unit->id, 'current_quantity' => 20, 'reorder_quantity' => 4, 'purchase_price' => 75, 'normal_sale_price' => 110, 'whole_sale_price' => 95, 'order_price' => 85]);
    }

    public function test_user_management_screen_and_livewire_component(): void
    {
        $admin = User::query()->create(['name' => 'POS Admin', 'user_code' => 'pos-admin', 'email' => 'pos-admin@example.com', 'password' => 'secret-password', 'role' => 'Admin', 'prohibition_flag' => false]);
        $cashier = User::query()->create(['name' => 'John Cashier', 'user_code' => 'EMP-01', 'email' => 'cashier@example.com', 'password' => 'secret-password', 'role' => 'Cashier', 'prohibition_flag' => false]);
        $manager = User::query()->create(['name' => 'Jane Manager', 'user_code' => 'EMP-02', 'email' => 'manager@example.com', 'password' => 'secret-password', 'role' => 'Manager', 'prohibition_flag' => false]);

        $this->actingAs($admin, 'web')->get('/management/users')
            ->assertOk()
            ->assertSee('Employees')
            ->assertSee('John Cashier')
            ->assertSee('Jane Manager');

        $this->actingAs($admin, 'web')->get('/management/users?search=Cashier')
            ->assertOk()
            ->assertSee('John Cashier')
            ->assertDontSee('Jane Manager');

        $this->actingAs($admin, 'web')->get('/management/users?role=Manager')
            ->assertOk()
            ->assertSee('Jane Manager')
            ->assertDontSee('John Cashier');

        \Livewire\Livewire::actingAs($admin, 'web')
            ->test(\App\Livewire\UserTable::class)
            ->set('createCode', 'EMP-03')
            ->set('createName', 'Bob Staff')
            ->set('createEmail', 'bob@example.com')
            ->set('createRole', 'Staff')
            ->set('createPassword', 'password123')
            ->call('createUser')
            ->assertDispatched('toast', message: 'Employee created.');

        $this->assertDatabaseHas('users', [
            'user_code' => 'EMP-03',
            'name' => 'Bob Staff',
            'email' => 'bob@example.com',
            'role' => 'Staff',
            'prohibition_flag' => false,
        ]);

        $bob = User::query()->where('user_code', 'EMP-03')->firstOrFail();

        \Livewire\Livewire::actingAs($admin, 'web')
            ->test(\App\Livewire\UserTable::class)
            ->call('toggleStatus', $bob->id)
            ->assertDispatched('toast', message: 'Employee prohibited.');

        $this->assertTrue($bob->fresh()->prohibition_flag);

        \Livewire\Livewire::actingAs($admin, 'web')
            ->test(\App\Livewire\UserTable::class)
            ->call('toggleStatus', $bob->id)
            ->assertDispatched('toast', message: 'Employee activated.');

        $this->assertFalse($bob->fresh()->prohibition_flag);

        \Livewire\Livewire::actingAs($admin, 'web')
            ->test(\App\Livewire\UserTable::class)
            ->call('editUser', $bob->id)
            ->set('editName', 'Bob Senior Staff')
            ->set('editRole', 'Senior Staff')
            ->call('updateUser')
            ->assertDispatched('toast', message: 'Employee updated.');

        $this->assertDatabaseHas('users', [
            'id' => $bob->id,
            'name' => 'Bob Senior Staff',
            'role' => 'Senior Staff',
        ]);

        $this->actingAs($admin, 'web')->get('/management/users/'.$bob->id)
            ->assertOk()
            ->assertSee('Bob Senior Staff')
            ->assertSee('EMP-03')
            ->assertSee('Recent sales processed');

        $this->actingAs($admin, 'web')->put('/management/users/'.$bob->id.'/status')
            ->assertRedirect();
        $this->assertTrue($bob->fresh()->prohibition_flag);

        $this->actingAs($admin, 'web')->put('/management/users/'.$bob->id, [
            'user_code' => 'EMP-03',
            'name' => 'Bob Lead Staff',
            'email' => 'bob@example.com',
            'phone' => '0912345678',
            'role' => 'Lead',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $bob->id,
            'name' => 'Bob Lead Staff',
            'phone' => '0912345678',
            'role' => 'Lead',
        ]);

        \Livewire\Livewire::actingAs($admin, 'web')
            ->test(\App\Livewire\UserDetail::class, ['user' => $bob->fresh()])
            ->call('toggleStatus')
            ->assertDispatched('toast', message: 'Employee activated.');

        $this->assertFalse($bob->fresh()->prohibition_flag);

        \Livewire\Livewire::actingAs($admin, 'web')
            ->test(\App\Livewire\UserDetail::class, ['user' => $bob->fresh()])
            ->call('toggleStatus')
            ->assertDispatched('toast', message: 'Employee prohibited.');

        $this->assertTrue($bob->fresh()->prohibition_flag);

        \Livewire\Livewire::actingAs($admin, 'web')
            ->test(\App\Livewire\UserDetail::class, ['user' => $bob->fresh()])
            ->set('name', 'Bob Principal Staff')
            ->call('updateUser')
            ->assertDispatched('toast', message: 'Employee updated.');

        $this->assertDatabaseHas('users', [
            'id' => $bob->id,
            'name' => 'Bob Principal Staff',
        ]);
    }

    public function test_change_password_screen_and_password_update(): void
    {
        $user = User::query()->create([
            'name' => 'Password User',
            'user_code' => 'pass-user',
            'email' => 'pass@example.com',
            'password' => 'secret-old-pass',
            'role' => 'Cashier',
            'prohibition_flag' => false,
        ]);

        $this->get('/change-password')->assertRedirect('/login');

        $this->actingAs($user, 'web')->get('/change-password')
            ->assertOk()
            ->assertSee('Change password')
            ->assertSee('Current password')
            ->assertSee('New password')
            ->assertSee('Password User');

        $this->actingAs($user, 'web')->put('/change-password', [
            'current_password' => 'wrong-pass',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user, 'web')->put('/change-password', [
            'current_password' => 'secret-old-pass',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertSessionHas('status', 'Password updated successfully.');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-secret-123', $user->fresh()->password));
    }
}

