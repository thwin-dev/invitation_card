@extends('layouts.management')

@section('title', 'Customers')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Customers</h1>
        <p class="mt-2 text-sm text-gray-500">Manage customer contact details and credit eligibility.</p>
    </div>
    @if(auth('web')->user()?->hasPermissionTo('customers', 'create'))<button type="button" onclick="Livewire.dispatch('open-customer-create')" class="pos-button">
        <i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add customer
    </button>@endif
</div>

<livewire:customer-table />
@endsection
