<?php

namespace Tests\Feature\POS;

use App\Models\User;
use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineSaleSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Offline POS Cashier',
            'user_code' => 'USR-'.uniqid(),
            'email' => 'cashier@pos.test',
            'password' => bcrypt('password'),
            'prohibition_flag' => false,
        ]);
    }

    private function productUnit(User $user, int $stock = 50, float $price = 2500): ProductUnit
    {
        $category = ProductCategory::query()->create(['name' => 'Category-'.uniqid(), 'created_by' => $user->id]);
        $subcategory = ProductSubcategory::query()->create(['name' => 'Subcategory-'.uniqid(), 'category_id' => $category->id]);
        $product = Product::query()->create([
            'code' => 'P-'.uniqid(),
            'name' => 'Offline Item '.uniqid(),
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'created_by' => $user->id,
        ]);

        return ProductUnit::query()->create([
            'product_id' => $product->id,
            'name' => 'Piece',
            'code' => 'UNIT-'.uniqid(),
            'current_quantity' => $stock,
            'reorder_quantity' => 5,
            'normal_sale_price' => $price,
            'whole_sale_price' => $price * 0.9,
            'order_price' => $price,
            'purchase_price' => $price * 0.7,
        ]);
    }

    public function test_offline_catalog_endpoint_returns_products_and_customers(): void
    {
        $user = $this->createUser();
        $unit = $this->productUnit($user, 30, 4500);

        $customer = PosCustomer::query()->create([
            'name' => 'Offline Regular Customer',
            'customer_level' => 2,
            'allow_credit' => true,
            'credit_limit' => 100000,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user, 'web')
            ->getJson('/management/sales/offline-catalog');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment([
                'id' => $unit->id,
                'price' => 4500.0,
                'stock' => 30,
            ])
            ->assertJsonFragment([
                'id' => $customer->id,
                'name' => 'Offline Regular Customer',
                'customer_level' => 2,
            ]);
    }

    public function test_bulk_offline_sales_sync_creates_sales_and_deducts_stock(): void
    {
        $user = $this->createUser();
        $unit1 = $this->productUnit($user, 20, 1000);
        $unit2 = $this->productUnit($user, 15, 2000);

        $transactionId1 = (string) Str::uuid();
        $transactionId2 = (string) Str::uuid();

        // 2 offline sales in 1 sync batch
        $payload = [
            'sales' => [
                [
                    'client_transaction_id' => $transactionId1,
                    'voucher_code' => 'OFF-VOU-20260914-1001',
                    'customer_id' => null,
                    'sale_date' => '2026-09-14',
                    'offline_created_at' => '2026-09-14 10:15:00',
                    'paid_amount' => 3000,
                    'repayment_date' => null,
                    'items' => [
                        ['unit_id' => $unit1->id, 'qty' => 3, 'price' => 1000], // 3,000
                    ],
                ],
                [
                    'client_transaction_id' => $transactionId2,
                    'voucher_code' => 'OFF-VOU-20260914-1002',
                    'customer_id' => null,
                    'sale_date' => '2026-09-14',
                    'offline_created_at' => '2026-09-14 10:20:00',
                    'paid_amount' => 4000,
                    'repayment_date' => null,
                    'items' => [
                        ['unit_id' => $unit2->id, 'qty' => 2, 'price' => 2000], // 4,000
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user, 'web')
            ->postJson('/management/sales/sync-offline', $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 2,
                'synced_ids' => [$transactionId1, $transactionId2],
            ]);

        $this->assertDatabaseHas('sales', [
            'client_transaction_id' => $transactionId1,
            'voucher_code' => 'OFF-VOU-20260914-1001',
            'is_offline_sync' => true,
            'total_price' => 3000,
            'paid_amount' => 3000,
            'credit_amount' => 0,
        ]);

        $this->assertDatabaseHas('sales', [
            'client_transaction_id' => $transactionId2,
            'voucher_code' => 'OFF-VOU-20260914-1002',
            'is_offline_sync' => true,
            'total_price' => 4000,
        ]);

        // Stock decreased
        $this->assertSame(17, $unit1->fresh()->current_quantity); // 20 - 3 = 17
        $this->assertSame(13, $unit2->fresh()->current_quantity); // 15 - 2 = 13
    }

    public function test_offline_sales_sync_idempotency_prevents_duplicate_transactions(): void
    {
        $user = $this->createUser();
        $unit = $this->productUnit($user, 10, 5000);
        $transactionId = (string) Str::uuid();

        $payload = [
            'sales' => [
                [
                    'client_transaction_id' => $transactionId,
                    'voucher_code' => 'OFF-VOU-DUP-TEST',
                    'customer_id' => null,
                    'sale_date' => '2026-09-14',
                    'paid_amount' => 5000,
                    'items' => [
                        ['unit_id' => $unit->id, 'qty' => 1, 'price' => 5000],
                    ],
                ],
            ],
        ];

        // First sync -> Creates 1 sale
        $this->actingAs($user, 'web')
            ->postJson('/management/sales/sync-offline', $payload)
            ->assertOk()
            ->assertJson(['count' => 1]);

        $this->assertSame(1, Sale::query()->where('client_transaction_id', $transactionId)->count());
        $this->assertSame(9, $unit->fresh()->current_quantity);

        // Second sync with identical client_transaction_id -> Re-returns success without duplicating sale or stock deduction
        $this->actingAs($user, 'web')
            ->postJson('/management/sales/sync-offline', $payload)
            ->assertOk()
            ->assertJson(['count' => 1, 'synced_ids' => [$transactionId]]);

        $this->assertSame(1, Sale::query()->where('client_transaction_id', $transactionId)->count());
        $this->assertSame(9, $unit->fresh()->current_quantity); // Still 9, not deducted twice
    }

    public function test_offline_credit_sales_sync_records_customer_debt(): void
    {
        $user = $this->createUser();
        $unit = $this->productUnit($user, 20, 6000);

        $customer = PosCustomer::query()->create([
            'name' => 'Credit Offline Buyer',
            'customer_level' => 3,
            'allow_credit' => true,
            'credit_limit' => 50000,
            'created_by' => $user->id,
        ]);

        $transactionId = (string) Str::uuid();

        // 2 items * 6000 = 12,000 total. Paid 4,000 -> Credit 8,000
        $payload = [
            'sales' => [
                [
                    'client_transaction_id' => $transactionId,
                    'voucher_code' => 'OFF-VOU-CREDIT',
                    'customer_id' => $customer->id,
                    'sale_date' => '2026-09-14',
                    'paid_amount' => 4000,
                    'repayment_date' => '2026-09-25',
                    'items' => [
                        ['unit_id' => $unit->id, 'qty' => 2, 'price' => 6000],
                    ],
                ],
            ],
        ];

        $this->actingAs($user, 'web')
            ->postJson('/management/sales/sync-offline', $payload)
            ->assertOk();

        $sale = Sale::query()->where('client_transaction_id', $transactionId)->firstOrFail();
        $this->assertEquals(12000.0, (float) $sale->total_price);
        $this->assertEquals(4000.0, (float) $sale->paid_amount);
        $this->assertEquals(8000.0, (float) $sale->credit_amount);
        $this->assertSame('2026-09-25', $sale->repayment_date->format('Y-m-d'));

        $credit = CustomerCreditRecord::query()->where('sale_id', $sale->id)->firstOrFail();
        $this->assertSame($customer->id, $credit->customer_id);
        $this->assertEquals(8000.0, (float) $credit->amount);
        $this->assertNull($credit->repayment_date);

        $this->assertEquals(8000.0, $customer->fresh()->outstandingDebt());
    }
}
