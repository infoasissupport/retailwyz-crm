@extends('layouts.app', ['breadcrumbs' => ['Units' => route('units.index'), $unit->name => null, 'Edit' => null]])

@section('title', 'Edit unit')

@section('content')
    <form method="POST" action="{{ route('units.update', $unit) }}">
        @csrf
        @method('PUT')
        @include('units.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('units.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
