@extends('layouts.app', ['breadcrumbs' => ['Customers' => route('customers.index'), $customer->name => route('customers.show', $customer), 'Edit' => null]])

@section('title', 'Edit customer')

@section('content')
    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf
        @method('PUT')
        @include('customers.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
