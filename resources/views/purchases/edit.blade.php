@extends('layouts.app', ['breadcrumbs' => ['Purchases' => route('purchases.index'), $purchase->reference_no => route('purchases.show', $purchase), 'Edit' => null]])

@section('title', 'Edit purchase '.$purchase->reference_no)

@section('content')
    <form method="POST" action="{{ route('purchases.update', $purchase) }}">
        @csrf
        @method('PUT')
        @include('purchases.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
