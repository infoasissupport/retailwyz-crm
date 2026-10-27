{{--
    Shared stock-in / stock-out form. Expects $type ('in'|'out'), $products, $branches.
    TODO (page design): replace the product <select> with a search wired to route('products.lookup').
--}}
@php
    $rows = old('items', []);
    $rows = array_pad($rows, max(count($rows), 5), []);
@endphp

<form method="POST" action="{{ route('inventory.adjustments.store') }}">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">

    <div class="row">
        @if ($branches->isNotEmpty())
            <div class="col-md-3"><x-form.select name="branch_id" label="Branch" :options="$branches" :value="auth()->user()->branch_id" /></div>
        @endif
        <div class="col-md-3"><x-form.input name="adjustment_date" label="Date" type="date" :value="now()->toDateString()" required /></div>
        <div class="col-md-6"><x-form.input name="reason" label="Reason" required maxlength="255" /></div>
    </div>

    <table class="table table-sm">
        <thead>
            <tr><th>Product</th><th>Variant ID</th><th>Quantity</th><th>Notes</th></tr>
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
                    <td><input type="text" name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}" class="form-control form-control-sm" maxlength="255"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-form.textarea name="notes" label="Notes" />

    @can('inventory.approve')
        <x-form.checkbox name="approve_now" label="Apply immediately (approve now)" />
    @endcan

    <button type="submit" class="btn btn-primary">{{ $type === 'in' ? 'Record stock in' : 'Record stock out' }}</button>
</form>
