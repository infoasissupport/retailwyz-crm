{{-- Shared by brands.create and brands.edit. Expects $brand. --}}
<div class="row">
    <div class="col-md-6"><x-form.input name="name" label="Name" :value="$brand->name" required maxlength="255" /></div>
    <div class="col-12"><x-form.textarea name="description" label="Description" :value="$brand->description" /></div>
    <div class="col-12"><x-form.checkbox name="is_active" label="Active" :checked="$brand->is_active" /></div>
</div>
