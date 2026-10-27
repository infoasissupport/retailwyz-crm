@extends('layouts.app', ['breadcrumbs' => ['Inventory' => route('inventory.index'), 'Stock out' => null]])

@section('title', 'Stock out')

@section('content')
    @include('inventory.partials.adjustment-form', ['type' => 'out'])
@endsection
