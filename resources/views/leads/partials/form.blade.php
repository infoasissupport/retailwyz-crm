{{-- Shared by leads.create and leads.edit. --}}
<div class="fw-card p-3 p-md-4">
    <h2 class="fw-card-title">Contact</h2>
    <div class="fw-grid">
        <div class="fw-span-md-6"><x-form.input name="name" label="Name" :value="$lead->name" required maxlength="255" /></div>
        <div class="fw-span-md-6"><x-form.input name="company" label="Company" :value="$lead->company" maxlength="255" /></div>
        <div class="fw-span-md-6"><x-form.input name="phone" label="Phone" type="tel" :value="$lead->phone" maxlength="30" /></div>
        <div class="fw-span-md-6"><x-form.input name="email" label="Email" type="email" :value="$lead->email" maxlength="255" /></div>
        <div class="fw-span-12"><x-form.textarea name="address" label="Address" :value="$lead->address" rows="2" /></div>
    </div>
</div>

<div class="fw-card p-3 p-md-4">
    <h2 class="fw-card-title">Enquiry</h2>
    <div class="fw-grid">
        <div class="fw-span-md-4"><x-form.select name="source" label="Source" :options="$sources" :value="$lead->source" required /></div>
        <div class="fw-span-md-4"><x-form.select name="category_id" label="Interested category" :options="$categories" :value="$lead->category_id" placeholder="Any" /></div>
        <div class="fw-span-md-4"><x-form.input name="expected_value" label="Expected value" type="number" step="0.01" min="0" :value="$lead->expected_value" /></div>
        <div class="fw-span-12"><x-form.textarea name="requirement" label="Requirement" :value="$lead->requirement" rows="3" placeholder="e.g. 3-seater sofa in grey, modular switches for a 3BHK" /></div>
        <div class="fw-span-md-4"><x-form.select name="assigned_to" label="Assigned to" :options="$staff" :value="$lead->assigned_to" placeholder="Unassigned" /></div>
        @if ($lead->exists)
            <div class="fw-span-md-4"><x-form.select name="status" label="Status" :options="$statuses" :value="$lead->status" required /></div>
            <div class="fw-span-md-4"><x-form.input name="lost_reason" label="Lost reason" :value="$lead->lost_reason" maxlength="255" help="Required when status is Lost." /></div>
        @endif
        <div class="fw-span-12"><x-form.textarea name="notes" label="Notes" :value="$lead->notes" rows="2" /></div>
    </div>
</div>

@unless ($lead->exists)
    <div class="fw-card p-3 p-md-4">
        <h2 class="fw-card-title">First follow-up <span class="text-secondary fw-normal small">(optional)</span></h2>
        <div class="fw-grid">
            <div class="fw-span-md-4"><x-form.input name="follow_up_at" label="When" type="datetime-local" /></div>
            <div class="fw-span-md-4"><x-form.select name="follow_up_type" label="Type" :options="$followUpTypes" value="call" /></div>
        </div>
    </div>
@endunless
