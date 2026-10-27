{{-- Shared by customers.create and customers.edit. Expects $customer. --}}
<div class="row">
    <div class="col-md-3"><x-form.input name="code" label="Code" :value="$customer->code" maxlength="30" help="Leave blank to generate." /></div>
    <div class="col-md-5"><x-form.input name="name" label="Name" :value="$customer->name" required maxlength="255" /></div>
    <div class="col-md-4"><x-form.input name="phone" label="Phone" :value="$customer->phone" maxlength="30" /></div>

    <div class="col-md-4"><x-form.input name="email" label="Email" type="email" :value="$customer->email" maxlength="255" /></div>
    <div class="col-md-4"><x-form.input name="tax_number" label="Tax number (GSTIN)" :value="$customer->tax_number" maxlength="50" /></div>
    <div class="col-md-2"><x-form.input name="opening_balance" label="Opening balance" type="number" step="0.01" :value="$customer->opening_balance" /></div>
    <div class="col-md-2"><x-form.input name="credit_limit" label="Credit limit" type="number" step="0.01" min="0" :value="$customer->credit_limit" /></div>

    <div class="col-md-6"><x-form.textarea name="address" label="Address" :value="$customer->address" /></div>
    <div class="col-md-6"><x-form.textarea name="notes" label="Notes" :value="$customer->notes" /></div>
    <div class="col-12"><x-form.checkbox name="is_active" label="Active" :checked="$customer->is_active" /></div>
</div>
