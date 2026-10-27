@extends('layouts.app', ['breadcrumbs' => ['Reports' => null]])

@section('title', 'Reports')

@section('content')
    <div class="list-group fw-maxw-480">
        <a href="{{ route('reports.sales') }}" class="list-group-item list-group-item-action">Sales report</a>
        <a href="{{ route('reports.purchases') }}" class="list-group-item list-group-item-action">Purchase report</a>
        <a href="{{ route('reports.stock') }}" class="list-group-item list-group-item-action">Stock &amp; valuation</a>
        @can('reports.financial')
            <a href="{{ route('reports.profit-loss') }}" class="list-group-item list-group-item-action">Profit &amp; loss</a>
            <a href="{{ route('reports.tax') }}" class="list-group-item list-group-item-action">Tax report</a>
        @endcan
    </div>
@endsection
