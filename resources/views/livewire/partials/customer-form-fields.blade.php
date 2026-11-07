@php
    $isEdit = ($mode === 'edit');
    $nameModel = $isEdit ? 'editName' : 'createName';
    $phoneModel = $isEdit ? 'editPhone' : 'createPhone';
    $addressModel = $isEdit ? 'editAddress' : 'createAddress';
    $levelModel = $isEdit ? 'editCustomerLevel' : 'createCustomerLevel';
    $logoModel = $isEdit ? 'editLogo' : 'createLogo';
    $qrModel = $isEdit ? 'editQrCode' : 'createQrCode';
    $activeLogo = $isEdit ? $editLogo : $createLogo;
    $activeQr = $isEdit ? $editQrCode : $createQrCode;
@endphp

<label class="block text-sm font-medium text-gray-700">Name
    <input wire:model="{{ $nameModel }}" required placeholder="Enter exhibitor name" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($nameModel) <span class="mt-1 block text-xs text-red-600 font-medium">{{ $message }}</span> @enderror
</label>

<label class="block text-sm font-medium text-gray-700">Phone
    <input wire:model="{{ $phoneModel }}" placeholder="e.g. 0912345678" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
    @error($phoneModel) <span class="mt-1 block text-xs text-red-600 font-medium">{{ $message }}</span> @enderror
</label>

<label class="block text-sm font-medium text-gray-700">Address
    <textarea wire:model="{{ $addressModel }}" rows="2" placeholder="Enter exhibitor address" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"></textarea>
    @error($addressModel) <span class="mt-1 block text-xs text-red-600 font-medium">{{ $message }}</span> @enderror
</label>

<label class="block text-sm font-medium text-gray-700">Exhibitor level
    <select wire:model.live="{{ $levelModel }}" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        <option value="1">Level 1 (Standard)</option>
        <option value="2">Level 2 (Silver)</option>
        <option value="3">Level 3 (VIP / Gold)</option>
    </select>
    @error($levelModel) <span class="mt-1 block text-xs text-red-600 font-medium">{{ $message }}</span> @enderror
</label>

{{-- Exhibitor Logo Upload --}}
<div class="rounded-xl border border-gray-200 bg-gray-50/50 p-3.5 space-y-2">
    <div class="flex items-center justify-between">
        <span class="block text-sm font-semibold text-gray-800">Exhibitor Logo</span>
        <span class="text-xs text-gray-400">PNG, JPG, SVG, WebP up to 10MB</span>
    </div>

    @if($isEdit && $currentLogoUrl && !$removeEditLogo)
        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-2">
            <div class="flex items-center gap-3">
                <img src="{{ $currentLogoUrl }}" alt="Logo" class="h-10 w-10 rounded-lg object-contain bg-gray-50 border border-gray-200 shadow-xs">
                <span class="text-xs text-gray-600">Current Logo</span>
            </div>
            <label class="flex items-center gap-1.5 text-xs text-red-600 cursor-pointer font-medium hover:text-red-700">
                <input type="checkbox" wire:model.live="removeEditLogo" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                Remove
            </label>
        </div>
    @endif

    @if($isEdit && $removeEditLogo)
        <p class="text-xs text-amber-600 font-medium flex items-center gap-1">
            <i data-lucide="info" class="h-3.5 w-3.5"></i> Logo will be removed upon saving.
        </p>
    @endif

    @if($activeLogo)
        @php
            $logoTempUrl = null;
            try {
                if (method_exists($activeLogo, 'temporaryUrl')) {
                    $logoTempUrl = $activeLogo->temporaryUrl();
                }
            } catch (\Throwable $e) {
                $logoTempUrl = null;
            }
        @endphp
        @if($logoTempUrl)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-brand-200 bg-brand-50/60 p-2">
                <div class="flex items-center gap-3">
                    <img src="{{ $logoTempUrl }}" alt="Preview" class="h-10 w-10 rounded-lg object-contain bg-white border border-brand-200 shadow-xs">
                    <span class="text-xs font-medium text-brand-700">New logo ready to upload</span>
                </div>
                <button type="button" wire:click="$set('{{ $logoModel }}', null)" class="text-xs font-medium text-red-600 hover:text-red-700">Clear</button>
            </div>
        @else
            <div class="flex items-center justify-between gap-2 text-xs font-medium text-brand-700 bg-brand-50 p-2 rounded-lg border border-brand-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    <span>New logo file selected</span>
                </div>
                <button type="button" wire:click="$set('{{ $logoModel }}', null)" class="text-xs font-medium text-red-600 hover:text-red-700">Clear</button>
            </div>
        @endif
    @endif

    <input type="file" wire:model="{{ $logoModel }}" accept=".png,.jpg,.jpeg,.svg,.webp" class="mt-1 block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100 cursor-pointer border border-gray-300 rounded-lg bg-white p-1">
    <div wire:loading wire:target="{{ $logoModel }}" class="text-xs text-brand-600 font-medium">Uploading logo...</div>
    @error($logoModel) <span class="mt-1 block text-xs text-red-600 font-medium">{{ $message }}</span> @enderror
</div>

{{-- Exhibitor QR Attachment Upload --}}
<div class="rounded-xl border border-gray-200 bg-gray-50/50 p-3.5 space-y-2">
    <div class="flex items-center justify-between">
        <span class="block text-sm font-semibold text-gray-800">Exhibitor QR Attachment</span>
        <span class="text-xs text-gray-400">QR code image up to 10MB</span>
    </div>

    @if($isEdit && $currentQrCodeUrl && !$removeEditQrCode)
        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-2">
            <div class="flex items-center gap-3">
                <img src="{{ $currentQrCodeUrl }}" alt="QR Code" class="h-10 w-10 rounded-lg object-contain bg-gray-50 border border-gray-200 shadow-xs">
                <span class="text-xs text-gray-600">Current QR Code</span>
            </div>
            <label class="flex items-center gap-1.5 text-xs text-red-600 cursor-pointer font-medium hover:text-red-700">
                <input type="checkbox" wire:model.live="removeEditQrCode" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                Remove
            </label>
        </div>
    @endif

    @if($isEdit && $removeEditQrCode)
        <p class="text-xs text-amber-600 font-medium flex items-center gap-1">
            <i data-lucide="info" class="h-3.5 w-3.5"></i> QR code will be removed upon saving.
        </p>
    @endif

    @if($activeQr)
        @php
            $qrTempUrl = null;
            try {
                if (method_exists($activeQr, 'temporaryUrl')) {
                    $qrTempUrl = $activeQr->temporaryUrl();
                }
            } catch (\Throwable $e) {
                $qrTempUrl = null;
            }
        @endphp
        @if($qrTempUrl)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-brand-200 bg-brand-50/60 p-2">
                <div class="flex items-center gap-3">
                    <img src="{{ $qrTempUrl }}" alt="Preview" class="h-10 w-10 rounded-lg object-contain bg-white border border-brand-200 shadow-xs">
                    <span class="text-xs font-medium text-brand-700">New QR code ready to upload</span>
                </div>
                <button type="button" wire:click="$set('{{ $qrModel }}', null)" class="text-xs font-medium text-red-600 hover:text-red-700">Clear</button>
            </div>
        @else
            <div class="flex items-center justify-between gap-2 text-xs font-medium text-brand-700 bg-brand-50 p-2 rounded-lg border border-brand-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    <span>New QR code file selected</span>
                </div>
                <button type="button" wire:click="$set('{{ $qrModel }}', null)" class="text-xs font-medium text-red-600 hover:text-red-700">Clear</button>
            </div>
        @endif
    @endif

    <input type="file" wire:model="{{ $qrModel }}" accept=".png,.jpg,.jpeg,.svg,.webp" class="mt-1 block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100 cursor-pointer border border-gray-300 rounded-lg bg-white p-1">
    <div wire:loading wire:target="{{ $qrModel }}" class="text-xs text-brand-600 font-medium">Uploading QR attachment...</div>
    @error($qrModel) <span class="mt-1 block text-xs text-red-600 font-medium">{{ $message }}</span> @enderror
</div>
