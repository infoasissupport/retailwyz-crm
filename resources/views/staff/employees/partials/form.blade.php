{{-- Shared by staff.employees.create and staff.employees.edit. --}}
<div class="fw-card p-3 p-md-4 mb-3">
    <h2 class="fw-card-title mb-3">Personal details</h2>
    <div class="row">
        <div class="col-md-6"><x-form.input name="name" label="Full name" :value="$employee->name" required maxlength="255" /></div>
        <div class="col-md-3"><x-form.input name="employee_code" label="Employee code" :value="$employee->employee_code" maxlength="30" help="Leave blank to generate (EMP-0001)." /></div>
        <div class="col-md-3"><x-form.select name="status" label="Status" :options="$statuses" :value="$employee->status" required /></div>
        <div class="col-md-4"><x-form.input name="phone" label="Phone" type="tel" :value="$employee->phone" maxlength="30" /></div>
        <div class="col-md-4"><x-form.input name="email" label="Email" type="email" :value="$employee->email" maxlength="255" /></div>
        <div class="col-md-4"><x-form.input name="emergency_contact" label="Emergency contact" :value="$employee->emergency_contact" maxlength="255" /></div>
        <div class="col-12"><x-form.textarea name="address" label="Address" :value="$employee->address" rows="2" /></div>
    </div>
</div>

<div class="fw-card p-3 p-md-4 mb-3">
    <h2 class="fw-card-title mb-3">Employment</h2>
    <div class="row">
        <div class="col-md-4"><x-form.input name="designation" label="Designation" :value="$employee->designation" maxlength="100" placeholder="e.g. Sales executive" /></div>
        <div class="col-md-4"><x-form.input name="joining_date" label="Joining date" type="date" :value="$employee->joining_date?->toDateString()" /></div>
        <div class="col-md-4"><x-form.input name="monthly_salary" label="Monthly salary" type="number" step="0.01" min="0" :value="$employee->monthly_salary" /></div>
        @if (! $employee->exists && $branches->isNotEmpty())
            <div class="col-md-6"><x-form.select name="branch_id" label="Branch" :options="$branches" :value="auth()->user()->branch_id" required /></div>
        @endif
        <div class="col-md-6">
            <x-form.select name="user_id" label="Login account" :options="$users" :value="$employee->user_id" placeholder="No login" />
            <p class="form-text mt-n2">Link the account this person uses to bill sales, so their sales count towards targets.</p>
        </div>
        <div class="col-12"><x-form.textarea name="notes" label="Notes" :value="$employee->notes" rows="2" /></div>
    </div>
</div>
