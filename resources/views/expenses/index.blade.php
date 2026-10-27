@extends('layouts.app', ['breadcrumbs' => ['Expenses' => null]])

@section('title', 'Expenses')

@section('page-actions')
    <a href="{{ route('expenses.categories.index') }}" class="btn btn-sm btn-outline-secondary">Categories</a>
    @can('expenses.manage')
        <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-primary">New expense</a>
    @endcan
@endsection

@section('content')
    <form method="GET" class="fw-grid gap-2">
        <div class="fw-span-md-3">
            <select name="expense_category_id" class="form-select">
                <option value="">All categories</option>
                @foreach ($categories as $id => $name)
                    <option value="{{ $id }}" @selected(request('expense_category_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        @if ($branches->isNotEmpty())
            <div class="fw-span-md-2">
                <select name="branch_id" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="fw-span-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From"></div>
        <div class="fw-span-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To"></div>
        <div class="fw-span-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <p>Total: <strong>{{ money($total) }}</strong></p>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr><th>Date</th><th>Reference</th><th>Category</th><th>Paid to</th><th>Method</th><th class="text-end">Amount</th><th>Branch</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->format('d-m-Y') }}</td>
                        <td>{{ $expense->reference_no }}</td>
                        <td>{{ $expense->category->name }}</td>
                        <td>{{ $expense->paid_to }}</td>
                        <td>{{ $expense->payment_method->label() }}</td>
                        <td class="text-end">{{ money($expense->amount) }}</td>
                        <td>{{ $expense->branch->name }}</td>
                        <td class="text-end">
                            @can('update', $expense)
                                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-link">Edit</a>
                            @endcan
                            @can('delete', $expense)
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Delete this expense?">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No expenses found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $expenses->links() }}
@endsection
