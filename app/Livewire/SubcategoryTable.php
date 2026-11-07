<?php
namespace App\Livewire;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;
use Livewire\WithPagination;
class SubcategoryTable extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;
    public string $search = '';
    public string $categoryId = '';
    public ?int $editingId = null;
    public string $editCode = '';
    public string $editName = '';
    public string $editCategoryId = '';
    public function editSubcategory(int $subcategoryId): void { $this->authorizePosPermission('subcategories', 'edit'); $subcategory = ProductSubcategory::query()->findOrFail($subcategoryId); $this->editingId = $subcategory->id; $this->editCode = (string) $subcategory->code; $this->editName = $subcategory->name; $this->editCategoryId = (string) $subcategory->category_id; }
    public function closeEdit(): void { $this->editingId = null; }
    public function updateSubcategory(): void { $this->authorizePosPermission('subcategories', 'edit'); $this->validate(['editCode' => ['nullable', 'string', 'max:255'], 'editName' => ['required', 'string', 'max:255'], 'editCategoryId' => ['required', 'exists:product_categories,id']]); ProductSubcategory::query()->findOrFail($this->editingId)->update(['code' => $this->editCode, 'name' => $this->editName, 'category_id' => $this->editCategoryId]); $this->editingId = null; $this->dispatch('toast', message: 'Subcategory updated.'); }
    public function mount(): void { $this->search = (string) request('search', ''); $this->categoryId = (string) request('category_id', ''); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategoryId(): void { $this->resetPage(); }
    public function toggleSubcategory(int $subcategoryId): void
    {
        $this->authorizePosPermission('subcategories', 'edit');
        $subcategory = ProductSubcategory::query()->findOrFail($subcategoryId);
        $subcategory->update(['is_active' => ! $subcategory->is_active]);
        $this->dispatch('toast', message: 'Subcategory status updated.');
    }
    public function render() { $this->authorizePosPermission('subcategories', 'view'); $subcategories = ProductSubcategory::query()->with('category')->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")))->orderBy('name')->paginate(15); $categories = ProductCategory::query()->orderBy('name')->get(); $editingSubcategory = $this->editingId ? ProductSubcategory::query()->find($this->editingId) : null; return view('livewire.subcategory-table', compact('subcategories', 'categories', 'editingSubcategory')); }
}
