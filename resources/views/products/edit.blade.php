@extends('layouts.app', ['breadcrumbs' => ['Products' => route('products.index'), $product->name => route('products.show', $product), 'Edit' => null]])

@section('title', 'Edit product')

@section('content')
    <form method="POST" action="{{ route('products.update', $product) }}">
        @csrf
        @method('PUT')
        @include('products.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('products.show', $product) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
