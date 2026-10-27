@extends('layouts.app', ['breadcrumbs' => ['Categories' => route('categories.index'), 'New category' => null]])

@section('title', 'New category')

@section('content')
    <form method="POST" action="{{ route('categories.store') }}">
        @csrf
        @include('categories.partials.form')

        <button type="submit" class="btn btn-primary">Save category</button>
        <a href="{{ route('categories.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
