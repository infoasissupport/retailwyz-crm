@extends('layouts.app', ['breadcrumbs' => ['Brands' => route('brands.index'), $brand->name => null, 'Edit' => null]])

@section('title', 'Edit brand')

@section('content')
    <form method="POST" action="{{ route('brands.update', $brand) }}">
        @csrf
        @method('PUT')
        @include('brands.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('brands.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
