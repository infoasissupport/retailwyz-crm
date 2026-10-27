@extends('layouts.app', ['breadcrumbs' => ['Brands' => route('brands.index'), 'New brand' => null]])

@section('title', 'New brand')

@section('content')
    <form method="POST" action="{{ route('brands.store') }}">
        @csrf
        @include('brands.partials.form')

        <button type="submit" class="btn btn-primary">Save brand</button>
        <a href="{{ route('brands.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
