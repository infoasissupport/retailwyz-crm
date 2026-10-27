{{-- Standalone printable invoice. All figures are the stored server-side values. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $sale->invoice_no }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="bg-white">
    <main class="container py-4" style="max-width: 900px">
        <div class="d-flex justify-content-between mb-4">
            <div>
                <h1 class="h4 mb-1">{{ $settings->get('business_name') }}</h1>
                <div class="small">
                    {{ $sale->branch->name }}<br>
                    @if ($sale->branch->address){!! nl2br(e($sale->branch->address)) !!}<br>@elseif ($settings->get('business_address')){!! nl2br(e($settings->get('business_address'))) !!}<br>@endif
                    @if ($phone = $sale->branch->phone ?: $settings->get('business_phone'))Phone: {{ $phone }}<br>@endif
                    @if ($taxNo = $sale->branch->tax_number ?: $settings->get('business_tax_number'))Tax no.: {{ $taxNo }}@endif
                </div>
            </div>
            <div class="text-end">
                <h2 class="h5 mb-1">{{ $sale->status === \App\Enums\SaleStatus::Cancelled ? 'CANCELLED INVOICE' : 'TAX INVOICE' }}</h2>
                <div class="small">
                    No.: {{ $sale->invoice_no }}<br>
                    Date: {{ $sale->sale_date->format($settings->get('date_format', 'd-m-Y')) }}
                </div>
            </div>
        </div>

        <div class="mb-3 small">
            <strong>Bill to:</strong>
            @if ($sale->customer)
                {{ $sale->customer->name }}<br>
                @if ($sale->customer->address){!! nl2br(e($sale->customer->address)) !!}<br>@endif
                @if ($sale->customer->phone)Phone: {{ $sale->customer->phone }}<br>@endif
                @if ($sale->customer->tax_number)Tax no.: {{ $sale->customer->tax_number }}@endif
            @else
                Walk-in customer
            @endif
        </div>

        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>#</th><th>Item</th><th class="text-end">Qty</th><th class="text-end">Rate</th>
                    <th class="text-end">Discount</th><th class="text-end">Tax %</th><th class="text-end">Tax</th><th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="text-end">{{ qty($item->quantity) }}</td>
                        <td class="text-end">{{ money($item->unit_price, false) }}</td>
                        <td class="text-end">{{ money($item->discount, false) }}</td>
                        <td class="text-end">{{ qty($item->tax_rate) }}</td>
                        <td class="text-end">{{ money($item->tax_amount, false) }}</td>
                        <td class="text-end">{{ money($item->line_total, false) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row">
            <div class="col-md-6">
                <h3 class="h6">Tax summary</h3>
                <table class="table table-sm">
                    <thead><tr><th>Rate</th><th class="text-end">Taxable value</th><th class="text-end">Tax</th></tr></thead>
                    <tbody>
                        @foreach ($taxBreakdown as $row)
                            <tr>
                                <td>{{ qty($row['rate']) }}%</td>
                                <td class="text-end">{{ money($row['taxable']) }}</td>
                                <td class="text-end">{{ money($row['tax']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="small text-muted">Prices are {{ $sale->prices_include_tax ? 'inclusive' : 'exclusive' }} of tax.</p>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><th>Subtotal</th><td class="text-end">{{ money($sale->subtotal) }}</td></tr>
                    <tr><th>Discount</th><td class="text-end">{{ money($sale->discount_total) }}</td></tr>
                    <tr><th>Tax</th><td class="text-end">{{ money($sale->tax_total) }}</td></tr>
                    <tr><th>Grand total</th><td class="text-end fw-bold">{{ money($sale->grand_total) }}</td></tr>
                    @if ((float) $sale->returned_amount > 0)
                        <tr><th>Returned</th><td class="text-end">{{ money($sale->returned_amount) }}</td></tr>
                    @endif
                    <tr><th>Paid</th><td class="text-end">{{ money($sale->paid_amount) }}</td></tr>
                    <tr><th>Balance due</th><td class="text-end">{{ money($sale->balanceDue()) }}</td></tr>
                </table>
            </div>
        </div>

        @if ($footer = $settings->get('invoice_footer'))
            <p class="text-center small mt-4">{{ $footer }}</p>
        @endif

        <div class="no-print text-center mt-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
        </div>
    </main>
</body>
</html>
