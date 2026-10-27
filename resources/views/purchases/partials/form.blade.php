{{--
    Shared by purchases.create and purchases.edit. Expects $purchase (items loaded), $suppliers, $products, $branches, $statuses.
    Totals are calculated server-side by PurchaseService; nothing here is authoritative.
--}}
@php
    $rows = old('items', $purchase->items->map->only(['product_id', 'product_variant_id', 'quantity', 'unit_cost', 'discount'])->all());
    $rows = array_pad($rows, max(count($rows) + 3, 5), []);
@endphp

<div class="fw-grid">
    @if (! $purchase->exists && $branches->isNotEmpty())
        <div class="fw-span-md-3"><x-form.select name="branch_id" label="Branch" :options="$branches" :value="auth()->user()->branch_id" /></div>
    @endif
    <div class="fw-span-md-3"><x-form.select name="supplier_id" label="Supplier" :options="$suppliers" :value="$purchase->supplier_id" placeholder="Select supplier" required /></div>
    <div class="fw-span-md-2"><x-form.input name="purchase_date" label="Purchase date" type="date" :value="$purchase->purchase_date?->toDateString()" required /></div>
    <div class="fw-span-md-2"><x-form.input name="expected_date" label="Expected date" type="date" :value="$purchase->expected_date?->toDateString()" /></div>
    <div class="fw-span-md-2"><x-form.input name="supplier_invoice_no" label="Supplier invoice no." :value="$purchase->supplier_invoice_no" maxlength="64" /></div>
    <div class="fw-span-md-2"><x-form.select name="status" label="Status" :options="$statuses" :value="$purchase->status ?? 'ordered'" required /></div>
    <div class="fw-span-md-2"><x-form.input name="shipping_cost" label="Shipping cost" type="number" step="0.01" min="0" :value="$purchase->shipping_cost" /></div>
</div>

<table class="table table-sm">
    <thead>
        <tr><th>Product</th><th>Variant ID</th><th>Quantity</th><th>Unit cost</th><th>Discount</th></tr>
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
                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_cost]" value="{{ $row['unit_cost'] ?? '' }}" class="form-control form-control-sm"></td>
                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][discount]" value="{{ $row['discount'] ?? '' }}" class="form-control form-control-sm"></td>
            </tr>
        @endforeach
    </tbody>
</table>

<x-form.textarea name="notes" label="Notes" :value="$purchase->notes" />

@if (! $purchase->exists)
    @can('purchases.receive')
        <x-form.checkbox name="receive_now" label="Goods received now (add to stock immediately)" />
    @endcan
@endif
