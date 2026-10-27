@extends('layouts.app', ['breadcrumbs' => ['Leads' => route('leads.index'), $lead->name => route('leads.show', $lead), 'Edit' => null]])

@section('title', 'Edit lead')

@section('content')
    <form method="POST" action="{{ route('leads.update', $lead) }}" class="fw-maxw-960">
        @csrf
        @method('PUT')
        @include('leads.partials.form')

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('leads.show', $lead) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
