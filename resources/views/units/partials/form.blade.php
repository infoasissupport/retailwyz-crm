{{-- Shared by units.create and units.edit. Expects $unit. --}}
<div class="fw-grid">
    <div class="fw-span-md-6"><x-form.input name="name" label="Name" :value="$unit->name" required maxlength="50" /></div>
    <div class="fw-span-md-6"><x-form.input name="short_name" label="Short name" :value="$unit->short_name" required maxlength="20" /></div>
    <div class="fw-span-12"><x-form.checkbox name="allow_decimal" label="Allow decimal quantities (metres, kg...)" :checked="$unit->allow_decimal" /></div>
    <div class="fw-span-12"><x-form.checkbox name="is_active" label="Active" :checked="$unit->is_active" /></div>
</div>
