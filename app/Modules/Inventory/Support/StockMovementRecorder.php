<?php

namespace App\Modules\Inventory\Support;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Validation\ValidationException;

final class StockMovementRecorder
{
    public static function record(ProductUnit $unit, string $direction, int $before, int $after, int $userId, ?string $type = null, ?int $id = null, ?string $note = null, array $tracking = []): void
    {
        $branch = InventoryBranch::where('is_main', true)->firstOrFail();
        $quantity = abs($after - $before);
        if (! $quantity) {
            return;
        }
        $parts = [];
        if ($direction === 'OUT' && $type === 'purchase' && $id && StockMovement::where('reference_type', 'purchase')->where('reference_id', $id)->where('product_unit_id', $unit->id)->whereNotNull('lot_id')->exists()) {
            $receipts = StockMovement::where('reference_type', 'purchase')->where('reference_id', $id)->where('product_unit_id', $unit->id)
                ->whereNotNull('lot_id')->selectRaw("lot_id, SUM(CASE WHEN direction = 'IN' THEN quantity ELSE -quantity END) as remaining")->groupBy('lot_id')->get();
            $remaining = $quantity;
            foreach ($receipts as $receipt) {
                $take = min($remaining, max(0, (int) $receipt->remaining));
                if (! $take) {
                    continue;
                }
                $lot = InventoryLot::lockForUpdate()->findOrFail($receipt->lot_id);
                if ($lot->branch_id != $branch->id || $lot->quantity < $take) {
                    throw ValidationException::withMessages(['stock' => 'Purchased stock has been used or transferred. Restore it before editing or deleting this purchase.']);
                }
                $lot->decrement('quantity', $take);
                $parts[] = ['lot' => $lot, 'quantity' => $take];
                $remaining -= $take;
            }
            if ($remaining) {
                throw ValidationException::withMessages(['stock' => 'Cannot reverse more than the original tracked receipt.']);
            }
        } elseif ($direction === 'OUT') {
            $parts = app(InventoryService::class)->consumeLots($unit, $branch, $quantity, $before, null, $type === 'purchase');
        } elseif (in_array($type, ['sale', 'order']) && $id) {
            // Restore the exact batches/serials consumed by this transaction on cancellation/edit.
            $outstanding = StockMovement::where('product_unit_id', $unit->id)->where('reference_type', $type)->where('reference_id', $id)
                ->whereNotNull('lot_id')->selectRaw("lot_id, SUM(CASE WHEN direction = 'OUT' THEN quantity ELSE -quantity END) as remaining")
                ->groupBy('lot_id')->get();
            $remaining = $quantity;
            foreach ($outstanding as $entry) {
                $restore = min($remaining, max(0, (int) $entry->remaining));
                if (! $restore) {
                    continue;
                }
                $lot = InventoryLot::lockForUpdate()->findOrFail($entry->lot_id);
                if ($lot->branch_id != $branch->id) {
                    throw ValidationException::withMessages(['stock' => 'The serialized stock has moved branches; reconcile it before reversing this sale.']);
                }
                $lot->increment('quantity', $restore);
                $parts[] = ['lot' => $lot, 'quantity' => $restore];
                $remaining -= $restore;
            }
            if ($remaining) {
                $parts[] = ['lot' => null, 'quantity' => $remaining];
            }
        } else {
            if ($unit->tracking === 'batch' && empty($tracking['batch_number'])) {
                throw ValidationException::withMessages(['stock' => 'Enter a batch number for '.$unit->name.'.']);
            }
            if ($unit->tracking === 'serial' && (empty($tracking['serial_number']) || $quantity !== 1)) {
                throw ValidationException::withMessages(['stock' => 'Receive '.$unit->name.' one at a time with a unique serial number.']);
            }
            $lot = ! empty($tracking['serial_number']) ? InventoryLot::where('serial_number', $tracking['serial_number'])->lockForUpdate()->first() : null;
            if (! empty($tracking['serial_number']) && ($quantity !== 1 || ($lot && ! ($type === 'purchase' && $id && $lot->quantity === 0 && $lot->branch_id === $branch->id && $lot->product_unit_id === $unit->id && StockMovement::where('reference_type', 'purchase')->where('reference_id', $id)->where('lot_id', $lot->id)->where('direction', 'IN')->exists())))) {
                throw ValidationException::withMessages(['stock' => 'Serial numbers must be unique and received one at a time.']);
            }
            if ($unit->tracking !== 'none' || array_filter($tracking)) {
                $attributes = ['product_unit_id' => $unit->id, 'branch_id' => $branch->id, 'quantity' => $quantity,
                    'batch_number' => $tracking['batch_number'] ?? null, 'serial_number' => $tracking['serial_number'] ?? null, 'expires_at' => $tracking['expires_at'] ?? null];
                if ($lot) {
                    $lot->update($attributes);
                } else {
                    $lot = InventoryLot::create($attributes);
                }
            }
            $parts[] = ['lot' => $lot, 'quantity' => $quantity];
        }
        $running = $before;
        foreach ($parts as $part) {
            $next = $direction === 'IN' ? $running + $part['quantity'] : $running - $part['quantity'];
            StockMovement::create(['product_unit_id' => $unit->id, 'user_id' => $userId, 'direction' => $direction,
                'quantity' => $part['quantity'], 'quantity_before' => $running, 'quantity_after' => $next,
                'reference_type' => $type, 'reference_id' => $id, 'note' => $note, 'branch_id' => $branch->id, 'lot_id' => $part['lot']?->id]);
            $running = $next;
        }
    }
}
