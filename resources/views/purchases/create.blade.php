@extends('layouts.app', ['breadcrumbs' => ['Purchases' => route('purchases.index'), 'New purchase' => null]])

@section('title', 'New purchase')

@section('content')
    <form method="POST" action="{{ route('purchases.store') }}">
        @csrf
        @include('purchases.partials.form')

        <button type="submit" class="btn btn-primary">Save purchase</button>
        <a href="{{ route('purchases.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
