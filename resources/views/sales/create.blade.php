@extends('layouts.app', ['breadcrumbs' => ['Sales' => route('sales.index'), 'New sale' => null]])

@section('title', 'New sale')

{{--
    Prices, tax and totals are decided by SaleService; anything the browser shows is a preview only.
    A submitted unit price is honoured only for users with sales.override-price.
    TODO (page design): replace the product <select> with a search wired to route('products.lookup').
--}}
@php
    $rows = old('items', []);
    $rows = array_pad($rows, max(count($rows), 5), []);
    $paymentRows = array_pad(old('payments', []), 2, []);
@endphp

@section('content')
    <form method="POST" action="{{ route('sales.store') }}">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

        <div class="fw-grid">
            @if ($branches->isNotEmpty())
                <div class="fw-span-md-3"><x-form.select name="branch_id" label="Branch" :options="$branches" :value="auth()->user()->branch_id" /></div>
            @endif
            <div class="fw-span-md-4"><x-form.select name="customer_id" label="Customer" :options="$customers" placeholder="Walk-in customer" /></div>
            <div class="fw-span-md-3"><x-form.input name="sale_date" label="Date" type="date" :value="now()->toDateString()" required /></div>
        </div>

        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Product</th><th>Variant ID</th><th>Quantity</th>
                    @can('sales.override-price')<th>Unit price</th>@endcan
                    <th>Discount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        <td>
                            <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm">
                                <option value="">—</option>
                                @foreach ($products as $id => $label)
                                    <option value="{{ $id }}" @selected(($row['product_id'] ?? null) == $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[{{ $i }}][product_variant_id]" value="{{ $row['product_variant_id'] ?? '' }}" class="form-control form-control-sm"></td>
                        <td><input type="number" step="0.001" min="0" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}" class="form-control form-control-sm"></td>
                        @can('sales.override-price')
                            <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" value="{{ $row['unit_price'] ?? '' }}" class="form-control form-control-sm" placeholder="List price"></td>
                        @endcan
                        <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][discount]" value="{{ $row['discount'] ?? '' }}" class="form-control form-control-sm"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <fieldset>
            <legend class="h6">Payment received</legend>
            @foreach ($paymentRows as $i => $payment)
                <div class="fw-grid gap-2 mb-2">
                    <div class="fw-span-md-3"><input type="number" step="0.01" min="0" name="payments[{{ $i }}][amount]" value="{{ $payment['amount'] ?? '' }}" class="form-control form-control-sm" placeholder="Amount"></div>
                    <div class="fw-span-md-3">
                        <select name="payments[{{ $i }}][method]" class="form-select form-select-sm">
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected(($payment['method'] ?? 'cash') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fw-span-md-4"><input type="text" name="payments[{{ $i }}][reference]" value="{{ $payment['reference'] ?? '' }}" class="form-control form-control-sm" placeholder="Reference"></div>
                </div>
            @endforeach
        </fieldset>

        <x-form.textarea name="notes" label="Notes" />

        <button type="submit" class="btn btn-primary">Save sale</button>
        <a href="{{ route('sales.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
