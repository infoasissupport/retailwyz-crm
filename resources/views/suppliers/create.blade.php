@extends('layouts.app', ['breadcrumbs' => ['Suppliers' => route('suppliers.index'), 'New supplier' => null]])

@section('title', 'New supplier')

@section('content')
    <form method="POST" action="{{ route('suppliers.store') }}">
        @csrf
        @include('suppliers.partials.form')

        <button type="submit" class="btn btn-primary">Save supplier</button>
        <a href="{{ route('suppliers.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
