{{-- Shared by suppliers.create and suppliers.edit. Expects $supplier. --}}
<div class="fw-grid">
    <div class="fw-span-md-3"><x-form.input name="code" label="Code" :value="$supplier->code" maxlength="30" help="Leave blank to generate." /></div>
    <div class="fw-span-md-5"><x-form.input name="name" label="Name" :value="$supplier->name" required maxlength="255" /></div>
    <div class="fw-span-md-4"><x-form.input name="contact_person" label="Contact person" :value="$supplier->contact_person" maxlength="255" /></div>

    <div class="fw-span-md-3"><x-form.input name="phone" label="Phone" :value="$supplier->phone" maxlength="30" /></div>
    <div class="fw-span-md-3"><x-form.input name="email" label="Email" type="email" :value="$supplier->email" maxlength="255" /></div>
    <div class="fw-span-md-3"><x-form.input name="tax_number" label="Tax number (GSTIN)" :value="$supplier->tax_number" maxlength="50" /></div>
    <div class="fw-span-md-3"><x-form.input name="opening_balance" label="Opening balance" type="number" step="0.01" :value="$supplier->opening_balance" /></div>

    <div class="fw-span-md-6"><x-form.textarea name="address" label="Address" :value="$supplier->address" /></div>
    <div class="fw-span-md-6"><x-form.textarea name="notes" label="Notes" :value="$supplier->notes" /></div>
    <div class="fw-span-12"><x-form.checkbox name="is_active" label="Active" :checked="$supplier->is_active" /></div>
</div>
