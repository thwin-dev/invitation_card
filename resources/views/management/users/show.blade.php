@extends('layouts.management')

@section('title', 'Employee details')

@section('content')
<livewire:user-detail :user="$user" />
@endsection
