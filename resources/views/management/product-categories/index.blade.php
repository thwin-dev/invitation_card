@extends('layouts.management')
@section('title', 'Categories')
@section('content')
<div x-data="{ open: false }">
<div class="mb-8 flex items-center justify-between gap-4"><div><h1 class="text-2xl font-semibold tracking-tight">Product categories</h1><p class="mt-2 text-sm text-gray-500">Organize products before adding stock units.</p></div>@if(auth('web')->user()?->hasPermissionTo('categories', 'create'))<button type="button" @click="$dispatch('open-category-create')" class="pos-button"><i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add category</button>@endif</div>
@if($errors->any())<div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<livewire:category-table />
</div>
@endsection
