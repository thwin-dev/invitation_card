<?php

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Support\StockMovementRecorder;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\Sale\Models\DiscountRule;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Services\BranchSalePricing;
use App\Modules\Sale\Services\SaleDiscounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OfflineSaleSyncController extends Controller
{
    public function catalog(): JsonResponse
    {
        $branch = InventoryBranch::query()->forSales()->where('is_main', true)->firstOrFail();
        $discountRules = DiscountRule::query()->forBranch($branch->id)->where('is_active', true)->orderBy('name')->get();
        $products = ProductUnit::query()
            ->with(['product.category'])
            ->whereHas('product')
            ->orderBy('name')
            ->get()
            ->map(function (ProductUnit $unit) use ($branch, $discountRules): array {
                $photoPath = $unit->product?->photo_path;
                $photoUrl = null;
                if ($photoPath) {
                    $photoUrl = str_starts_with($photoPath, 'images/')
                        ? asset($photoPath)
                        : Storage::disk('public')->url($photoPath);
                }

                return [
                    'id' => $unit->id,
                    'product_id' => $unit->product_id,
                    'product_name' => $unit->product?->name ?: 'Unknown product',
                    'unit_name' => $unit->name,
                    'code' => $unit->code ?: $unit->original_code ?: '',
                    'category_id' => $unit->product?->category_id,
                    'category_name' => $unit->product?->category?->name,
                    'stock' => (int) $unit->current_quantity,
                    'price' => app(BranchSalePricing::class)->price($branch, $unit),
                    'discount_rules' => $discountRules->filter(fn ($rule) => ! $rule->product_unit_id || (int) $rule->product_unit_id === $unit->id)
                        ->map(fn ($rule) => ['id' => $rule->id, 'name' => $rule->name, 'type' => $rule->type, 'value' => $rule->value, 'version' => $rule->version()])->values(),
                    'wholesale_price' => (float) ($unit->whole_sale_price ?: $unit->normal_sale_price),
                    'photo_url' => $photoUrl,
                ];
            });

        $categories = ProductCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $customers = PosCustomer::query()
            ->orderBy('name')
            ->get()
            ->map(function (PosCustomer $c): array {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone ?: '',
                    'customer_level' => (int) ($c->customer_level ?: 1),
                    'allow_credit' => (bool) $c->allow_credit,
                    'credit_limit' => (float) ($c->credit_limit ?: 0),
                    'debt' => $c->outstandingDebt(),
                    'available_credit' => $c->availableCredit(),
                ];
            });

        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sales' => ['required', 'array', 'min:1'],
            'sales.*.client_transaction_id' => ['required', 'string', 'max:64'],
            'sales.*.branch_id' => ['nullable', 'integer'],
            'sales.*.voucher_code' => ['nullable', 'string', 'max:64'],
            'sales.*.customer_id' => ['nullable', 'exists:pos_customers,id'],
            'sales.*.sale_date' => ['required', 'date'],
            'sales.*.offline_created_at' => ['nullable', 'date'],
            'sales.*.paid_amount' => ['required', 'numeric', 'min:0'],
            'sales.*.repayment_date' => ['nullable', 'date'],
            'sales.*.items' => ['required', 'array', 'min:1'],
            'sales.*.items.*.unit_id' => ['required', 'exists:product_units,id'],
            'sales.*.items.*.qty' => ['required', 'integer', 'min:1'],
            'sales.*.items.*.price' => ['required', 'numeric', 'min:0'],
            'sales.*.items.*.discount_rule_id' => ['nullable', 'integer'],
            'sales.*.items.*.discount_rule_version' => ['nullable', 'string'],
            'sales.*.items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $syncedIds = [];
        $failed = [];

        foreach ($data['sales'] as $salePayload) {
            $transactionId = $salePayload['client_transaction_id'];

            // Idempotency: Check if this offline transaction was already synced
            $existing = Sale::query()->where('client_transaction_id', $transactionId)->first();
            if ($existing) {
                $syncedIds[] = $transactionId;

                continue;
            }

            try {
                DB::transaction(function () use ($salePayload, $transactionId, $request, &$syncedIds): void {
                    $branch = InventoryBranch::query()->where('is_main', true)->where('type', 'shop')->firstOrFail();
                    $totalQuantity = array_sum(array_map(fn ($item) => (int) $item['qty'], $salePayload['items']));
                    if (! empty($salePayload['branch_id']) && (int) $salePayload['branch_id'] !== $branch->id) {
                        throw new \InvalidArgumentException('Offline sales are only available at the Main shop.');
                    }
                    $calculation = app(SaleDiscounts::class)->calculate($branch, $salePayload['items']);
                    foreach ($salePayload['items'] as $key => $item) {
                        if (! empty($item['discount_rule_id'])) {
                            $rule = DiscountRule::query()->findOrFail($item['discount_rule_id']);
                            if (($item['discount_rule_version'] ?? '') !== $rule->version()
                                || ! isset($item['discount_amount'])
                                || (int) round($item['discount_amount'] * 100) !== (int) round($calculation['items'][$key]['discount_amount'] * 100)) {
                                throw new \InvalidArgumentException('A discount rule changed after the offline catalog was cached. This sale needs review before synchronization.');
                            }
                        }
                    }
                    $totalPrice = $calculation['total_price'];
                    $tendered = (float) $salePayload['paid_amount'];
                    $paidAmount = min($tendered, $totalPrice);
                    $creditAmount = max(0, round($totalPrice - $paidAmount, 2));

                    $customer = ! empty($salePayload['customer_id'])
                        ? PosCustomer::query()->find($salePayload['customer_id'])
                        : null;

                    if (! $customer) {
                        // Walk-in offline sales are cash sales settled in full at the counter
                        $paidAmount = $totalPrice;
                        $creditAmount = 0.0;
                    } else {
                        $paidAmount = min($tendered, $totalPrice);
                        $creditAmount = max(0, round($totalPrice - $paidAmount, 2));

                        if ($creditAmount > 0 && ! $customer->allow_credit) {
                            throw new \InvalidArgumentException("Customer {$customer->name} is not allowed to buy on credit.");
                        }
                    }

                    $voucherCode = ! empty($salePayload['voucher_code'])
                        ? $salePayload['voucher_code']
                        : ('VOU-'.now()->format('dmY').'-'.str_pad((string) (Sale::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT));

                    $sale = Sale::query()->create([
                        'voucher_code' => $voucherCode,
                        'branch_id' => $branch->id,
                        ...app(BranchSalePricing::class)->receiptSnapshot($branch),
                        'customer_id' => $customer?->id,
                        'sale_by' => $request->user()?->id ?: 1,
                        'total_quantity' => $totalQuantity,
                        'total_price' => $totalPrice,
                        'subtotal' => $calculation['subtotal'],
                        'discount_amount' => $calculation['discount_amount'],
                        'paid_amount' => $paidAmount,
                        'credit_amount' => $creditAmount,
                        'repayment_date' => $creditAmount > 0 ? ($salePayload['repayment_date'] ?? null) : null,
                        'sale_date' => $salePayload['sale_date'],
                        'status' => 'completed',
                        'client_transaction_id' => $transactionId,
                        'is_offline_sync' => true,
                        'offline_created_at' => $salePayload['offline_created_at'] ?? now(),
                    ]);

                    foreach ($salePayload['items'] as $key => $item) {
                        $unit = ProductUnit::query()->lockForUpdate()->find($item['unit_id']);
                        if (! $unit) {
                            continue;
                        }

                        $qty = (int) $item['qty'];
                        $before = $unit->current_quantity;

                        $sale->items()->create($calculation['items'][$key]);

                        $unit->decrement('current_quantity', $qty);
                        StockMovementRecorder::record(
                            $unit,
                            'OUT',
                            $before,
                            $before - $qty,
                            $sale->sale_by,
                            'sale',
                            $sale->id,
                            'Offline sale sync'
                        );
                    }

                    if ($creditAmount > 0 && $customer) {
                        CustomerCreditRecord::query()->create([
                            'customer_id' => $customer->id,
                            'sale_id' => $sale->id,
                            'amount' => $creditAmount,
                            'repayment_date' => null,
                        ]);
                    }

                    $syncedIds[] = $transactionId;
                });
            } catch (\Throwable $e) {
                $failed[] = [
                    'client_transaction_id' => $transactionId,
                    'error' => $e instanceof ValidationException ? $e->validator->errors()->first() : $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($failed) === 0,
            'synced_ids' => $syncedIds,
            'failed' => $failed,
            'count' => count($syncedIds),
            'message' => count($syncedIds).' offline sales synchronized successfully.',
        ]);
    }
}
