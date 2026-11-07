<?php

namespace Tests\Feature\POS;

use App\Livewire\DiscountReport;
use App\Livewire\DiscountRuleTable;
use App\Livewire\SaleScreen;
use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Sale\Models\DiscountRule;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Services\SaleDiscounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private InventoryBranch $branch;

    private ProductUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::query()->create(['name' => 'Owner', 'user_code' => 'OWNER', 'email' => 'owner@example.test', 'role' => 'Owner', 'password' => 'secret', 'prohibition_flag' => false]);
        $this->actingAs($this->admin, 'web');
        $this->branch = InventoryBranch::query()->where('is_main', true)->firstOrFail();
        $category = ProductCategory::query()->create(['name' => 'Drinks', 'created_by' => $this->admin->id]);
        $subcategory = ProductSubcategory::query()->create(['name' => 'Coffee', 'category_id' => $category->id]);
        $product = Product::query()->create(['name' => 'Coffee', 'code' => 'COFFEE', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $this->admin->id]);
        $this->unit = ProductUnit::query()->create(['product_id' => $product->id, 'name' => 'Cup', 'current_quantity' => 30, 'normal_sale_price' => 100, 'purchase_price' => 50]);
    }

    private function rule(array $attributes = []): DiscountRule
    {
        return DiscountRule::query()->create(array_merge(['branch_id' => $this->branch->id, 'name' => 'Member saving', 'type' => 'percentage', 'value' => 10, 'is_active' => true], $attributes));
    }

    private function form(DiscountRule $rule): array
    {
        return ['branch_id' => $this->branch->id, 'sale_date' => now()->toDateString(), 'paid_amount' => 180,
            'unit' => [$this->unit->id], 'qty' => [2], 'price' => [100], 'discount_rule' => [$rule->id]];
    }

    public function test_rules_are_created_edited_toggled_and_permission_protected(): void
    {
        Livewire::test(DiscountRuleTable::class)->call('create')->set('branchId', (string) $this->branch->id)
            ->set('name', 'Shop promotion')->set('value', '101')->call('save')->assertHasErrors(['value'])
            ->set('value', '10')->call('save')->assertHasNoErrors()->assertDispatched('toast');
        $rule = DiscountRule::query()->firstOrFail();
        Livewire::test(DiscountRuleTable::class)->call('edit', $rule->id)->set('type', 'fixed')->set('value', '5.25')->call('save')
            ->call('toggle', $rule->id)->assertHasNoErrors();
        $this->assertDatabaseHas('discount_rules', ['id' => $rule->id, 'value' => 5.25, 'is_active' => false]);
        $this->assertDatabaseHas('activity_logs', ['module' => 'discounts', 'action' => 'created']);
        $cashier = User::query()->create(['name' => 'Cashier', 'user_code' => 'CASH', 'email' => 'cash@example.test', 'role' => 'Cashier', 'password' => 'secret']);
        $this->actingAs($cashier)->get('/management/discounts')->assertForbidden();
    }

    public function test_one_rule_can_be_assigned_to_multiple_branches(): void
    {
        $other = InventoryBranch::query()->create(['name' => 'Other', 'type' => 'shop', 'is_active' => true]);
        Livewire::test(DiscountRuleTable::class)->call('create')
            ->set('branchIds', [(string) $this->branch->id, (string) $other->id])
            ->set('name', 'Shared promotion')->set('type', 'percentage')->set('value', '15')->call('save')->assertHasNoErrors();

        $rule = DiscountRule::query()->where('name', 'Shared promotion')->firstOrFail();
        $this->assertDatabaseHas('discount_rule_branches', ['discount_rule_id' => $rule->id, 'branch_id' => $this->branch->id]);
        $this->assertDatabaseHas('discount_rule_branches', ['discount_rule_id' => $rule->id, 'branch_id' => $other->id]);
        $this->assertEquals(85, app(SaleDiscounts::class)->calculate($other, [
            ['unit_id' => $this->unit->id, 'qty' => 1, 'price' => 100],
        ])['total_price']);

        Livewire::test(DiscountRuleTable::class)->call('edit', $rule->id)
            ->assertSet('branchIds', [(string) $this->branch->id, (string) $other->id]);
    }

    public function test_money_rounding_and_fixed_amount_per_unit(): void
    {
        $percentage = $this->rule(['value' => 50]);
        $fixed = $this->rule(['type' => 'fixed', 'value' => 0.06]);
        $result = app(SaleDiscounts::class)->calculate($this->branch, [
            ['unit_id' => $this->unit->id, 'qty' => 1, 'price' => '0.05'],
            ['unit_id' => $this->unit->id, 'qty' => 3, 'price' => '0.10'],
        ]);
        $this->assertEquals(0.35, $result['subtotal']);
        $this->assertEquals(0.21, $result['discount_amount']);
        $this->assertEquals(0.14, $result['total_price']);
    }

    public function test_auto_discount_uses_only_active_rules_assigned_to_branch_and_item(): void
    {
        $other = InventoryBranch::query()->create(['name' => 'Other', 'type' => 'shop', 'is_active' => true]);
        $unit2 = $this->unit->replicate();
        $unit2->name = 'Bottle';
        $unit2->save();
        $this->rule(['branch_id' => $other->id, 'value' => 50]);
        $this->rule(['is_active' => false, 'value' => 50]);
        $this->rule(['product_unit_id' => $unit2->id, 'value' => 50]);
        $this->rule(['type' => 'fixed', 'value' => 101]);
        $result = app(SaleDiscounts::class)->calculate($this->branch, [
            ['unit_id' => $this->unit->id, 'qty' => 2, 'price' => 100],
        ]);
        $this->assertEquals(0, $result['discount_amount']);
        $this->assertSame(30, $this->unit->fresh()->current_quantity);
    }

    public function test_livewire_hold_resume_checkout_and_credit_use_net_total(): void
    {
        $rule = $this->rule();
        $customer = PosCustomer::query()->create(['name' => 'Member', 'allow_credit' => true, 'credit_limit' => 1000, 'created_by' => $this->admin->id]);
        $screen = Livewire::test(SaleScreen::class)->call('addUnit', $this->unit->id)
            ->set('cart.'.$this->unit->id.'.qty', 2)
            ->call('holdSale')->assertHasNoErrors();
        $held = Sale::query()->firstOrFail();
        $this->assertSame('180.00', $held->total_price);
        $screen->call('resumeHeldSale', $held->id)->assertSet('cart.'.$this->unit->id.'.discount_rule_id', (string) $rule->id)
            ->set('customerId', (string) $customer->id)->set('paidAmount', '100')->call('completeSale')->assertHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('sales', ['id' => $held->id, 'subtotal' => 200, 'discount_amount' => 20, 'total_price' => 180, 'credit_amount' => 80, 'status' => 'completed']);
        $this->assertDatabaseHas('customer_credit_records', ['sale_id' => $held->id, 'amount' => 80]);
        $this->assertSame(28, $this->unit->fresh()->current_quantity);
    }

    public function test_changing_branch_clears_selected_rules(): void
    {
        $rule = $this->rule();
        $other = InventoryBranch::query()->create(['name' => 'Other', 'type' => 'shop', 'is_active' => true]);
        Livewire::test(SaleScreen::class)->call('addUnit', $this->unit->id)
            ->set('cart.'.$this->unit->id.'.discount_rule_id', (string) $rule->id)
            ->set('branchId', (string) $other->id)->assertSet('cart.'.$this->unit->id.'.discount_rule_id', '');
    }

    public function test_saved_receipts_and_edits_preserve_the_original_rule_snapshot(): void
    {
        $rule = $this->rule();
        $this->post('/management/sales', $this->form($rule))->assertRedirect();
        $sale = Sale::query()->with('items')->firstOrFail();
        $item = $sale->items->first();
        $rule->update(['name' => 'Changed promotion', 'value' => 80, 'is_active' => false]);
        $this->get('/management/sales/'.$sale->id.'/print')->assertOk()->assertSee('Member saving')->assertSee('180.00')->assertDontSee('Changed promotion');
        $this->get('/management/sales/'.$sale->id)->assertOk()->assertSee('Subtotal')->assertSee('Item discounts');
        $this->get('/management/sales/'.$sale->id.'/edit')->assertOk()->assertSee('saved 10.00');
        $data = $this->form($rule);
        $data['item_id'] = [$item->id];
        $data['qty'] = [3];
        $data['paid_amount'] = 270;
        $this->put('/management/sales/'.$sale->id, $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'subtotal' => 300, 'discount_amount' => 30, 'total_price' => 270]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'discount_name' => 'Member saving', 'discount_value' => 10]);
    }

    public function test_branch_discount_report_excludes_cancelled_and_held_sales(): void
    {
        $rule = $this->rule();
        $this->post('/management/sales', $this->form($rule))->assertRedirect();
        $this->get('/management/reports/financial?tab=discounts')->assertOk()->assertSee('Discounts by branch');
        Livewire::test(DiscountReport::class)->assertViewHas('summaries', fn ($s) => $s->count() === 1 && (float) $s->first()->discounts === 20.0)
            ->set('from', 'not-a-date')->assertHasErrors(['from'])->assertSee('No discounted sales in this period.');
        Sale::query()->firstOrFail()->update(['status' => 'cancelled']);
        Livewire::test(DiscountReport::class)->assertViewHas('summaries', fn ($s) => $s->isEmpty());
    }

    public function test_branch_rules_produce_different_totals_and_report_separate_branches_and_currencies(): void
    {
        $mainRule = $this->rule();
        $this->post('/management/sales', $this->form($mainRule))->assertRedirect();
        $other = InventoryBranch::query()->create(['name' => 'Downtown', 'type' => 'shop', 'is_active' => true, 'currency_code' => 'USD']);
        InventoryLot::query()->create(['branch_id' => $other->id, 'product_unit_id' => $this->unit->id, 'quantity' => 10]);
        $otherRule = $this->rule(['branch_id' => $other->id, 'type' => 'fixed', 'value' => 25]);
        $data = $this->form($otherRule);
        $data['branch_id'] = $other->id;
        $data['paid_amount'] = 150;
        $this->post('/management/sales', $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales', ['branch_id' => $other->id, 'subtotal' => 200, 'discount_amount' => 50, 'total_price' => 150]);
        Livewire::test(DiscountReport::class)->assertViewHas('summaries', fn ($s) => $s->count() === 2)
            ->set('branchId', (string) $other->id)->assertViewHas('summaries', fn ($s) => $s->count() === 1 && (float) $s->first()->discounts === 50.0)
            ->assertViewHas('sales', fn ($sales) => $sales->count() === 1 && $sales->first()->branch_id === $other->id);
        Sale::query()->where('branch_id', $other->id)->firstOrFail()->update(['status' => 'held']);
        Livewire::test(DiscountReport::class)->set('branchId', (string) $other->id)->assertViewHas('summaries', fn ($s) => $s->isEmpty());
    }

    public function test_sales_without_discounts_and_fully_discounted_sales_remain_valid(): void
    {
        $data = $this->form($this->rule(['is_active' => false]));
        $data['discount_rule'] = [''];
        $data['paid_amount'] = 200;
        $this->post('/management/sales', $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales', ['subtotal' => 200, 'discount_amount' => 0, 'total_price' => 200]);

        $rule = $this->rule(['value' => 100]);
        $data = $this->form($rule);
        $data['discount_rule'] = [''];
        $data['paid_amount'] = 0;
        $this->post('/management/sales', $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales', ['subtotal' => 200, 'discount_amount' => 200, 'total_price' => 0, 'paid_amount' => 0, 'credit_amount' => 0]);
    }

    public function test_offline_discount_sync_validates_cached_rules_and_is_idempotent(): void
    {
        $rule = $this->rule();
        $catalog = $this->getJson('/management/sales/offline-catalog')->assertOk();
        $this->assertEquals($rule->id, $catalog->json('products.0.discount_rules.0.id'));
        $payload = ['sales' => [['client_transaction_id' => 'offline-discount-1', 'branch_id' => $this->branch->id, 'sale_date' => now()->toDateString(), 'paid_amount' => 180,
            'items' => [['unit_id' => $this->unit->id, 'qty' => 2, 'price' => 100, 'discount_rule_id' => $rule->id,
                'discount_amount' => 20, 'discount_rule_version' => $catalog->json('products.0.discount_rules.0.version')]]]]];
        $this->postJson('/management/sales/sync-offline', $payload)->assertOk()->assertJsonPath('count', 1);
        $this->postJson('/management/sales/sync-offline', $payload)->assertOk()->assertJsonPath('count', 1);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('sales', ['client_transaction_id' => 'offline-discount-1', 'subtotal' => 200, 'discount_amount' => 20, 'total_price' => 180]);
        $this->assertSame(28, $this->unit->fresh()->current_quantity);
        $payload['sales'][0]['client_transaction_id'] = 'offline-discount-stale';
        $rule->update(['value' => 20]);
        $this->postJson('/management/sales/sync-offline', $payload)->assertOk()->assertJsonPath('count', 0)->assertJsonPath('success', false);
        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(28, $this->unit->fresh()->current_quantity);
    }
}
