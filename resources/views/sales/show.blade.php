@extends('layouts.app', ['breadcrumbs' => ['Sales' => route('sales.index'), $sale->invoice_no => null]])

@section('title', 'Sale '.$sale->invoice_no)

@section('page-actions')
    <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Invoice</a>
    @if ($sale->status === \App\Enums\SaleStatus::Completed)
        @can('create', App\Models\Delivery::class)
            <a href="{{ route('deliveries.create', ['sale_id' => $sale->id]) }}" class="btn btn-sm btn-outline-secondary">Schedule delivery</a>
        @endcan
    @endif
    @if ($sale->status === \App\Enums\SaleStatus::Completed)
        @can('cancel', $sale)
            <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Cancel this sale and restore stock?">Cancel sale</button>
            </form>
        @endcan
    @endif
@endsection

@section('content')
    <dl class="row">
        <dt class="col-sm-2">Customer</dt><dd class="col-sm-4">{{ $sale->customer?->name ?? 'Walk-in' }}</dd>
        <dt class="col-sm-2">Branch</dt><dd class="col-sm-4">{{ $sale->branch->name }}</dd>
        <dt class="col-sm-2">Date</dt><dd class="col-sm-4">{{ $sale->sale_date->format('d-m-Y') }}</dd>
        <dt class="col-sm-2">Status</dt><dd class="col-sm-4">{{ $sale->status->label() }}</dd>
        <dt class="col-sm-2">Payment</dt><dd class="col-sm-4">{{ $sale->payment_status->label() }}</dd>
        <dt class="col-sm-2">Prices</dt><dd class="col-sm-4">{{ $sale->prices_include_tax ? 'Tax inclusive' : 'Tax exclusive' }}</dd>
        <dt class="col-sm-2">Created by</dt><dd class="col-sm-4">{{ $sale->creator?->name }}</dd>
    </dl>

    <h2 class="h6">Items</h2>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Item</th><th class="text-end">Qty</th><th class="text-end">Returned</th><th class="text-end">Unit price</th>
                    <th class="text-end">Discount</th><th class="text-end">Tax</th><th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->description }} <span class="small text-muted">{{ $item->product->sku }}</span></td>
                        <td class="text-end">{{ qty($item->quantity) }}</td>
                        <td class="text-end">{{ qty($item->returned_quantity) }}</td>
                        <td class="text-end">{{ money($item->unit_price) }}</td>
                        <td class="text-end">{{ money($item->discount) }}</td>
                        <td class="text-end">{{ money($item->tax_amount) }} <span class="small text-muted">({{ qty($item->tax_rate) }}%)</span></td>
                        <td class="text-end">{{ money($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><th colspan="6" class="text-end">Subtotal</th><td class="text-end">{{ money($sale->subtotal) }}</td></tr>
                <tr><th colspan="6" class="text-end">Discount</th><td class="text-end">{{ money($sale->discount_total) }}</td></tr>
                <tr><th colspan="6" class="text-end">Tax</th><td class="text-end">{{ money($sale->tax_total) }}</td></tr>
                <tr><th colspan="6" class="text-end">Grand total</th><td class="text-end">{{ money($sale->grand_total) }}</td></tr>
                <tr><th colspan="6" class="text-end">Returned</th><td class="text-end">{{ money($sale->returned_amount) }}</td></tr>
                <tr><th colspan="6" class="text-end">Paid</th><td class="text-end">{{ money($sale->paid_amount) }}</td></tr>
                <tr><th colspan="6" class="text-end">Balance due</th><td class="text-end">{{ money($sale->balanceDue()) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <h2 class="h6">Payments</h2>
            <table class="table table-sm">
                <thead><tr><th>No.</th><th>Date</th><th>Method</th><th>Type</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse ($sale->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_no }}</td>
                            <td>{{ $payment->paid_on->format('d-m-Y') }}</td>
                            <td>{{ $payment->method->label() }}</td>
                            <td>{{ $payment->direction === 'in' ? 'Received' : 'Refund' }}</td>
                            <td class="text-end">{{ money($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No payments.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if ($sale->status === \App\Enums\SaleStatus::Completed)
                @can('recordPayment', $sale)
                    <form method="POST" action="{{ route('sales.payments.store', $sale) }}" class="row g-2">
                        @csrf
                        <div class="col-md-3"><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required></div>
                        <div class="col-md-3">
                            <select name="method" class="form-select form-select-sm" required>
                                @foreach ($paymentMethods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><input type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required></div>
                        <div class="col-md-3"><input type="text" name="reference" class="form-control form-control-sm" placeholder="Reference"></div>
                        @can('refund', $sale)
                            <div class="col-12 form-check ms-2">
                                <input type="hidden" name="refund" value="0">
                                <input type="checkbox" name="refund" value="1" id="sale_refund" class="form-check-input">
                                <label for="sale_refund" class="form-check-label">This is a refund paid to the customer</label>
                            </div>
                        @endcan
                        <div class="col-12"><button type="submit" class="btn btn-sm btn-primary">Record payment</button></div>
                    </form>
                @endcan
            @endif
        </div>

        <div class="col-lg-6">
            <h2 class="h6">Returns</h2>
            <table class="table table-sm">
                <thead><tr><th>Reference</th><th>Date</th><th>Reason</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @forelse ($sale->returns as $return)
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

            @if ($sale->status === \App\Enums\SaleStatus::Completed && $sale->items->contains(fn ($item) => (float) $item->returnableQuantity() > 0))
                @can('return', $sale)
                    <form method="POST" action="{{ route('sales.returns.store', $sale) }}">
                        @csrf
                        <table class="table table-sm">
                            <thead><tr><th>Item</th><th class="text-end">Returnable</th><th>Return qty</th></tr></thead>
                            <tbody>
                                @foreach ($sale->items as $i => $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ qty($item->returnableQuantity()) }}</td>
                                        <td>
                                            <input type="hidden" name="items[{{ $i }}][sale_item_id]" value="{{ $item->id }}">
                                            <input type="number" step="0.001" min="0" max="{{ $item->returnableQuantity() }}" name="items[{{ $i }}][quantity]" class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="row g-2">
                            <div class="col-md-4"><input type="date" name="return_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required></div>
                            <div class="col-md-8"><input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" maxlength="255"></div>
                            <div class="col-12 form-check ms-2">
                                <input type="hidden" name="restock" value="0">
                                <input type="checkbox" name="restock" value="1" id="restock" class="form-check-input" checked>
                                <label for="restock" class="form-check-label">Return items to stock</label>
                            </div>
                            @can('refund', $sale)
                                <div class="col-md-6"><input type="number" step="0.01" min="0" name="refund[amount]" class="form-control form-control-sm" placeholder="Refund now (optional)"></div>
                                <div class="col-md-6">
                                    <select name="refund[method]" class="form-select form-select-sm">
                                        <option value="">Refund method</option>
                                        @foreach ($paymentMethods as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endcan
                            <div class="col-12"><button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Record this return?">Record return</button></div>
                        </div>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    @if ($sale->notes)
        <h2 class="h6 mt-3">Notes</h2>
        <p>{{ $sale->notes }}</p>
    @endif
@endsection
