@extends('layouts.management')
@section('title', 'Inventory operations')
@section('content')
    <div class="mb-6">
        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-brand-600">Inventory · Stock control</p>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Inventory operations</h1>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Review stock by location, record changes, and move items between shops and warehouses.</p>
    </div>
    <livewire:inventory-workspace />
@endsection
