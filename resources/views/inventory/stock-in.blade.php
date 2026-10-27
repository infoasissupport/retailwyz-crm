@extends('layouts.app', ['breadcrumbs' => ['Inventory' => route('inventory.index'), 'Stock in' => null]])

@section('title', 'Stock in')

@section('content')
    @include('inventory.partials.adjustment-form', ['type' => 'in'])
@endsection
