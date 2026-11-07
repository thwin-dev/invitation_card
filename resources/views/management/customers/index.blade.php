@extends('layouts.management')

@section('title', 'Exhibitors')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Exhibitors</h1>
        <p class="mt-2 text-sm text-gray-500">Manage exhibitor contact details, tiers, logos and QR code attachments.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('management.exhibitors.sample-excel') }}" class="pos-button-secondary" title="Download Excel template with sample data">
            <i data-lucide="file-spreadsheet" class="mr-2 h-4 w-4 text-emerald-600"></i>Sample Excel
        </a>
        @if(auth('web')->user()?->hasPermissionTo('customers', 'import') || auth('web')->user()?->hasPermissionTo('exhibitors', 'import'))
            <button type="button" onclick="Livewire.dispatch('open-exhibitor-import')" class="pos-button-secondary">
                <i data-lucide="upload" class="mr-2 h-4 w-4"></i>Import Excel
            </button>
        @endif
        @if(auth('web')->user()?->hasPermissionTo('customers', 'create') || auth('web')->user()?->hasPermissionTo('exhibitors', 'create'))
            <button type="button" onclick="Livewire.dispatch('open-exhibitor-create')" class="pos-button">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add exhibitor
            </button>
        @endif
    </div>
</div>

<livewire:customer-table />
@endsection
