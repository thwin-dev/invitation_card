<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\ProductUnitConversion;
use App\Modules\Inventory\Models\ProductUnitRelation;
use App\Modules\Inventory\Support\StockMovementRecorder;
use App\Modules\Product\Models\Product;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductUnitRelationTable extends Component
{
    use AuthorizesPosPermissions;

    public string $productId = '';
    public bool $relationOpen = false;
    public bool $conversionOpen = false;
    public ?int $deletingId = null;

    public string $relationFromUnitId = '';
    public string $relationToUnitId = '';
    public int $relationQuantity = 1;

    public string $convertFromUnitId = '';
    public string $convertToUnitId = '';
    public int $convertQuantity = 1;

    public function mount(): void
    {
        $this->productId = (string) request('product_id', '');
    }

    public function updatedProductId(): void
    {
        $this->resetForms();
    }

    #[On('open-unit-relation-create')]
    public function openRelation(): void
    {
        $this->authorizePosPermission('unit-relations', 'create');
        if (! $this->canManageRelations()) {
            return;
        }

        $this->resetValidation();
        $this->relationOpen = true;
    }

    public function closeRelation(): void
    {
        $this->relationOpen = false;
    }

    #[On('open-unit-conversion')]
    public function openConversion(): void
    {
        $this->authorizePosPermission('unit-relations', 'edit');
        if (! $this->canManageRelations()) {
            return;
        }

        $this->resetValidation();
        $this->conversionOpen = true;
    }

    public function closeConversion(): void
    {
        $this->conversionOpen = false;
    }

    public function createRelation(): void
    {
        $this->authorizePosPermission('unit-relations', 'create');
        $data = $this->validate([
            'productId' => ['required', 'exists:products,id'],
            'relationFromUnitId' => ['required', 'different:relationToUnitId', 'exists:product_units,id'],
            'relationToUnitId' => ['required', 'exists:product_units,id'],
            'relationQuantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->ensureUnitsBelongToProduct((int) $data['productId'], [(int) $data['relationFromUnitId'], (int) $data['relationToUnitId']]);

        $exists = ProductUnitRelation::query()
            ->where('product_id', $data['productId'])
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query->where('from_unit_id', $data['relationFromUnitId'])->where('to_unit_id', $data['relationToUnitId']))
                ->orWhere(fn ($query) => $query->where('from_unit_id', $data['relationToUnitId'])->where('to_unit_id', $data['relationFromUnitId'])))
            ->exists();

        if ($exists) {
            $this->addError('relationFromUnitId', 'This unit relation already exists.');
            return;
        }

        ProductUnitRelation::query()->create([
            'product_id' => $data['productId'],
            'from_unit_id' => $data['relationFromUnitId'],
            'to_unit_id' => $data['relationToUnitId'],
            'quantity' => $data['relationQuantity'],
        ]);

        $this->relationOpen = false;
        $this->resetRelationForm();
        $this->dispatch('toast', message: 'Unit relation created.');
    }

    public function confirmDelete(int $relationId): void
    {
        $this->deletingId = $relationId;
    }

    public function closeDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteRelation(): void
    {
        $this->authorizePosPermission('unit-relations', 'delete');
        ProductUnitRelation::query()->findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        $this->dispatch('toast', message: 'Unit relation deleted.');
    }

    public function convertStock(): void
    {
        $this->authorizePosPermission('unit-relations', 'edit');
        $data = $this->validate([
            'productId' => ['required', 'exists:products,id'],
            'convertFromUnitId' => ['required', 'different:convertToUnitId', 'exists:product_units,id'],
            'convertToUnitId' => ['required', 'exists:product_units,id'],
            'convertQuantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->ensureUnitsBelongToProduct((int) $data['productId'], [(int) $data['convertFromUnitId'], (int) $data['convertToUnitId']]);

        $relation = ProductUnitRelation::query()
            ->where('product_id', $data['productId'])
            ->where('from_unit_id', $data['convertFromUnitId'])
            ->where('to_unit_id', $data['convertToUnitId'])
            ->first();

        if (! $relation) {
            $this->addError('convertFromUnitId', 'Create this unit relation before converting stock.');
            return;
        }

        DB::transaction(function () use ($data, $relation): void {
            $from = ProductUnit::query()->lockForUpdate()->findOrFail($data['convertFromUnitId']);
            $to = ProductUnit::query()->lockForUpdate()->findOrFail($data['convertToUnitId']);

            if ($from->current_quantity < $data['convertQuantity']) {
                $this->addError('convertQuantity', 'Not enough quantity in the source unit.');
                return;
            }

            $fromBefore = $from->current_quantity;
            $toBefore = $to->current_quantity;
            $toAddedQuantity = $data['convertQuantity'] * $relation->quantity;

            $from->decrement('current_quantity', $data['convertQuantity']);
            $to->increment('current_quantity', $toAddedQuantity);

            StockMovementRecorder::record($from, 'OUT', $fromBefore, $fromBefore - $data['convertQuantity'], auth('web')->id(), 'conversion', null, 'Unit conversion');
            StockMovementRecorder::record($to, 'IN', $toBefore, $toBefore + $toAddedQuantity, auth('web')->id(), 'conversion', null, 'Unit conversion');

            ProductUnitConversion::query()->create([
                'product_id' => $data['productId'],
                'from_unit_id' => $from->id,
                'from_unit_quantity' => $fromBefore - $data['convertQuantity'],
                'to_unit_id' => $to->id,
                'to_unit_quantity' => $toBefore + $toAddedQuantity,
            ]);
        });

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->conversionOpen = false;
        $this->resetConversionForm();
        $this->dispatch('toast', message: 'Unit quantity converted.');
    }

    public function render()
    {
        $this->authorizePosPermission('unit-relations', 'view');
        $products = Product::query()->with('units')->orderBy('name')->get();
        $product = $this->productId ? Product::query()->find($this->productId) : null;
        $units = $product ? $product->units()->orderBy('name')->get() : collect();
        $relations = $product
            ? ProductUnitRelation::query()->with(['fromUnit', 'toUnit'])->where('product_id', $product->id)->orderByDesc('id')->get()
            : collect();
        $conversions = $product
            ? ProductUnitConversion::query()->with(['fromUnit', 'toUnit'])->where('product_id', $product->id)->latest()->limit(20)->get()
            : collect();

        return view('livewire.product-unit-relation-table', compact('products', 'product', 'units', 'relations', 'conversions'));
    }

    private function ensureUnitsBelongToProduct(int $productId, array $unitIds): void
    {
        $validCount = ProductUnit::query()->where('product_id', $productId)->whereIn('id', $unitIds)->count();
        abort_unless($validCount === count(array_unique($unitIds)), 422, 'Selected units must belong to the selected product.');
    }

    private function resetForms(): void
    {
        $this->resetRelationForm();
        $this->resetConversionForm();
        $this->relationOpen = false;
        $this->conversionOpen = false;
        $this->deletingId = null;
    }

    private function resetRelationForm(): void
    {
        $this->relationFromUnitId = '';
        $this->relationToUnitId = '';
        $this->relationQuantity = 1;
    }

    private function resetConversionForm(): void
    {
        $this->convertFromUnitId = '';
        $this->convertToUnitId = '';
        $this->convertQuantity = 1;
    }

    private function canManageRelations(): bool
    {
        if (! $this->productId) {
            $this->dispatch('toast', message: 'Choose a product first.');
            return false;
        }

        $unitCount = ProductUnit::query()->where('product_id', $this->productId)->count();

        if ($unitCount < 2) {
            $this->dispatch('toast', message: 'Add at least two counting units for this product.');
            return false;
        }

        return true;
    }
}
