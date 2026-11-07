@extends('layouts.management')

@section('title', 'Stock Movements')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('management.stock.index') }}" class="text-sm font-medium text-brand-500">Back to stock</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">Stock movement history</h1>
        <p class="mt-2 text-sm text-gray-500">Persistent movement records with before and after balances.</p>
    </div>
</div>

<livewire:stock-movement-table />
@endsection
