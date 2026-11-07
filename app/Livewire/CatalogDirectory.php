<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Product\Models\ProductBrand;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogDirectory extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;

    #[Locked]
    public string $kind = 'brands';

    #[Locked]
    public ?int $editingId = null;

    public string $search = '';

    public string $name = '';

    public string $type = 'shop';

    public bool $isMain = false;

    public bool $isActive = true;

    public string $phone = '';

    public string $address = '';

    public string $receiptHeader = '';

    public string $receiptFooter = '';

    public bool $open = false;

    public function mount(string $kind = 'brands'): void
    {
        abort_unless(in_array($kind, ['brands', 'branches']), 404);
        $this->kind = $kind;
    }

    private function model(): string
    {
        return $this->kind === 'brands' ? ProductBrand::class : InventoryBranch::class;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        if ($this->type !== 'shop') {
            $this->isMain = false;
        }
    }

    public function edit(?int $id = null): void
    {
        $this->authorizePosPermission($this->kind === 'branches' ? 'branches' : 'products', $id ? 'edit' : 'create');
        $this->resetValidation();
        $this->editingId = $id;
        $this->resetBranchFields();
        if ($id) {
            $row = $this->model()::findOrFail($id);
            $this->name = $row->name;
            if ($this->kind === 'branches') {
                $this->type = $row->type ?: 'shop';
                $this->isMain = (bool) $row->is_main;
                $this->isActive = (bool) $row->is_active;
                $this->phone = (string) ($row->phone ?? '');
                $this->address = (string) ($row->address ?? '');
                $this->receiptHeader = (string) ($row->receipt_header ?? '');
                $this->receiptFooter = (string) ($row->receipt_footer ?? '');
            }
        } else {
            $this->name = '';
        }
        $this->open = true;
    }

    public function save(): void
    {
        $this->authorizePosPermission($this->kind === 'branches' ? 'branches' : 'products', $this->editingId ? 'edit' : 'create');
        $this->name = trim($this->name);
        $this->validate(['name' => ['required', 'string', 'max:255', Rule::unique((new ($this->model()))->getTable(), 'name')->ignore($this->editingId)]]);
        if ($this->kind === 'brands') {
            ProductBrand::query()->updateOrCreate(['id' => $this->editingId], ['name' => $this->name]);
        } else {
            $this->validate([
                'type' => ['required', 'in:shop,warehouse'],
                'isMain' => ['boolean'],
                'isActive' => ['boolean'],
                'phone' => ['nullable', 'string', 'max:40'],
                'address' => ['nullable', 'string', 'max:2000'],
                'receiptHeader' => ['nullable', 'string', 'max:2000'],
                'receiptFooter' => ['nullable', 'string', 'max:2000'],
            ]);
            $existingBranch = $this->editingId ? InventoryBranch::query()->findOrFail($this->editingId) : null;
            $mainBranchId = InventoryBranch::query()->where('is_main', true)->value('id');
            if ($existingBranch?->is_main && (! $this->isMain || $this->type !== 'shop' || ! $this->isActive)) {
                throw ValidationException::withMessages(['type' => 'The Main location must remain an active shop. Its stock location cannot be changed.']);
            }
            if ($this->isMain && ! $existingBranch?->is_main && $mainBranchId) {
                throw ValidationException::withMessages(['isMain' => 'The Main location is fixed after stock tracking starts.']);
            }

            DB::transaction(function (): void {
                InventoryBranch::query()->updateOrCreate(['id' => $this->editingId], [
                    'name' => $this->name,
                    'type' => $this->type,
                    'is_main' => $this->isMain,
                    'is_active' => $this->isActive,
                    'phone' => $this->phone ?: null,
                    'address' => $this->address ?: null,
                    'receipt_header' => $this->receiptHeader ?: null,
                    'receipt_footer' => $this->receiptFooter ?: null,
                ]);
            });
        }
        $this->open = false;
        $this->dispatch('toast', message: 'Saved successfully.');
    }

    public function toggle(int $id): void
    {
        $this->authorizePosPermission('products', 'edit');
        abort_unless($this->kind === 'brands', 404);
        $brand = ProductBrand::findOrFail($id);
        $brand->update(['is_active' => ! $brand->is_active]);
        $this->dispatch('toast', message: 'Brand status updated.');
    }

    public function render()
    {
        $this->authorizePosPermission($this->kind === 'branches' ? 'branches' : 'products', 'view');
        $query = $this->model()::when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'));

        return view('livewire.catalog-directory', [
            'rows' => $query->orderBy('name')->paginate(15),
            'hasMain' => $this->kind === 'branches' && InventoryBranch::query()->where('is_main', true)->exists(),
        ]);
    }

    private function resetBranchFields(): void
    {
        $this->type = 'shop';
        $this->isMain = false;
        $this->isActive = true;
        $this->phone = '';
        $this->address = '';
        $this->receiptHeader = '';
        $this->receiptFooter = '';
    }
}
