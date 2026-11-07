@extends('layouts.management')
@section('title', 'Stock')
@section('content')
<div class="mb-8"><h1 class="mt-1 text-2xl font-semibold tracking-tight">Stock dashboard</h1><p class="mt-2 text-sm text-gray-500">Review quantities, reorder levels, and unit prices.</p></div>
<livewire:stock-table :mode="$mode" />
@endsection
