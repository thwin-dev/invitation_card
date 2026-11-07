@extends('layouts.management')

@section('title', 'Purchases')

@section('content')
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Purchase history</h1>
            <p class="mt-1 text-sm text-gray-500">Review stock purchases recorded from suppliers.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth('web')->user()?->hasPermissionTo('purchases', 'export'))<a href="{{ route('management.purchases.export') }}" class="pos-button-secondary">
                <i data-lucide="download" class="mr-2 h-4 w-4"></i>
                Export CSV
            </a>
            @endif
            @if(auth('web')->user()?->hasPermissionTo('purchases', 'create'))<a href="{{ route('management.purchases.create') }}" class="pos-button">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Record purchase
            </a>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div
            x-data
            x-init="$dispatch('pos-toast', { type: 'success', title: 'Success', message: @js(session('status')) })"
        ></div>
    @endif

    <livewire:purchase-table />
@endsection
