@extends('layouts.app', ['breadcrumbs' => ['Products' => route('products.index'), 'New product' => null]])

@section('title', 'New product')

@section('content')
    <form method="POST" action="{{ route('products.store') }}">
        @csrf
        @include('products.partials.form')

        <button type="submit" class="btn btn-primary">Save product</button>
        <a href="{{ route('products.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
