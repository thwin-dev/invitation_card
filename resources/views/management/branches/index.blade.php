@extends('layouts.management')
@section('title', 'Shops & warehouses')
@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-brand-600">Inventory · Locations</p>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Shops &amp; warehouses</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage sales shops, stock locations, and receipt details in one place.</p>
        </div>
    </div>
    <livewire:catalog-directory kind="branches" />
@endsection
