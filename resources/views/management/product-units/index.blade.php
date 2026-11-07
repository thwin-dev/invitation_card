@extends('layouts.management')

@section('title', 'Counting Units')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Counting units</h1>
        <p class="mt-2 text-sm text-gray-500">Manage stock quantities and the prices used by sales and purchases.</p>
    </div>
    @if(auth('web')->user()?->hasPermissionTo('units', 'create'))<button type="button" onclick="Livewire.dispatch('open-product-unit-create')" class="pos-button">
        <i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add counting unit
    </button>@endif
</div>

<livewire:product-unit-table />
@endsection
