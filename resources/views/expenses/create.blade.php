@extends('layouts.app', ['breadcrumbs' => ['Expenses' => route('expenses.index'), 'New expense' => null]])

@section('title', 'New expense')

@section('content')
    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
        @csrf
        @include('expenses.partials.form')

        <button type="submit" class="btn btn-primary">Save expense</button>
        <a href="{{ route('expenses.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
