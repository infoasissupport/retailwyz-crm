@extends('layouts.app', ['breadcrumbs' => ['Inventory' => route('inventory.index'), 'Adjustments' => null]])

@section('title', 'Stock adjustments')

@section('page-actions')
    @can('inventory.adjust')
        <a href="{{ route('inventory.stock-in') }}" class="btn btn-sm btn-outline-primary">Stock in</a>
        <a href="{{ route('inventory.stock-out') }}" class="btn btn-sm btn-outline-primary">Stock out</a>
    @endcan
@endsection

@section('content')
    <form method="GET" class="fw-grid gap-2">
        <div class="fw-span-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="fw-span-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Reference</th><th>Date</th><th>Branch</th><th>Type</th><th>Reason</th>
                    <th class="text-end">Items</th><th>Status</th><th>By</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->reference_no }}</td>
                        <td>{{ $adjustment->adjustment_date->format('d-m-Y') }}</td>
                        <td>{{ $adjustment->branch->name }}</td>
                        <td>{{ ucfirst($adjustment->type) }}</td>
                        <td>{{ $adjustment->reason }}</td>
                        <td class="text-end">{{ $adjustment->items_count }}</td>
                        <td>
                            {{ $adjustment->status->label() }}
                            @if ($adjustment->approver)
                                <span class="small text-muted">({{ $adjustment->approver->name }})</span>
                            @endif
                        </td>
                        <td>{{ $adjustment->creator?->name }}</td>
                        <td class="text-nowrap">
                            @if ($adjustment->status === \App\Enums\AdjustmentStatus::Pending)
                                @can('approve', $adjustment)
                                    <form method="POST" action="{{ route('inventory.adjustments.approve', $adjustment) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" data-confirm="Approve and apply this adjustment?">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('inventory.adjustments.reject', $adjustment) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Reject this adjustment?">Reject</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No adjustments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $adjustments->links() }}
@endsection
