{{-- Shared by deliveries.create and deliveries.edit. Expects $delivery, $sale (nullable), $drivers, $customers. --}}
@if ($sale)
    @unless ($delivery->exists)
        <input type="hidden" name="sale_id" value="{{ $sale->id }}">
    @endunless
    <div class="alert alert-info py-2 small">
        Delivery for invoice <strong>{{ $sale->invoice_no }}</strong>. The order value follows the invoice.
    </div>
@endif

<div class="fw-card p-3 p-md-4">
    <h2 class="fw-card-title">Deliver to</h2>
    <div class="fw-grid">
        @if (! $delivery->exists && ! $sale)
            <div class="fw-span-12">
                <label for="customer_id" class="form-label">Existing customer <span class="text-secondary small">(optional, fills the details below)</span></label>
                <select name="customer_id" id="customer_id" class="form-select" data-fw-customer-fill>
                    <option value="">New / walk-in customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" data-name="{{ $customer->name }}" data-phone="{{ $customer->phone }}" data-address="{{ $customer->address }}"
                                @selected(old('customer_id') == $customer->id)>{{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="fw-span-md-6"><x-form.input name="customer_name" label="Customer name" :value="$delivery->customer_name" required maxlength="255" /></div>
        <div class="fw-span-md-6"><x-form.input name="phone" label="Phone" type="tel" :value="$delivery->phone" required maxlength="30" /></div>
        <div class="fw-span-12"><x-form.textarea name="address" label="Delivery address" :value="$delivery->address" rows="2" required /></div>
        <div class="fw-span-md-4"><x-form.input name="scheduled_date" label="Delivery date" type="date" :value="$delivery->scheduled_date?->toDateString()" :min="today()->toDateString()" required /></div>
        <div class="fw-span-md-4"><x-form.input name="time_slot" label="Time slot" :value="$delivery->time_slot" maxlength="50" placeholder="e.g. 10 AM – 1 PM" /></div>
        @unless ($sale)
            <div class="fw-span-md-4"><x-form.input name="order_value" label="Order value" type="number" step="0.01" min="0" :value="$delivery->order_value" /></div>
        @endunless
        <div class="fw-span-md-4"><x-form.input name="delivery_charge" label="Delivery charge" type="number" step="0.01" min="0" :value="$delivery->delivery_charge" /></div>
    </div>
</div>

<div class="fw-card p-3 p-md-4">
    <h2 class="fw-card-title">Driver &amp; vehicle</h2>
    <div class="fw-grid">
        <div class="fw-span-md-6"><x-form.select name="employee_id" label="Driver from staff" :options="$drivers" :value="$delivery->employee_id" placeholder="Not from staff / enter below" /></div>
        <div class="fw-span-md-6"><x-form.input name="driver_name" label="Driver name" :value="$delivery->driver_name" maxlength="255" help="Filled automatically when a staff driver is chosen." /></div>
        <div class="fw-span-md-6"><x-form.input name="driver_phone" label="Driver phone" type="tel" :value="$delivery->driver_phone" maxlength="30" /></div>
        <div class="fw-span-md-6"><x-form.input name="vehicle_no" label="Vehicle number" :value="$delivery->vehicle_no" maxlength="30" placeholder="e.g. MH-04-AB-1234" /></div>
        <div class="fw-span-12"><x-form.textarea name="notes" label="Instructions" :value="$delivery->notes" rows="2" placeholder="e.g. Call before arriving, 3rd floor, no lift" /></div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelector('[data-fw-customer-fill]')?.addEventListener('change', (event) => {
        const option = event.target.selectedOptions[0];
        if (!option?.value) return;
        for (const field of ['name', 'phone', 'address']) {
            const input = document.querySelector(field === 'name' ? '[name="customer_name"]' : `[name="${field}"]`);
            if (input && option.dataset[field]) input.value = option.dataset[field];
        }
    });
</script>
@endpush
