@extends('layouts.management')

@section('title', 'Reports')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Reports</h1>
        <p class="mt-1 text-sm text-gray-500">View financial performance and sales by shop.</p>
    </div>

    <nav aria-label="Report types" class="mb-6 flex gap-2 border-b border-gray-200">
        <a href="{{ route('management.reports.financial') }}" @class([
            'border-b-2 px-4 py-3 text-sm font-medium',
            'border-brand-500 text-brand-600' => $activeReport === 'financial',
            'border-transparent text-gray-500 hover:text-gray-700' => $activeReport !== 'financial',
        ]) aria-current="{{ $activeReport === 'financial' ? 'page' : 'false' }}">Financial</a>
        <a href="{{ route('management.reports.financial', ['tab' => 'branches']) }}" @class([
            'border-b-2 px-4 py-3 text-sm font-medium',
            'border-brand-500 text-brand-600' => $activeReport === 'branches',
            'border-transparent text-gray-500 hover:text-gray-700' => $activeReport !== 'branches',
        ]) aria-current="{{ $activeReport === 'branches' ? 'page' : 'false' }}">Branches</a>
        <a href="{{ route('management.reports.financial', ['tab' => 'discounts']) }}" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeReport === 'discounts' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500' }}" aria-current="{{ $activeReport === 'discounts' ? 'page' : 'false' }}">Discounts</a>
    </nav>

    @if($activeReport === 'branches')
        <livewire:branch-report />
    @elseif($activeReport === 'discounts')
        <livewire:discount-report />
    @else
        <livewire:financial-report />
    @endif
@endsection
