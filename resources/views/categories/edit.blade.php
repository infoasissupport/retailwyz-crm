@extends('layouts.app', ['breadcrumbs' => ['Categories' => route('categories.index'), $category->name => null, 'Edit' => null]])

@section('title', 'Edit category')

@section('content')
    <form method="POST" action="{{ route('categories.update', $category) }}">
        @csrf
        @method('PUT')
        @include('categories.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('categories.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
