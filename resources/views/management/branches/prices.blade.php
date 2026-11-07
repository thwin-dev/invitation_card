@extends('layouts.management')

@section('title', 'Branch prices')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('management.branches.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Shops &amp; warehouses</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">Branch prices</h1>
            <p class="mt-1 text-sm text-gray-500">Set shop-specific prices. Leave a price blank to use the product default.</p>
        </div>
    </div>
    <livewire:branch-price-table />
@endsection
