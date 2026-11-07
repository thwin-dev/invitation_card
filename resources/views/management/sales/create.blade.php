@extends('layouts.management')

@section('title', 'New Sale')

@section('content')
    <livewire:sale-screen :selected-customer="$selectedCustomer" />
@endsection
