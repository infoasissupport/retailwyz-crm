@extends('layouts.app', ['breadcrumbs' => ['Deliveries' => route('deliveries.index'), 'Schedule delivery' => null]])

@section('title', 'Schedule delivery')

@section('content')
    <form method="POST" action="{{ route('deliveries.store') }}" class="fw-maxw-960">
        @csrf
        @include('deliveries.partials.form')

        <button type="submit" class="btn fw-btn-accent">Schedule delivery</button>
        <a href="{{ route('deliveries.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
