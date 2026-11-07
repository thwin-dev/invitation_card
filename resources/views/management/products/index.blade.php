@extends('layouts.management')
@section('title', 'Products')
@section('content')
<div class="mb-6"><h1 class="text-2xl font-semibold">Products</h1><p class="mt-2 text-sm text-gray-500">Manage products, images, brands, and their sellable variants.</p></div>
<livewire:product-table />
@endsection
