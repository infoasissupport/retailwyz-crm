@extends('layouts.app', ['breadcrumbs' => ['Customers' => route('customers.index'), 'New customer' => null]])

@section('title', 'New customer')

@section('content')
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        @include('customers.partials.form')

        <button type="submit" class="btn btn-primary">Save customer</button>
        <a href="{{ route('customers.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
