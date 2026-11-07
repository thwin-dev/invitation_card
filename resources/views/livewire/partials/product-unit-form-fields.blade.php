@php($prefix = $mode === 'create' ? 'create' : 'edit')

@if($mode === 'create')
    <label class="block text-sm font-medium text-gray-700 md:col-span-2">Product
        <select wire:model="createProductId" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
            <option value="">Choose product</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->code }} · {{ $product->name }}</option>
            @endforeach
        </select>
        @error('createProductId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>
@endif

<label class="block text-sm font-medium text-gray-700">Unit name
    <input wire:model="{{ $prefix }}Name" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'Name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">SKU
    <input wire:model="{{ $prefix }}Code" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'Code') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Barcode (EAN-13 or supplier code)
    <input wire:model="{{ $prefix }}OriginalCode" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'OriginalCode') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Current quantity
    <input type="number" min="0" @disabled($mode === 'edit') wire:model="{{ $prefix }}CurrentQuantity" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'CurrentQuantity') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Reorder quantity
    <input type="number" min="0" wire:model="{{ $prefix }}ReorderQuantity" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'ReorderQuantity') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Sale price
    <input type="number" step="0.01" min="0" wire:model="{{ $prefix }}NormalSalePrice" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'NormalSalePrice') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Wholesale price
    <input type="number" step="0.01" min="0" wire:model="{{ $prefix }}WholeSalePrice" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'WholeSalePrice') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Order price
    <input type="number" step="0.01" min="0" wire:model="{{ $prefix }}OrderPrice" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'OrderPrice') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Purchase price
    <input type="number" step="0.01" min="0" wire:model="{{ $prefix }}PurchasePrice" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'PurchasePrice') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>

<div class="md:col-span-2"><button type="button" wire:click="generateIdentifiers('{{ $mode }}')" class="pos-button-secondary">Generate SKU and barcode</button><p class="mt-2 text-xs text-gray-500">Change existing stock through Inventory operations. Generated barcodes are for internal use.</p></div>
<label class="block text-sm font-medium text-gray-700">Size<input wire:model="size" class="mt-1 w-full rounded-lg border border-gray-300 p-2"></label>
<label class="block text-sm font-medium text-gray-700">Color<input wire:model="color" class="mt-1 w-full rounded-lg border border-gray-300 p-2"></label>
<label class="block text-sm font-medium text-gray-700">Stock tracking<select wire:model="tracking" class="mt-1 w-full rounded-lg border border-gray-300 p-2"><option value="none">Standard</option><option value="batch">Batch</option><option value="serial">Serial number</option></select></label>
<div class="space-y-3 md:col-span-2"><h3 class="font-semibold">Bundle / combo components</h3><p class="text-xs text-gray-500">Leave empty for an individual item. Assemble combos in Inventory operations before selling them.</p>
@foreach($components as $index => $component)<div wire:key="component-{{ $index }}" class="flex items-end gap-2"><label class="flex-1 text-sm">Component<select wire:model="components.{{ $index }}.unit_id" class="mt-1 w-full rounded-lg border border-gray-300 p-2"><option value="">Choose unit</option>@foreach($componentOptions as $option)<option value="{{ $option->id }}">{{ $option->product->name }} / {{ $option->name }}</option>@endforeach</select></label><label class="text-sm">Quantity<input type="number" min="1" wire:model="components.{{ $index }}.quantity" class="mt-1 w-24 rounded-lg border border-gray-300 p-2"></label><button type="button" wire:click="removeComponent({{ $index }})" class="pos-button-secondary">Remove</button></div>@endforeach
<button type="button" wire:click="addComponent" class="pos-button-secondary">Add component</button></div>
@if($errors->any())<ul role="alert" class="text-sm text-red-600 md:col-span-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
