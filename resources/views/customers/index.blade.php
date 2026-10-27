@extends('layouts.app', ['breadcrumbs' => ['Customers' => null]])

@section('title', 'Customers')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2 flex-grow-1" style="max-width: 560px">
            <label class="fw-search flex-grow-1">
                <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <span class="visually-hidden">Search customers</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search customers..." class="form-control">
            </label>
            <select name="status" class="form-select" style="width: auto" onchange="this.form.submit()" aria-label="Status">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </form>

        @can('customers.manage')
            <a href="{{ route('customers.create') }}" class="btn fw-btn-accent">
                <span aria-hidden="true" class="me-1">+</span> New Customer
            </a>
        @endcan
    </div>

    <div class="row g-3">
        @forelse ($customers as $customer)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('customers.show', $customer) }}" class="fw-card fw-person-card h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-person-name text-truncate">{{ $customer->name }}</div>
                            <div class="fw-person-code">{{ $customer->code }}</div>
                        </div>
                        @unless ($customer->is_active)
                            <span class="fw-chip">Inactive</span>
                        @endunless
                    </div>

                    <ul class="fw-person-lines list-unstyled">
                        @if ($customer->phone)
                            <li>
                                <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span class="visually-hidden">Phone:</span> {{ $customer->phone }}
                            </li>
                        @endif
                        @if ($customer->email)
                            <li class="text-truncate">
                                <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                                <span class="visually-hidden">Email:</span> {{ $customer->email }}
                            </li>
                        @endif
                        @if ($customer->address)
                            <li class="text-truncate">
                                <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span class="visually-hidden">Address:</span> {{ \Illuminate\Support\Str::of($customer->address)->replace(["\r\n", "\n"], ', ')->limit(48) }}
                            </li>
                        @endif
                    </ul>

                    <div class="fw-person-foot">
                        <span>Since {{ $customer->created_at->format('d M Y') }}</span>
                        @if (isset($customer->balance_due) && (float) $customer->balance_due > 0)
                            <span class="fw-status is-warning"><span aria-hidden="true">●</span> {{ money($customer->balance_due) }} due</span>
                        @endif
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="fw-card p-5 text-center text-secondary">
                    {{ request('q') ? 'No customers match “'.request('q').'”.' : 'No customers yet.' }}
                    @can('customers.manage')<a href="{{ route('customers.create') }}">Add a customer</a>.@endcan
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $customers->links() }}</div>
@endsection
