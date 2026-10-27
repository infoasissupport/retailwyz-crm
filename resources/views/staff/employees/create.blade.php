@extends('layouts.app', ['breadcrumbs' => ['Employees' => route('staff.employees.index'), 'Add employee' => null]])

@section('title', 'Add employee')

@section('content')
    <form method="POST" action="{{ route('staff.employees.store') }}" style="max-width: 960px">
        @csrf
        @include('staff.employees.partials.form')

        <button type="submit" class="btn btn-primary">Save employee</button>
        <a href="{{ route('staff.employees.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
