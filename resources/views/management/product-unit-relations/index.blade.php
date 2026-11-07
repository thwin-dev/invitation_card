@extends('layouts.management')

@section('title', 'Unit Relations')

@section('content')
<div class="mb-8">
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">Unit relations and conversions</h1>
    <p class="mt-2 text-sm text-gray-500">Define how units relate, then convert stock between them.</p>
</div>

<livewire:product-unit-relation-table />
@endsection
