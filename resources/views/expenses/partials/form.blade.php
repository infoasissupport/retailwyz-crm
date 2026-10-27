{{-- Shared by expenses.create and expenses.edit. Expects $expense, $categories, $methods, $branches. --}}
<div class="row">
    @if ($branches->isNotEmpty())
        <div class="col-md-4"><x-form.select name="branch_id" label="Branch" :options="$branches" :value="$expense->branch_id ?? auth()->user()->branch_id" /></div>
    @endif
    <div class="col-md-4"><x-form.select name="expense_category_id" label="Category" :options="$categories" :value="$expense->expense_category_id" placeholder="Select category" required /></div>
    <div class="col-md-4"><x-form.input name="expense_date" label="Date" type="date" :value="$expense->expense_date?->toDateString()" required /></div>

    <div class="col-md-4"><x-form.input name="amount" label="Amount (incl. tax)" type="number" step="0.01" min="0.01" :value="$expense->amount" required /></div>
    <div class="col-md-4"><x-form.input name="tax_amount" label="Tax included" type="number" step="0.01" min="0" :value="$expense->tax_amount" /></div>
    <div class="col-md-4"><x-form.select name="payment_method" label="Payment method" :options="$methods" :value="$expense->payment_method" required /></div>

    <div class="col-md-6"><x-form.input name="paid_to" label="Paid to" :value="$expense->paid_to" maxlength="255" /></div>
    <div class="col-md-6">
        <x-form.input name="attachment" label="Receipt (PDF/JPG/PNG, max 5 MB)" type="file" accept=".pdf,.jpg,.jpeg,.png" />
        @if ($expense->attachment_path)
            <p class="small text-muted">A receipt is attached; uploading a new file replaces it.</p>
        @endif
    </div>

    <div class="col-12"><x-form.textarea name="description" label="Description" :value="$expense->description" /></div>
</div>
