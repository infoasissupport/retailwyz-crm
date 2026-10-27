@extends('layouts.app', ['breadcrumbs' => ['Units' => route('units.index'), 'New unit' => null]])

@section('title', 'New unit')

@section('content')
    <form method="POST" action="{{ route('units.store') }}">
        @csrf
        @include('units.partials.form')

        <button type="submit" class="btn btn-primary">Save unit</button>
        <a href="{{ route('units.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
