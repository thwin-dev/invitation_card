@php($prefix = $mode === 'create' ? 'create' : 'edit')

<label class="block text-sm font-medium text-gray-700">Name
    <input wire:model="{{ $prefix }}Name" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'Name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Phone
    <input wire:model="{{ $prefix }}Phone" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($prefix.'Phone') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Address
    <textarea wire:model="{{ $prefix }}Address" rows="3" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"></textarea>
    @error($prefix.'Address') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="block text-sm font-medium text-gray-700">Customer level
    <select wire:model.live="{{ $prefix }}CustomerLevel" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        <option value="1">Level 1 (Standard)</option>
        <option value="2">Level 2 (Silver)</option>
        <option value="3">Level 3 (VIP / Gold)</option>
    </select>
    @error($prefix.'CustomerLevel') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
</label>
<label class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-700">
    <div>
        <span class="block text-sm font-medium text-gray-900">Allow credit</span>
        <span class="block text-xs text-gray-500">Permit this customer to make purchases on credit</span>
    </div>
    <button type="button" wire:click="$toggle('{{ $prefix }}AllowCredit')" class="relative h-6 w-11 shrink-0 rounded-full p-1 transition-colors {{ ${$prefix.'AllowCredit'} ? 'bg-[#465fff]' : 'bg-gray-300' }}" aria-label="Toggle credit">
        <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform {{ ${$prefix.'AllowCredit'} ? 'translate-x-5' : 'translate-x-0' }}"></span>
    </button>
</label>

@if(${$prefix.'AllowCredit'})
    <label class="block text-sm font-medium text-gray-700">Credit limit (Credit amount)
        <input type="number" step="0.01" min="0" wire:model="{{ $prefix }}CreditLimit" placeholder="e.g. 100000" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        <span class="mt-1 block text-xs text-gray-500">Maximum allowable outstanding credit balance (0 for unlimited).</span>
        @error($prefix.'CreditLimit') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>
@endif
