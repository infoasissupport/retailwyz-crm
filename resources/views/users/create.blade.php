@extends('layouts.app', ['breadcrumbs' => ['Users' => route('users.index'), 'New user' => null]])

@section('title', 'New user')

@section('content')
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users.partials.form')

        <button type="submit" class="btn btn-primary">Save user</button>
        <a href="{{ route('users.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
