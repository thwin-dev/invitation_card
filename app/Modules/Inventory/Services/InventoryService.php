<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Support\StockMovementRecorder;
use App\Modules\Sale\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function saveUnit(ProductUnit $unit, array $data, int $userId): ProductUnit
    {
        return DB::transaction(function () use ($unit, $data, $userId) {
            $locked = $unit->exists ? ProductUnit::lockForUpdate()->findOrFail($unit->id) : $unit;
            $before = $locked->exists ? $locked->current_quantity : 0;
            $locked->fill($data)->save();
            $after = $locked->current_quantity;
            if ($before !== $after) {
                StockMovementRecorder::record(
                    $locked, $after > $before ? 'IN' : 'OUT', $before, $after, $userId, 'adjust', null, 'Stock updated from unit management'
                );
            }

            return $locked;
        });
    }

    public function balance(ProductUnit $unit, InventoryBranch $branch): int
    {
        return $branch->is_main ? $unit->current_quantity : (int) InventoryLot::where('product_unit_id', $unit->id)->where('branch_id', $branch->id)->sum('quantity');
    }

    public function sell(ProductUnit $unit, InventoryBranch $branch, int $quantity, int $userId, int $saleId): void
    {
        $this->issue($unit, $branch, $quantity, [
            'kind' => 'sale',
            'token' => (string) Str::uuid(),
            'reference_type' => 'sale',
            'reference_id' => $saleId,
            'note' => 'Sale completed',
            'lot_id' => null,
        ], $userId);
    }

    public function restoreSale(Sale $sale, int $userId, string $note = 'Sale reversed'): void
    {
        $branch = $sale->branch ?: InventoryBranch::where('is_main', true)->firstOrFail();
        $items = $sale->items()->get();

        foreach ($items as $item) {
            $unit = ProductUnit::query()->lockForUpdate()->findOrFail($item->product_unit_id);
            $quantity = (int) $item->quantity;
            $before = $this->balance($unit, $branch);
            $remaining = $quantity;
            $parts = [];

            $outstanding = StockMovement::query()
                ->where('product_unit_id', $unit->id)
                ->where('reference_type', 'sale')
                ->where('reference_id', $sale->id)
                ->where('branch_id', $branch->id)
                ->whereNotNull('lot_id')
                ->selectRaw("lot_id, SUM(CASE WHEN direction = 'OUT' THEN quantity ELSE -quantity END) as remaining")
                ->groupBy('lot_id')
                ->get();

            foreach ($outstanding as $entry) {
                $restore = min($remaining, max(0, (int) $entry->remaining));
                if ($restore === 0) {
                    continue;
                }

                $lot = InventoryLot::query()->lockForUpdate()->findOrFail($entry->lot_id);
                if ($lot->branch_id !== $branch->id || $lot->product_unit_id !== $unit->id) {
                    throw ValidationException::withMessages(['stock' => 'This sale stock has moved to another branch. Transfer it back before reversing the sale.']);
                }
                $lot->increment('quantity', $restore);
                $parts[] = ['lot' => $lot, 'quantity' => $restore];
                $remaining -= $restore;
            }

            if ($remaining > 0) {
                if (! $branch->is_main) {
                    throw ValidationException::withMessages(['stock' => 'The original branch stock movement is missing; this sale cannot be reversed safely.']);
                }
                $parts[] = ['lot' => null, 'quantity' => $remaining];
            }

            if ($branch->is_main) {
                $unit->increment('current_quantity', $quantity);
            }

            $running = $before;
            foreach ($parts as $part) {
                $next = $running + $part['quantity'];
                $this->movement($unit, $branch, $running, $next, [
                    'kind' => 'sale',
                    'token' => (string) Str::uuid(),
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'note' => $note,
                ], $userId, $part['lot']?->id);
                $running = $next;
            }
        }
    }

    public function execute(array $data, int $userId): void
    {
        $data = Validator::make($data, [
            'token' => 'required|uuid', 'kind' => 'required|in:in,out,adjust,count,transfer,damaged,expired,lost,assemble',
            'unit_id' => 'required|integer|exists:product_units,id', 'branch_id' => 'required|integer|exists:inventory_branches,id',
            'destination_id' => 'nullable|required_if:kind,transfer|different:branch_id|exists:inventory_branches,id',
            'quantity' => 'required|integer|min:0|max:100000000', 'expected' => 'nullable|integer|min:0',
            'lot_id' => 'nullable|integer|exists:inventory_lots,id', 'batch_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255', 'expires_at' => 'nullable|date', 'note' => 'required|string|max:1000',
        ])->validate();
        DB::transaction(function () use ($data, $userId) {
            // Inserting the unique token first serializes duplicate submissions.
            if (! DB::table('inventory_operations')->insertOrIgnore([
                'token' => $data['token'], 'kind' => $data['kind'], 'user_id' => $userId,
                'details' => json_encode($data), 'created_at' => now(), 'updated_at' => now(),
            ])) {
                return;
            }
            $unit = ProductUnit::whereHas('product')->lockForUpdate()->findOrFail($data['unit_id']);
            $branch = InventoryBranch::findOrFail($data['branch_id']);
            $quantity = (int) $data['quantity'];
            $before = $this->balance($unit, $branch);
            if (in_array($data['kind'], ['count', 'adjust'])) {
                if (! isset($data['expected']) || (int) $data['expected'] !== $before) {
                    throw ValidationException::withMessages(['quantity' => 'Stock changed since this form opened. Reopen it and recount before saving.']);
                }
                $delta = $quantity - $before;
                if ($delta > 0) {
                    $this->receive($unit, $branch, $delta, $data, $userId);
                }
                if ($delta < 0) {
                    $this->issue($unit, $branch, -$delta, $data, $userId);
                }

                // The operation record also preserves zero-variance physical counts.
                return;
            }
            if ($quantity < 1) {
                throw ValidationException::withMessages(['quantity' => 'Enter a quantity greater than zero.']);
            }
            if ($data['kind'] === 'assemble') {
                $components = $unit->components()->orderBy('product_units.id')->get();
                if ($components->isEmpty()) {
                    throw ValidationException::withMessages(['unit_id' => 'Configure components on this unit before assembling a bundle.']);
                }
                foreach ($components as $component) {
                    $locked = ProductUnit::lockForUpdate()->findOrFail($component->id);
                    $this->issue($locked, $branch, $quantity * $component->pivot->quantity, [...$data, 'lot_id' => null], $userId);
                }
                $this->receive($unit, $branch, $quantity, $data, $userId);
            } elseif ($data['kind'] === 'in') {
                $this->receive($unit, $branch, $quantity, $data, $userId);
            } elseif ($data['kind'] === 'transfer') {
                $destination = InventoryBranch::findOrFail($data['destination_id']);
                $parts = $this->issue($unit, $branch, $quantity, $data, $userId);
                foreach ($parts as $part) {
                    // Serial numbers identify the same physical item across branches.
                    if ($part['lot']?->serial_number) {
                        $lot = $part['lot'];
                        $destBefore = $this->balance($unit, $destination);
                        $lot->update(['branch_id' => $destination->id, 'quantity' => 1]);
                        if ($destination->is_main) {
                            $unit->increment('current_quantity');
                        }
                        $this->movement($unit, $destination, $destBefore, $destBefore + 1, $data, $userId, $lot->id);
                    } else {
                        $this->receive($unit, $destination, $part['quantity'], [...$data,
                            'batch_number' => $part['lot']?->batch_number,
                            'serial_number' => null, 'expires_at' => $part['lot']?->expires_at?->toDateString(),
                        ], $userId, false);
                    }
                }
            } else {
                $this->issue($unit, $branch, $quantity, $data, $userId);
            }
        }, 3);
    }

    private function receive(ProductUnit $unit, InventoryBranch $branch, int $quantity, array $data, int $userId, bool $validateTracking = true): void
    {
        if ($validateTracking && $unit->tracking === 'batch' && empty($data['batch_number'])) {
            throw ValidationException::withMessages(['batch_number' => 'A batch number is required for this unit.']);
        }
        if (($unit->tracking === 'serial' && $validateTracking) || ! empty($data['serial_number'])) {
            if ($quantity !== 1 || empty($data['serial_number'])) {
                throw ValidationException::withMessages(['serial_number' => 'Receive serialized items individually with a unique serial number.']);
            }
        }
        if (! empty($data['serial_number']) && InventoryLot::where('serial_number', $data['serial_number'])->exists()) {
            throw ValidationException::withMessages(['serial_number' => 'This serial number is already recorded.']);
        }
        $before = $this->balance($unit, $branch);
        $lot = InventoryLot::create([
            'product_unit_id' => $unit->id, 'branch_id' => $branch->id, 'quantity' => $quantity,
            'batch_number' => $data['batch_number'] ?? null, 'serial_number' => $data['serial_number'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
        if ($branch->is_main) {
            $unit->increment('current_quantity', $quantity);
        }
        $this->movement($unit, $branch, $before, $before + $quantity, $data, $userId, $lot->id);
    }

    private function issue(ProductUnit $unit, InventoryBranch $branch, int $quantity, array $data, int $userId): array
    {
        $before = $this->balance($unit, $branch);
        if ($before < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Insufficient stock for '.$unit->name.'.']);
        }
        $parts = $this->consumeLots($unit, $branch, $quantity, $before, $data['lot_id'] ?? null,
            in_array($data['kind'], ['transfer', 'damaged', 'expired', 'lost', 'count', 'adjust']));
        if ($branch->is_main) {
            $unit->decrement('current_quantity', $quantity);
        }
        $running = $before;
        foreach ($parts as $part) {
            $this->movement($unit, $branch, $running, $running - $part['quantity'], $data, $userId, $part['lot']?->id);
            $running -= $part['quantity'];
        }

        return $parts;
    }

    public function consumeLots(ProductUnit $unit, InventoryBranch $branch, int $quantity, int $before, ?int $lotId = null, bool $allowExpired = false): array
    {
        $lots = InventoryLot::where('product_unit_id', $unit->id)->where('branch_id', $branch->id)->where('quantity', '>', 0)
            ->orderByRaw('expires_at IS NULL')->orderBy('expires_at')->orderBy('id')->lockForUpdate()->get();
        $untracked = $branch->is_main ? max(0, $before - $lots->sum('quantity')) : 0;
        $remaining = $quantity;
        $parts = [];
        foreach ($lots as $lot) {
            if ($lotId && $lot->id !== $lotId) {
                continue;
            }
            if (! $allowExpired && $lot->expires_at && $lot->expires_at->lt(today())) {
                continue;
            }
            $take = min($remaining, $lot->quantity);
            if ($take <= 0) {
                break;
            }
            $lot->decrement('quantity', $take);
            $parts[] = ['lot' => $lot, 'quantity' => $take];
            $remaining -= $take;
        }
        if (! $lotId && $remaining && $untracked) {
            $take = min($remaining, $untracked);
            $parts[] = ['lot' => null, 'quantity' => $take];
            $remaining -= $take;
        }
        if ($remaining) {
            throw ValidationException::withMessages(['quantity' => 'Not enough available stock in the selected batch/branch. Check expired stock.']);
        }

        return $parts;
    }

    private function movement(ProductUnit $unit, InventoryBranch $branch, int $before, int $after, array $data, int $userId, ?int $lotId): void
    {
        StockMovement::create([
            'product_unit_id' => $unit->id, 'user_id' => $userId, 'direction' => $after > $before ? 'IN' : 'OUT',
            'quantity' => abs($after - $before), 'quantity_before' => $before, 'quantity_after' => $after,
            'branch_id' => $branch->id, 'lot_id' => $lotId, 'operation_key' => $data['token'],
            'reference_type' => $data['reference_type'] ?? $data['kind'],
            'reference_id' => $data['reference_id'] ?? null, 'note' => $data['note'],
        ]);
    }
}
