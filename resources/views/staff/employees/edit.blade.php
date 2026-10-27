@extends('layouts.app', ['breadcrumbs' => ['Employees' => route('staff.employees.index'), $employee->name => route('staff.employees.show', $employee), 'Edit' => null]])

@section('title', 'Edit employee')

@section('content')
    <form method="POST" action="{{ route('staff.employees.update', $employee) }}" style="max-width: 960px">
        @csrf
        @method('PUT')
        @include('staff.employees.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('staff.employees.show', $employee) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
