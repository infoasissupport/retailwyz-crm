{{-- Shared by categories.create and categories.edit. Expects $category and $parents. --}}
<div class="row">
    <div class="col-md-6"><x-form.input name="name" label="Name" :value="$category->name" required maxlength="255" /></div>
    <div class="col-md-6"><x-form.select name="parent_id" label="Parent category" :options="$parents" :value="$category->parent_id" placeholder="None (top level)" /></div>
    <div class="col-12"><x-form.textarea name="description" label="Description" :value="$category->description" /></div>
    <div class="col-12"><x-form.checkbox name="is_active" label="Active" :checked="$category->is_active" /></div>
</div>
