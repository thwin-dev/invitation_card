@extends('layouts.management')

@section('title', $title)

@section('content')
<div class="mb-8"><a class="text-brand-500" href="{{ route('management.dashboard') }}" class="text-sm font-medium text-brand-500">Back to dashboard</a><h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $title }}</h1><p class="mt-2 text-sm text-gray-500">{{ $description }}</p></div>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($links as [$label, $route, $detail])
        <a href="{{ route($route) }}" class="pos-panel group p-5 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
            <div class="flex items-start justify-between gap-4"><div><h2 class="font-medium group-hover:text-emerald-800">{{ $label }}</h2><p class="mt-2 text-sm leading-6 text-gray-500">{{ $detail }}</p></div><span class="text-xl text-brand-500" aria-hidden="true">&rarr;</span></div>
        </a>
    @endforeach
</div>
@endsection
