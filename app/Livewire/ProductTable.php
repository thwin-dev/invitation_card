<?php

namespace App\Livewire;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductBrand;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductTable extends Component
{
    use WithFileUploads, WithPagination;
    use AuthorizesPosPermissions;

    public string $search = '';

    public string $categoryId = '';

    public string $subcategoryId = '';

    public string $brandFilter = '';

    public bool $open = false;

    #[Locked]
    public ?int $editingId = null;

    public array $form = [];

    public $photo;

    public function mount(): void
    {
        $this->search = (string) request('search', '');
        $this->categoryId = (string) request('category_id', '');
        $this->subcategoryId = (string) request('subcategory_id', '');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'categoryId', 'subcategoryId', 'brandFilter'])) {
            $this->resetPage();
        }
        if ($property === 'form.category_id') {
            $this->form['subcategory_id'] = '';
        }
    }

    public function edit(?int $id = null): void
    {
        $this->authorizePosPermission('products', $id ? 'edit' : 'create');
        $this->resetValidation();
        $this->photo = null;
        $this->editingId = $id;
        $this->form = $id ? Product::findOrFail($id)->only(['name', 'code', 'category_id', 'subcategory_id', 'brand_id', 'customer_visible']) :
            ['name' => '', 'code' => 'PRD-'.Str::upper((string) Str::ulid()), 'category_id' => '', 'subcategory_id' => '', 'brand_id' => '', 'customer_visible' => true];
        $this->open = true;
    }

    public function generateCode(): void
    {
        $this->authorizePosPermission('products', $this->editingId ? 'edit' : 'create');
        $this->form['code'] = 'PRD-'.Str::upper((string) Str::ulid());
    }

    public function save(): void
    {
        $this->authorizePosPermission('products', $this->editingId ? 'edit' : 'create');
        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.code' => ['required', 'string', 'max:255', Rule::unique('products', 'code')->ignore($this->editingId)],
            'form.category_id' => 'required|exists:product_categories,id',
            'form.subcategory_id' => ['required', Rule::exists('product_subcategories', 'id')->where('category_id', $this->form['category_id'] ?? null)],
            'form.brand_id' => 'nullable|exists:product_brands,id', 'form.customer_visible' => 'required|boolean',
            'photo' => 'nullable|image|max:5120',
        ])['form'];
        $data['brand_id'] = $data['brand_id'] ?: null;
        $product = $this->editingId ? Product::findOrFail($this->editingId) : new Product(['created_by' => auth('web')->id()]);
        $oldPhoto = $product->photo_path;
        if ($this->photo) {
            $data['photo_path'] = $this->photo->store('products', 'public');
        }
        try {
            $product->fill($data)->save();
        } catch (\Throwable $e) {
            if (isset($data['photo_path'])) {
                Storage::disk('public')->delete($data['photo_path']);
            } throw $e;
        }
        if ($this->photo && $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }
        $this->open = false;
        $this->photo = null;
        $this->dispatch('toast', message: 'Product saved.');
    }

    public function render()
    {
        $this->authorizePosPermission('products', 'view');
        $products = Product::with(['category', 'subcategory', 'brand'])
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->subcategoryId, fn ($q) => $q->where('subcategory_id', $this->subcategoryId))
            ->when($this->brandFilter, fn ($q) => $q->where('brand_id', $this->brandFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%")))
            ->latest()->paginate(15);

        return view('livewire.product-table', [
            'products' => $products, 'categories' => ProductCategory::orderBy('name')->get(),
            'subcategories' => ProductSubcategory::where('category_id', $this->form['category_id'] ?? null)->orderBy('name')->get(),
            'brands' => ProductBrand::orderBy('name')->get(),
        ]);
    }
}
