@extends('layouts.management')

@section('title', 'Orders')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Customer orders</h1>
            <p class="mt-2 text-sm text-gray-500">Track requested items and delivery status.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if(auth('web')->user()?->hasPermissionTo('orders', 'export'))<a href="{{ route('management.orders.export') }}" class="pos-button-secondary">
                <i data-lucide="download" class="mr-2 h-4 w-4"></i>
                Export CSV
            </a>
            @endif
            @if(auth('web')->user()?->hasPermissionTo('orders', 'create'))<a href="{{ route('management.orders.create') }}" class="pos-button">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                New order
            </a>
            @endif
        </div>
    </div>

    <livewire:order-table />
@endsection
