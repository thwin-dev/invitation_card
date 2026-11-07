@extends('layouts.management')

@section('title', 'Product Photo')

@section('content')
<div class="mb-8">
    <a class="text-brand-500" href="{{ route('management.products.index') }}" class="text-sm text-brand-500">Back to products</a>
    <h1 class="mt-2 text-2xl font-semibold tracking-tight">Product photo</h1>
    <p class="mt-2 text-sm text-gray-500">{{ $product->code }} · {{ $product->name }}</p>
</div>

@if (session('status'))
    <div class="mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
@endif

<section class="max-w-xl pos-panel p-5">
    @if ($product->photo_path)
        <img src="{{ str_starts_with($product->photo_path, 'images/') ? asset($product->photo_path) : \Illuminate\Support\Facades\Storage::disk('public')->url($product->photo_path) }}" alt="{{ $product->name }}" class="mb-5 max-h-72 w-full object-contain">
    @else
        <div class="mb-5 border border-dashed border-gray-300 px-5 py-16 text-center text-sm text-gray-500">No product photo uploaded.</div>
    @endif
    @if(auth('web')->user()?->hasPermissionTo('products', 'edit'))<form method="post" action="{{ route('management.products.photo.update', $product) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="file" name="photo" accept="image/*" required class="w-full border border-gray-300 px-3 py-2">
        <button class="w-full pos-button">Upload photo</button>
    </form>
    @endif
    @if ($product->photo_path && auth('web')->user()?->hasPermissionTo('products', 'delete'))
        <form method="post" action="{{ route('management.products.photo.delete', $product) }}" class="mt-3">
            @csrf
            @method('delete')
            <button class="w-full border border-red-200 px-4 py-2.5 text-sm text-red-700">Remove photo</button>
        </form>
    @endif
</section>
@endsection
