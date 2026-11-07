<?php

namespace Tests\Feature\POS;

use App\Livewire\CatalogDirectory;
use App\Livewire\InventoryWorkspace;
use App\Livewire\ProductTable;
use App\Livewire\ProductUnitTable;
use App\Livewire\PurchaseScreen;
use App\Models\User;
use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Inventory\Support\StockMovementRecorder;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductBrand;
use App\Modules\Product\Support\Ean13;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Purchase\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ProductInventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProductUnit $unit;

    private InventoryBranch $main;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Stock manager', 'user_code' => 'stock-test', 'email' => 'stock@example.com', 'password' => 'secret', 'prohibition_flag' => false]);
        $category = ProductCategory::create(['name' => 'Clothes', 'created_by' => $this->user->id]);
        $subcategory = ProductSubcategory::create(['name' => 'Shirts', 'category_id' => $category->id]);
        $product = Product::create(['name' => 'Shirt', 'code' => 'SHIRT', 'category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'created_by' => $this->user->id]);
        $this->unit = ProductUnit::create(['product_id' => $product->id, 'name' => 'Piece', 'current_quantity' => 10, 'reorder_quantity' => 2, 'purchase_price' => 10, 'normal_sale_price' => 20]);
        $this->main = InventoryBranch::where('is_main', true)->firstOrFail();
        $this->actingAs($this->user, 'web');
    }

    private function data(array $overrides = []): array
    {
        return [...['token' => (string) Str::uuid(), 'kind' => 'in', 'unit_id' => $this->unit->id, 'branch_id' => $this->main->id, 'quantity' => 3, 'note' => 'Test operation'], ...$overrides];
    }

    private function execute(array $overrides = []): void
    {
        app(InventoryService::class)->execute($this->data($overrides), $this->user->id);
    }

    public function test_new_management_screens_render_and_require_authentication(): void
    {
        foreach (['brands', 'branches', 'inventory-operations', 'products', 'product-units'] as $page) {
            $this->get('/management/'.$page)->assertOk();
        }
        auth('web')->logout();
        $this->get('/management/inventory-operations')->assertRedirect('/login');
    }

    public function test_brands_and_branches_have_livewire_editing(): void
    {
        Livewire::test(CatalogDirectory::class, ['kind' => 'brands'])->call('edit')->set('name', 'Acme')->call('save')->assertHasNoErrors()->assertDispatched('toast');
        $brand = ProductBrand::firstOrFail();
        Livewire::test(CatalogDirectory::class, ['kind' => 'brands'])->call('toggle', $brand->id)->assertSee('Inactive');
        $this->assertFalse($brand->fresh()->is_active);
        Livewire::test(CatalogDirectory::class, ['kind' => 'branches'])->call('edit')->set('name', 'Warehouse')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('inventory_branches', ['name' => 'Warehouse', 'is_main' => false]);
    }

    public function test_product_modal_saves_brand_and_rejects_wrong_subcategory(): void
    {
        $brand = ProductBrand::create(['name' => 'Acme']);
        $product = $this->unit->product;
        Livewire::test(ProductTable::class)->call('edit', $product->id)->set('form.brand_id', $brand->id)->set('form.name', 'Blue shirt')->call('save')->assertHasNoErrors()->assertDispatched('toast');
        $this->assertSame($brand->id, $product->fresh()->brand_id);
        $category = ProductCategory::create(['name' => 'Other', 'created_by' => $this->user->id]);
        Livewire::test(ProductTable::class)->call('edit', $product->id)->set('form.category_id', $category->id)->set('form.subcategory_id', $product->subcategory_id)->call('save')->assertHasErrors('form.subcategory_id');
    }

    public function test_variants_generate_valid_barcode_and_save_prices_and_components(): void
    {
        $component = Livewire::test(ProductUnitTable::class)->call('openCreate')->set('createProductId', (string) $this->unit->product_id)
            ->set('createName', 'Blue large')->set('size', 'L')->set('color', 'Blue')->set('createNormalSalePrice', '30')->set('createWholeSalePrice', '25')
            ->set('components', [['unit_id' => (string) $this->unit->id, 'quantity' => 2]]);
        $barcode = $component->get('createOriginalCode');
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $barcode[$i] * ($i % 2 ? 3 : 1);
        }
        $this->assertSame(0, $sum % 10);
        $component->call('createUnit')->assertHasNoErrors();
        $variant = ProductUnit::where('name', 'Blue large')->firstOrFail();
        $this->assertSame('L', $variant->size);
        $this->assertSame('Blue', $variant->color);
        $this->assertSame('25.00', $variant->whole_sale_price);
        $this->assertSame(2, $variant->components->first()->pivot->quantity);
    }

    public function test_receipt_transfer_and_damage_preserve_total_and_batch_details(): void
    {
        $remote = InventoryBranch::create(['name' => 'North']);
        $this->execute(['batch_number' => 'B-1', 'expires_at' => '2028-01-01', 'quantity' => 6]);
        $lot = InventoryLot::where('batch_number', 'B-1')->firstOrFail();
        $this->execute(['kind' => 'transfer', 'quantity' => 4, 'lot_id' => $lot->id, 'destination_id' => $remote->id]);
        $this->assertSame(12, $this->unit->fresh()->current_quantity);
        $this->assertDatabaseHas('inventory_lots', ['branch_id' => $remote->id, 'batch_number' => 'B-1', 'quantity' => 4]);
        $this->assertSame('2028-01-01', InventoryLot::where('branch_id', $remote->id)->firstOrFail()->expires_at->toDateString());
        $this->execute(['kind' => 'damaged', 'branch_id' => $remote->id, 'quantity' => 1]);
        $this->assertSame(3, app(InventoryService::class)->balance($this->unit->fresh(), $remote));
        $this->assertDatabaseHas('stock_movements', ['reference_type' => 'damaged', 'quantity_before' => 4, 'quantity_after' => 3, 'user_id' => $this->user->id]);
    }

    public function test_duplicate_submissions_are_idempotent(): void
    {
        $data = $this->data();
        app(InventoryService::class)->execute($data, $this->user->id);
        app(InventoryService::class)->execute($data, $this->user->id);
        $this->assertSame(13, $this->unit->fresh()->current_quantity);
        $this->assertDatabaseCount('inventory_operations', 1);
    }

    public function test_transfer_cannot_overdraw_and_rolls_back_operation(): void
    {
        $remote = InventoryBranch::create(['name' => 'North']);
        try {
            $this->execute(['kind' => 'transfer', 'quantity' => 11, 'destination_id' => $remote->id]);
            $this->fail('Expected insufficient stock');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }
        $this->assertSame(10, $this->unit->fresh()->current_quantity);
        $this->assertDatabaseCount('inventory_operations', 0);
    }

    public function test_count_uses_new_total_and_rejects_stale_balance(): void
    {
        $this->execute(['kind' => 'count', 'quantity' => 8, 'expected' => 10]);
        $this->assertSame(8, $this->unit->fresh()->current_quantity);
        try {
            $this->execute(['kind' => 'count', 'quantity' => 9, 'expected' => 10]);
            $this->fail('Expected stale count rejection');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }
        $this->assertSame(8, $this->unit->fresh()->current_quantity);
        $this->execute(['kind' => 'count', 'quantity' => 8, 'expected' => 8]);
        $this->assertDatabaseCount('inventory_operations', 2);
    }

    public function test_serial_transfer_moves_identity_and_duplicate_serial_is_rejected(): void
    {
        $this->unit->update(['tracking' => 'serial', 'current_quantity' => 0]);
        $this->execute(['quantity' => 1, 'serial_number' => 'SERIAL-1']);
        $lot = InventoryLot::where('serial_number', 'SERIAL-1')->firstOrFail();
        $remote = InventoryBranch::create(['name' => 'North']);
        $this->execute(['kind' => 'transfer', 'quantity' => 1, 'lot_id' => $lot->id, 'destination_id' => $remote->id]);
        $this->assertSame($remote->id, $lot->fresh()->branch_id);
        $this->assertSame(0, $this->unit->fresh()->current_quantity);
        try {
            $this->execute(['quantity' => 1, 'serial_number' => 'SERIAL-1']);
            $this->fail('Expected duplicate rejection');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('serial_number', $e->errors());
        }
        $this->assertDatabaseCount('inventory_lots', 1);
    }

    public function test_expired_stock_cannot_be_sold_but_can_be_written_off(): void
    {
        $this->unit->update(['current_quantity' => 0]);
        $this->execute(['quantity' => 3, 'batch_number' => 'OLD', 'expires_at' => today()->subDay()->toDateString()]);
        try {
            $this->execute(['kind' => 'out', 'quantity' => 1]);
            $this->fail('Expected expired rejection');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }
        $this->assertSame(3, $this->unit->fresh()->current_quantity);
        $this->execute(['kind' => 'expired', 'quantity' => 3]);
        $this->assertSame(0, $this->unit->fresh()->current_quantity);
    }

    public function test_bundle_assembly_consumes_components_atomically(): void
    {
        $bundle = ProductUnit::create(['product_id' => $this->unit->product_id, 'name' => 'Two pack', 'current_quantity' => 0]);
        $bundle->components()->attach($this->unit->id, ['quantity' => 2]);
        $this->execute(['kind' => 'assemble', 'unit_id' => $bundle->id, 'quantity' => 3]);
        $this->assertSame(4, $this->unit->fresh()->current_quantity);
        $this->assertSame(3, $bundle->fresh()->current_quantity);
        try {
            $this->execute(['kind' => 'assemble', 'unit_id' => $bundle->id, 'quantity' => 3]);
            $this->fail('Expected insufficient components');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }
        $this->assertSame(4, $this->unit->fresh()->current_quantity);
        $this->assertSame(3, $bundle->fresh()->current_quantity);
    }

    public function test_sale_stock_recorder_consumes_and_restores_exact_serial(): void
    {
        $this->unit->update(['current_quantity' => 0, 'tracking' => 'serial']);
        $this->execute(['quantity' => 1, 'serial_number' => 'SALE-SERIAL']);
        DB::transaction(function () {
            $unit = $this->unit->fresh();
            $unit->decrement('current_quantity');
            StockMovementRecorder::record($unit, 'OUT', 1, 0, $this->user->id, 'sale', 99);
        });
        $this->assertDatabaseHas('inventory_lots', ['serial_number' => 'SALE-SERIAL', 'quantity' => 0]);
        DB::transaction(function () {
            $unit = $this->unit->fresh();
            $unit->increment('current_quantity');
            StockMovementRecorder::record($unit, 'IN', 0, 1, $this->user->id, 'sale', 99);
        });
        $this->assertDatabaseHas('inventory_lots', ['serial_number' => 'SALE-SERIAL', 'quantity' => 1]);
    }

    public function test_purchase_screen_receives_batch_and_expiry(): void
    {
        $this->unit->update(['tracking' => 'batch']);
        Livewire::test(PurchaseScreen::class)->call('addUnit', $this->unit->id)
            ->set('isCustomSupplier', true)->set('supplierName', 'Supplier')
            ->set('cart.'.$this->unit->id.'.batch_number', 'PO-BATCH')
            ->set('cart.'.$this->unit->id.'.expires_at', '2028-01-01')
            ->call('savePurchase')->assertRedirect('/management/purchases');
        $this->assertSame(11, $this->unit->fresh()->current_quantity);
        $this->assertDatabaseHas('inventory_lots', ['batch_number' => 'PO-BATCH', 'quantity' => 1]);
    }

    public function test_inventory_modal_records_counts_and_live_filters_alerts(): void
    {
        Livewire::test(InventoryWorkspace::class)->call('start', $this->unit->id)->set('form.kind', 'count')->set('form.quantity', 0)->set('form.note', 'Shelf count')->call('save')->assertHasNoErrors()->assertDispatched('toast')->set('status', 'out')->assertSee('Shirt');
        $this->assertSame(0, $this->unit->fresh()->current_quantity);
    }

    public function test_tracked_purchase_edit_reuses_serial_and_delete_reverses_it(): void
    {
        $this->unit->update(['tracking' => 'serial', 'current_quantity' => 0]);
        Livewire::test(PurchaseScreen::class)->call('addUnit', $this->unit->id)
            ->set('isCustomSupplier', true)->set('supplierName', 'Supplier')
            ->set('cart.'.$this->unit->id.'.serial_number', 'PURCHASE-SERIAL')
            ->call('savePurchase')->assertRedirect('/management/purchases');
        $purchase = Purchase::firstOrFail();
        $this->get('/management/purchases/'.$purchase->id.'/edit')->assertOk()->assertSee('PURCHASE-SERIAL');
        $this->put('/management/purchases/'.$purchase->id, [
            'supplier_name' => 'Updated supplier', 'purchase_date' => today()->toDateString(),
            'unit' => [$this->unit->id], 'qty' => [1], 'price' => [12], 'serial_number' => ['PURCHASE-SERIAL'],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('inventory_lots', 1);
        $this->assertSame(1, $this->unit->fresh()->current_quantity);
        $this->delete('/management/purchases/'.$purchase->id)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(0, $this->unit->fresh()->current_quantity);
        $this->assertDatabaseHas('inventory_lots', ['serial_number' => 'PURCHASE-SERIAL', 'quantity' => 0]);
    }

    public function test_used_tracked_purchase_cannot_remove_unrelated_stock(): void
    {
        $this->unit->update(['tracking' => 'batch']);
        Livewire::test(PurchaseScreen::class)->call('addUnit', $this->unit->id)
            ->set('isCustomSupplier', true)->set('supplierName', 'Supplier')
            ->set('cart.'.$this->unit->id.'.batch_number', 'USED-BATCH')->call('savePurchase');
        $purchase = Purchase::firstOrFail();
        $this->execute(['kind' => 'out', 'quantity' => 1]);
        $this->delete('/management/purchases/'.$purchase->id)->assertSessionHasErrors('stock');
        $this->assertSame(10, $this->unit->fresh()->current_quantity);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
    }

    public function test_bundle_failure_rolls_back_components_already_consumed(): void
    {
        $second = ProductUnit::create(['product_id' => $this->unit->product_id, 'name' => 'Second component', 'current_quantity' => 1]);
        $bundle = ProductUnit::create(['product_id' => $this->unit->product_id, 'name' => 'Combo', 'current_quantity' => 0]);
        $bundle->components()->attach([$this->unit->id => ['quantity' => 2], $second->id => ['quantity' => 1]]);
        try {
            $this->execute(['kind' => 'assemble', 'unit_id' => $bundle->id, 'quantity' => 2]);
            $this->fail('Expected insufficient components');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }
        $this->assertSame(10, $this->unit->fresh()->current_quantity);
        $this->assertSame(1, $second->fresh()->current_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('inventory_operations', 0);
    }

    public function test_price_edit_does_not_overwrite_stock_changed_since_modal_opened(): void
    {
        $editor = Livewire::test(ProductUnitTable::class)->call('editUnit', $this->unit->id);
        $this->execute(['quantity' => 2]);
        $editor->set('editNormalSalePrice', '45')->call('updateUnit')->assertHasNoErrors();
        $this->assertSame(12, $this->unit->fresh()->current_quantity);
        $this->assertSame('45.00', $this->unit->fresh()->normal_sale_price);
    }

    public function test_barcode_label_renders_valid_ean13_and_rejects_invalid_checksum(): void
    {
        $this->unit->update(['original_code' => '4006381333931']);
        $this->get('/management/product-units/'.$this->unit->id.'/barcode')->assertOk()->assertSee('<svg', false)->assertSee('4006381333931');
        $bars = Ean13::bars('4006381333931');
        $this->assertSame(95, strlen($bars));
        $this->assertNull(Ean13::bars('4006381333932'));
    }
}
