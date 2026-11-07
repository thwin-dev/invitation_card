@extends('layouts.management')

@section('title', 'Record Purchase')

@section('content')
    <livewire:purchase-screen :selected-supplier-id="$selectedSupplierId" />
@endsection
