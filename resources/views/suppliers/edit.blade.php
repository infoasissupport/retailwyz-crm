@extends('layouts.app', ['breadcrumbs' => ['Suppliers' => route('suppliers.index'), $supplier->name => route('suppliers.show', $supplier), 'Edit' => null]])

@section('title', 'Edit supplier')

@section('content')
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')
        @include('suppliers.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
