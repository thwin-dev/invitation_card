<?php
namespace App\Livewire;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;
use Livewire\WithPagination;
class CategoryTable extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;
    public string $search = '';
    public string $toast = '';
    public bool $createOpen = false;
    public string $createCode = '';
    public string $createName = '';
    public function openCreate(): void { $this->authorizePosPermission('categories', 'create'); $this->createOpen = true; }
    public function closeCreate(): void { $this->createOpen = false; }
    public function createCategory(): void { $this->authorizePosPermission('categories', 'create'); $this->validate(['createCode' => ['nullable', 'string', 'max:255'], 'createName' => ['required', 'string', 'max:255']]); ProductCategory::query()->create(['code' => $this->createCode, 'name' => $this->createName, 'created_by' => auth()->id()]); $this->createCode = ''; $this->createName = ''; $this->createOpen = false; $this->toast = 'Category created.'; }
    public ?int $editingId = null;
    public string $editCode = '';
    public string $editName = '';
    public function editCategory(int $categoryId): void { $this->authorizePosPermission('categories', 'edit'); $category = ProductCategory::query()->findOrFail($categoryId); $this->editingId = $category->id; $this->editCode = (string) $category->code; $this->editName = $category->name; }
    public function closeEdit(): void { $this->editingId = null; }
    public function updateCategory(): void { $this->authorizePosPermission('categories', 'edit'); $this->validate(['editCode' => ['nullable', 'string', 'max:255'], 'editName' => ['required', 'string', 'max:255']]); ProductCategory::query()->findOrFail($this->editingId)->update(['code' => $this->editCode, 'name' => $this->editName]); $this->editingId = null; $this->toast = 'Category updated.'; }
    public function mount(): void { $this->search = (string) request('search', ''); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function toggleCategory(int $categoryId): void
    {
        $this->authorizePosPermission('categories', 'edit');
        $category = ProductCategory::query()->findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
        $this->toast = 'Category status updated.';
    }
    public function render() { $this->authorizePosPermission('categories', 'view'); $categories = ProductCategory::query()->with('subcategories')->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")))->orderBy('name')->paginate(15); return view('livewire.category-table', compact('categories')); }
}
