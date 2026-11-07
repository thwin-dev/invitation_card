@extends('layouts.management')

@section('title', 'Suppliers')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Suppliers</h1>
        <p class="mt-2 text-sm text-gray-500">Manage supplier profiles, purchase history, and payable balances.</p>
    </div>
    @if(auth('web')->user()?->hasPermissionTo('suppliers', 'create'))<button type="button" onclick="Livewire.dispatch('open-supplier-create')" class="pos-button">
        <i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add supplier
    </button>@endif
</div>

<livewire:supplier-table />
@endsection
