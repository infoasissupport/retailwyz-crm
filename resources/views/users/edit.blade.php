@extends('layouts.app', ['breadcrumbs' => ['Users' => route('users.index'), $user->name => route('users.show', $user), 'Edit' => null]])

@section('title', 'Edit user')

@section('content')
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('users.show', $user) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
