<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\ProductUnit;
use App\Support\ActivityLogger;
use App\Modules\Inventory\Support\StockMovementRecorder;
use App\Modules\Product\Models\Product;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ProductUnitTable extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;

    public array $components = [];

    public string $size = '';

    public string $color = '';

    public string $tracking = 'none';

    #[Locked]
    public int $originalQuantity = 0;

    public string $search = '';

    public string $productId = '';

    public bool $createOpen = false;

    #[Locked]
    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $createProductId = '';

    public string $createName = '';

    public string $createCode = '';

    public string $createOriginalCode = '';

    public int $createCurrentQuantity = 0;

    public int $createReorderQuantity = 0;

    public string $createNormalSalePrice = '0';

    public string $createWholeSalePrice = '0';

    public string $createOrderPrice = '0';

    public string $createPurchasePrice = '0';

    public string $editName = '';

    public string $editCode = '';

    public string $editOriginalCode = '';

    public int $editCurrentQuantity = 0;

    public int $editReorderQuantity = 0;

    public string $editNormalSalePrice = '0';

    public string $editWholeSalePrice = '0';

    public string $editOrderPrice = '0';

    public string $editPurchasePrice = '0';

    public function mount(): void
    {
        $this->productId = (string) request('product_id', '');
        $this->createProductId = $this->productId;
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'productId'], true)) {
            $this->resetPage();
        }
    }

    #[On('open-product-unit-create')]
    public function openCreate(): void
    {
        $this->resetValidation();
        $this->createProductId = $this->productId;
        $this->components = [];
        $this->size = '';
        $this->color = '';
        $this->tracking = 'none';
        $this->generateIdentifiers('create');
        $this->createOpen = true;
    }

    public function closeCreate(): void
    {
        $this->createOpen = false;
    }

    public function createUnit(): void
    {
        $this->authorizePosPermission('units', 'create');
        $data = $this->validate($this->createRules());

        $this->validateMetadata();
        DB::transaction(function () use ($data) {
            $unit = ProductUnit::query()->create([
                'size' => $this->size ?: null, 'color' => $this->color ?: null, 'tracking' => $this->tracking,
                'product_id' => $data['createProductId'],
                'name' => $data['createName'],
                'code' => $data['createCode'] ?: null,
                'original_code' => $data['createOriginalCode'] ?: null,
                'current_quantity' => $data['createCurrentQuantity'],
                'reorder_quantity' => $data['createReorderQuantity'],
                'normal_sale_price' => $data['createNormalSalePrice'],
                'whole_sale_price' => $data['createWholeSalePrice'],
                'order_price' => $data['createOrderPrice'],
                'purchase_price' => $data['createPurchasePrice'],
            ]);

            $this->saveComponents($unit);
            if ($unit->current_quantity) {
                StockMovementRecorder::record($unit, 'IN', 0, $unit->current_quantity, auth('web')->id(), 'opening', null, 'Opening stock');
            }
        });

        $this->resetCreateForm();
        $this->createOpen = false;
        $this->dispatch('toast', message: 'Counting unit created.');
    }

    public function editUnit(int $unitId): void
    {
        $this->authorizePosPermission('units', 'edit');
        $unit = ProductUnit::query()->findOrFail($unitId);
        $this->resetValidation();
        $this->editingId = $unit->id;
        $this->components = $unit->components->map(fn ($component) => ['unit_id' => (string) $component->id, 'quantity' => $component->pivot->quantity])->all();
        $this->size = $unit->size ?? '';
        $this->color = $unit->color ?? '';
        $this->tracking = $unit->tracking;
        $this->originalQuantity = $unit->current_quantity;
        $this->editName = $unit->name;
        $this->editCode = (string) $unit->code;
        $this->editOriginalCode = (string) $unit->original_code;
        $this->editCurrentQuantity = (int) $unit->current_quantity;
        $this->editReorderQuantity = (int) $unit->reorder_quantity;
        $this->editNormalSalePrice = (string) $unit->normal_sale_price;
        $this->editWholeSalePrice = (string) $unit->whole_sale_price;
        $this->editOrderPrice = (string) $unit->order_price;
        $this->editPurchasePrice = (string) $unit->purchase_price;
    }

    public function closeEdit(): void
    {
        $this->editingId = null;
    }

    public function updateUnit(): void
    {
        $this->authorizePosPermission('units', 'edit');
        $data = $this->validate($this->editRules());

        $this->validateMetadata();
        DB::transaction(function () use ($data) {
            $unit = ProductUnit::lockForUpdate()->findOrFail($this->editingId);
            if ($data['editCurrentQuantity'] !== $this->originalQuantity) {
                throw ValidationException::withMessages(['editCurrentQuantity' => 'Use Inventory operations to adjust or count stock.']);
            }
            $unit->update([
                'size' => $this->size ?: null, 'color' => $this->color ?: null, 'tracking' => $this->tracking,
                'name' => $data['editName'],
                'code' => $data['editCode'] ?: null,
                'original_code' => $data['editOriginalCode'] ?: null,
                'reorder_quantity' => $data['editReorderQuantity'],
                'normal_sale_price' => $data['editNormalSalePrice'],
                'whole_sale_price' => $data['editWholeSalePrice'],
                'order_price' => $data['editOrderPrice'],
                'purchase_price' => $data['editPurchasePrice'],
            ]);

            $this->saveComponents($unit);
        });
        $this->editingId = null;
        $this->dispatch('toast', message: 'Counting unit updated.');
    }

    public function confirmDelete(int $unitId): void
    {
        $this->deletingId = $unitId;
    }

    public function closeDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteUnit(): void
    {
        $this->authorizePosPermission('units', 'delete');
        ProductUnit::query()->findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        $this->dispatch('toast', message: 'Counting unit deleted.');
    }

    public function render()
    {
        $this->authorizePosPermission('units', 'view');
        $units = ProductUnit::query()
            ->with('product')
            ->whereHas('product')
            ->when($this->productId, fn ($query) => $query->where('product_id', $this->productId))
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('original_code', 'like', "%{$this->search}%")
                    ->orWhereHas('product', fn ($productQuery) => $productQuery
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%"));
            }))
            ->orderBy('name')
            ->paginate(15);

        $products = Product::query()->orderBy('name')->get();

        return view('livewire.product-unit-table', ['products' => $products, 'units' => $units, 'componentOptions' => ProductUnit::with('product')->whereHas('product')->where('id', '!=', $this->editingId ?? 0)->orderBy('name')->get()]);
    }

    public function generateIdentifiers(string $mode): void
    {
        $this->authorizePosPermission('units', $mode === 'edit' ? 'edit' : 'create');
        if (! in_array($mode, ['create', 'edit'])) {
            return;
        }
        $this->{$mode.'Code'} = 'SKU-'.Str::upper((string) Str::ulid());
        do {
            $base = '20'.str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) $base[$i] * ($i % 2 ? 3 : 1);
            }
            $barcode = $base.((10 - $sum % 10) % 10);
        } while (ProductUnit::withTrashed()->where('original_code', $barcode)->exists());
        $this->{$mode.'OriginalCode'} = $barcode;
    }

    public function addComponent(): void
    {
        $this->components[] = ['unit_id' => '', 'quantity' => 1];
    }

    public function removeComponent(int $index): void
    {
        unset($this->components[$index]);
        $this->components = array_values($this->components);
    }

    private function validateMetadata(): void
    {
        $this->validate([
            'size' => 'nullable|string|max:100', 'color' => 'nullable|string|max:100', 'tracking' => 'required|in:none,batch,serial',
            'components' => 'array|max:50', 'components.*.unit_id' => ['required', 'distinct', Rule::exists('product_units', 'id')->whereNull('deleted_at')],
            'components.*.quantity' => 'required|integer|min:1|max:100000',
        ]);
        if ($this->createOpen && ($this->tracking !== 'none' || $this->components) && $this->createCurrentQuantity > 0) {
            throw ValidationException::withMessages(['createCurrentQuantity' => 'Start at zero and receive tracked stock or assemble bundles through Inventory operations.']);
        }
    }

    private function saveComponents(ProductUnit $unit): void
    {
        $ids = array_column($this->components, 'unit_id');
        if (in_array($unit->id, $ids)) {
            throw ValidationException::withMessages(['components' => 'A bundle cannot contain itself.']);
        }
        // Keep recipes one level deep to avoid cycles and ambiguous assembly.
        if (DB::table('bundle_components')->whereIn('bundle_unit_id', $ids)->exists() ||
            ($ids && DB::table('bundle_components')->where('component_unit_id', $unit->id)->exists())) {
            throw ValidationException::withMessages(['components' => 'Nested bundles are not supported. Choose individual component units.']);
        }

        $before = $unit->components()->get()->mapWithKeys(fn (ProductUnit $component): array => [
            (string) $component->id => (int) $component->pivot->quantity,
        ])->all();
        $after = collect($this->components)->mapWithKeys(fn (array $row): array => [
            (string) $row['unit_id'] => (int) $row['quantity'],
        ])->all();
        ksort($before);
        ksort($after);

        $unit->components()->sync(collect($this->components)->mapWithKeys(fn ($row) => [$row['unit_id'] => ['quantity' => $row['quantity']]])->all());

        if ($before !== $after) {
            ActivityLogger::record('units', 'updated', 'Updated bundle recipe for '.$unit->name, $unit, [
                'components' => ['from' => $before, 'to' => $after],
            ]);
        }
    }

    private function createRules(): array
    {
        return [
            'createProductId' => ['required', 'exists:products,id'],
            'createName' => ['required', 'string', 'max:255'],
            'createCode' => ['nullable', 'string', 'max:255', Rule::unique('product_units', 'code')->ignore(null)],
            'createOriginalCode' => ['nullable', 'string', 'max:255', Rule::unique('product_units', 'original_code')->ignore(null)],
            'createCurrentQuantity' => ['required', 'integer', 'min:0'],
            'createReorderQuantity' => ['required', 'integer', 'min:0'],
            'createNormalSalePrice' => ['required', 'numeric', 'min:0'],
            'createWholeSalePrice' => ['required', 'numeric', 'min:0'],
            'createOrderPrice' => ['required', 'numeric', 'min:0'],
            'createPurchasePrice' => ['required', 'numeric', 'min:0'],
        ];
    }

    private function editRules(): array
    {
        return [
            'editName' => ['required', 'string', 'max:255'],
            'editCode' => ['nullable', 'string', 'max:255', Rule::unique('product_units', 'code')->ignore($this->editingId)],
            'editOriginalCode' => ['nullable', 'string', 'max:255', Rule::unique('product_units', 'original_code')->ignore($this->editingId)],
            'editCurrentQuantity' => ['required', 'integer', 'min:0'],
            'editReorderQuantity' => ['required', 'integer', 'min:0'],
            'editNormalSalePrice' => ['required', 'numeric', 'min:0'],
            'editWholeSalePrice' => ['required', 'numeric', 'min:0'],
            'editOrderPrice' => ['required', 'numeric', 'min:0'],
            'editPurchasePrice' => ['required', 'numeric', 'min:0'],
        ];
    }

    private function resetCreateForm(): void
    {
        $this->createProductId = $this->productId;
        $this->createName = '';
        $this->createCode = '';
        $this->createOriginalCode = '';
        $this->createCurrentQuantity = 0;
        $this->createReorderQuantity = 0;
        $this->createNormalSalePrice = '0';
        $this->createWholeSalePrice = '0';
        $this->createOrderPrice = '0';
        $this->createPurchasePrice = '0';
    }
}
