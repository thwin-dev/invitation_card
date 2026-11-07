@extends('layouts.management')
@section('title', 'Branch discount rules')
@section('content')
    <div class="mb-6"><h1 class="text-2xl font-semibold">Branch discount rules</h1><p class="mt-2 text-sm text-gray-500">Create percentage or fixed discounts per unit for each shop. Apply one rule to each sale item.</p></div>
    <livewire:discount-rule-table />
@endsection
