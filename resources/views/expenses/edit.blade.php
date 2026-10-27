@extends('layouts.app', ['breadcrumbs' => ['Expenses' => route('expenses.index'), $expense->reference_no => null]])

@section('title', 'Edit expense')

@section('content')
    <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('expenses.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('expenses.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
