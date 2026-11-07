<?php

namespace App\Modules\Sale\Services;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Sale\Models\DiscountRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaleDiscounts
{
    // All calculation is in cents. Percentage rates are stored as hundredths of a percent.
    public function calculate(InventoryBranch $branch, array $lines, array $savedItems = []): array
    {
        Validator::make(['cart' => $lines], [
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'cart.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'cart.*.price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'cart.*.discount_rule_id' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        $unitIds = collect($lines)->pluck('unit_id')->unique()->all();
        $rules = DiscountRule::query()->forBranch($branch->id)->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('product_unit_id')->orWhereIn('product_unit_id', $unitIds))
            ->lockForUpdate()->get()->groupBy(fn (DiscountRule $rule) => $rule->product_unit_id ?: '*');
        $subtotal = $discount = 0;
        $items = [];
        foreach ($lines as $key => $line) {
            $gross = (int) $line['qty'] * $this->cents($line['price']);
            if ($gross > 999999999999) {
                throw ValidationException::withMessages(['cart' => 'The sale amount is too large.']);
            }
            $snapshot = ['discount_rule_id' => null, 'discount_name' => null, 'discount_type' => null, 'discount_value' => 0];
            $saved = $savedItems[$line['item_id'] ?? ''] ?? null;
            if ($saved && ! empty($saved->discount_rule_id) && (int) $saved->product_unit_id === (int) $line['unit_id']
                && (int) $saved->discount_rule_id === (int) ($line['discount_rule_id'] ?? 0)) {
                $snapshot = $saved->only(array_keys($snapshot));
            } else {
                $candidates = $rules->get((int) $line['unit_id'], collect())->concat($rules->get('*', collect()));
                $rule = $candidates->map(function (DiscountRule $candidate) use ($gross, $line): array {
                    $rate = $this->cents($candidate->value);
                    $amount = $candidate->type === 'percentage'
                        ? intdiv($gross * $rate + 5000, 10000)
                        : $rate * (int) $line['qty'];

                    return ['rule' => $candidate, 'amount' => $amount];
                })->filter(fn (array $candidate): bool => $candidate['amount'] <= $gross)->sort(function (array $a, array $b): int {
                    return ($b['amount'] <=> $a['amount']) ?: ($a['rule']->id <=> $b['rule']->id);
                })->first();
                if ($rule) {
                    $candidate = $rule['rule'];
                    $snapshot = ['discount_rule_id' => $candidate->id, 'discount_name' => $candidate->name,
                        'discount_type' => $candidate->type, 'discount_value' => $candidate->value];
                }
            }
            $rate = $this->cents($snapshot['discount_value']);
            $amount = match ($snapshot['discount_type']) {
                'percentage' => intdiv($gross * $rate + 5000, 10000),
                'fixed' => (int) $line['qty'] * $rate,
                default => 0,
            };
            if ($amount < 0 || $amount > $gross) {
                throw ValidationException::withMessages(["cart.$key.discount_rule_id" => 'Discount cannot exceed the item amount. Remove it or choose another rule.']);
            }
            $subtotal += $gross;
            $discount += $amount;
            if ($subtotal > 999999999999) {
                throw ValidationException::withMessages(['cart' => 'The sale amount is too large.']);
            }
            $items[$key] = ['product_unit_id' => (int) $line['unit_id'], 'quantity' => (int) $line['qty'],
                'price' => $this->cents($line['price']) / 100, ...$snapshot, 'discount_amount' => $amount / 100];
        }

        return ['subtotal' => $subtotal / 100, 'discount_amount' => $discount / 100,
            'total_price' => ($subtotal - $discount) / 100, 'items' => $items];
    }

    private function cents(mixed $value): int
    {
        return (int) round((float) $value * 100, 0, PHP_ROUND_HALF_UP);
    }
}
