@extends('layouts.management')

@section('title', 'Sales')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Sales history</h1>
        <p class="mt-2 text-sm text-gray-500">Review completed vouchers and sale totals.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(auth('web')->user()?->hasPermissionTo('discounts', 'view'))<a href="{{ route('management.discounts.index') }}" class="pos-button-secondary">Discount rules</a>@endif
        @if(auth('web')->user()?->hasPermissionTo('sales', 'export'))<a href="{{ route('management.sales.export') }}" class="pos-button-secondary"><i data-lucide="download" class="mr-2 h-4 w-4"></i>Export CSV</a>@endif
        @if(auth('web')->user()?->hasPermissionTo('sales', 'create'))<a href="{{ route('management.sales.create') }}" class="pos-button"><i data-lucide="plus" class="mr-2 h-4 w-4"></i>New sale</a>@endif
    </div>
</div>

<livewire:sale-table />
@endsection
