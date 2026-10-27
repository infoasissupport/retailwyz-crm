@extends('layouts.app', ['breadcrumbs' => ['Leads' => route('leads.index'), 'New lead' => null]])

@section('title', 'New lead')

@section('content')
    <form method="POST" action="{{ route('leads.store') }}" style="max-width: 960px">
        @csrf
        @include('leads.partials.form')

        <button type="submit" class="btn btn-primary">Save lead</button>
        <a href="{{ route('leads.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
