@extends('layouts.app', ['breadcrumbs' => ['Purchases' => route('purchases.index'), $purchase->reference_no => null]])

@section('title', 'Purchase '.$purchase->reference_no)

@section('page-actions')
    @can('update', $purchase)
        <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    @endcan
    @if ($purchase->status->isEditable())
        @can('cancel', $purchase)
            <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Cancel this purchase?">Cancel purchase</button>
            </form>
        @endcan
    @endif
@endsection

@section('content')
    <dl class="fw-grid">
        <dt class="fw-span-sm-2">Supplier</dt><dd class="fw-span-sm-4">{{ $purchase->supplier->name }}</dd>
        <dt class="fw-span-sm-2">Branch</dt><dd class="fw-span-sm-4">{{ $purchase->branch->name }}</dd>
        <dt class="fw-span-sm-2">Date</dt><dd class="fw-span-sm-4">{{ $purchase->purchase_date->format('d-m-Y') }}</dd>
        <dt class="fw-span-sm-2">Supplier invoice</dt><dd class="fw-span-sm-4">{{ $purchase->supplier_invoice_no ?? '—' }}</dd>
        <dt class="fw-span-sm-2">Status</dt><dd class="fw-span-sm-4">{{ $purchase->status->label() }}</dd>
        <dt class="fw-span-sm-2">Payment</dt><dd class="fw-span-sm-4">{{ $purchase->payment_status->label() }}</dd>
        <dt class="fw-span-sm-2">Prices</dt><dd class="fw-span-sm-4">{{ $purchase->prices_include_tax ? 'Tax inclusive' : 'Tax exclusive' }}</dd>
        <dt class="fw-span-sm-2">Created by</dt><dd class="fw-span-sm-4">{{ $purchase->creator?->name }}</dd>
    </dl>

    <h2 class="h6">Items</h2>
    <form method="POST" action="{{ route('purchases.receive', $purchase) }}" id="receive-form">
        @csrf
    </form>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Product</th><th class="text-end">Ordered</th><th class="text-end">Received</th><th class="text-end">Returned</th>
                    <th class="text-end">Unit cost</th><th class="text-end">Discount</th><th class="text-end">Tax</th><th class="text-end">Total</th>
                    @if ($purchase->status->canReceive())
                        @can('receive', $purchase)<th>Receive now</th>@endcan
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}@if ($item->variant) — {{ $item->variant->name }}@endif</td>
                        <td class="text-end">{{ qty($item->quantity) }}</td>
                        <td class="text-end">{{ qty($item->received_quantity) }}</td>
                        <td class="text-end">{{ qty($item->returned_quantity) }}</td>
                        <td class="text-end">{{ money($item->unit_cost) }}</td>
                        <td class="text-end">{{ money($item->discount) }}</td>
                        <td class="text-end">{{ money($item->tax_amount) }} <span class="small text-muted">({{ qty($item->tax_rate) }}%)</span></td>
                        <td class="text-end">{{ money($item->line_total) }}</td>
                        @if ($purchase->status->canReceive())
                            @can('receive', $purchase)
                                <td>
                                    <input type="number" step="0.001" min="0" max="{{ $item->pendingQuantity() }}" form="receive-form"
                                           name="quantities[{{ $item->id }}]" value="{{ qty($item->pendingQuantity()) }}" class="form-control form-control-sm">
                                </td>
                            @endcan
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><th colspan="7" class="text-end">Subtotal</th><td class="text-end">{{ money($purchase->subtotal) }}</td></tr>
                <tr><th colspan="7" class="text-end">Discount</th><td class="text-end">{{ money($purchase->discount_total) }}</td></tr>
                <tr><th colspan="7" class="text-end">Tax</th><td class="text-end">{{ money($purchase->tax_total) }}</td></tr>
                <tr><th colspan="7" class="text-end">Shipping</th><td class="text-end">{{ money($purchase->shipping_cost) }}</td></tr>
                <tr><th colspan="7" class="text-end">Grand total</th><td class="text-end">{{ money($purchase->grand_total) }}</td></tr>
                <tr><th colspan="7" class="text-end">Returned</th><td class="text-end">{{ money($purchase->returned_amount) }}</td></tr>
                <tr><th colspan="7" class="text-end">Paid</th><td class="text-end">{{ money($purchase->paid_amount) }}</td></tr>
                <tr><th colspan="7" class="text-end">Balance due</th><td class="text-end">{{ money($purchase->balanceDue()) }}</td></tr>
            </tfoot>
        </table>
    </div>

    @if ($purchase->status->canReceive())
        @can('receive', $purchase)
            <button type="submit" form="receive-form" class="btn btn-sm btn-success mb-4" data-confirm="Receive the entered quantities into stock?">Receive goods</button>
        @endcan
    @endif

    <div class="fw-grid gap-4">
        <div class="fw-span-lg-6">
            <h2 class="h6">Payments</h2>
            <table class="table table-sm">
                <thead><tr><th>No.</th><th>Date</th><th>Method</th><th>Direction</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse ($purchase->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_no }}</td>
                            <td>{{ $payment->paid_on->format('d-m-Y') }}</td>
                            <td>{{ $payment->method->label() }}</td>
                            <td>{{ $payment->direction === 'out' ? 'Paid' : 'Refund received' }}</td>
                            <td class="text-end">{{ money($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No payments.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @can('recordPayment', $purchase)
                <form method="POST" action="{{ route('purchases.payments.store', $purchase) }}" class="fw-grid gap-2">
                    @csrf
                    <div class="fw-span-md-3"><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required></div>
                    <div class="fw-span-md-3">
                        <select name="method" class="form-select form-select-sm" required>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fw-span-md-3"><input type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required></div>
                    <div class="fw-span-md-3"><input type="text" name="reference" class="form-control form-control-sm" placeholder="Reference"></div>
                    @can('refund', $purchase)
                        <div class="fw-span-12 form-check ms-2">
                            <input type="hidden" name="refund" value="0">
                            <input type="checkbox" name="refund" value="1" id="purchase_refund" class="form-check-input">
                            <label for="purchase_refund" class="form-check-label">This is a refund received from the supplier</label>
                        </div>
                    @endcan
                    <div class="fw-span-12"><button type="submit" class="btn btn-sm btn-primary">Record payment</button></div>
                </form>
            @endcan
        </div>

        <div class="fw-span-lg-6">
            <h2 class="h6">Returns</h2>
            <table class="table table-sm">
                <thead><tr><th>Reference</th><th>Date</th><th>Reason</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @forelse ($purchase->returns as $return)
                        <tr>
                            <td>{{ $return->reference_no }}</td>
                            <td>{{ $return->return_date->format('d-m-Y') }}</td>
                            <td>{{ $return->reason }}</td>
                            <td class="text-end">{{ money($return->grand_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No returns.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @can('return', $purchase)
                @if ($purchase->items->contains(fn ($item) => (float) $item->returnableQuantity() > 0))
                    <form method="POST" action="{{ route('purchases.returns.store', $purchase) }}">
                        @csrf
                        <table class="table table-sm">
                            <thead><tr><th>Product</th><th class="text-end">Returnable</th><th>Return qty</th></tr></thead>
                            <tbody>
                                @foreach ($purchase->items as $i => $item)
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td class="text-end">{{ qty($item->returnableQuantity()) }}</td>
                                        <td>
                                            <input type="hidden" name="items[{{ $i }}][purchase_item_id]" value="{{ $item->id }}">
                                            <input type="number" step="0.001" min="0" max="{{ $item->returnableQuantity() }}" name="items[{{ $i }}][quantity]" class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="fw-grid gap-2">
                            <div class="fw-span-md-4"><input type="date" name="return_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required></div>
                            <div class="fw-span-md-8"><input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" maxlength="255"></div>
                            @can('refund', $purchase)
                                <div class="fw-span-md-6"><input type="number" step="0.01" min="0" name="refund[amount]" class="form-control form-control-sm" placeholder="Refund received (optional)"></div>
                                <div class="fw-span-md-6">
                                    <select name="refund[method]" class="form-select form-select-sm">
                                        <option value="">Refund method</option>
                                        @foreach ($paymentMethods as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endcan
                            <div class="fw-span-12"><button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Record this return? Stock will be reduced.">Record return</button></div>
                        </div>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @if ($purchase->notes)
        <h2 class="h6 mt-3">Notes</h2>
        <p>{{ $purchase->notes }}</p>
    @endif
@endsection
