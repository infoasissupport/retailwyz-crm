{{-- Shared by products.create and products.edit. Expects $product and the option lists from ProductController::formOptions(). --}}
@php
    $specRows = old('specifications', collect($product->specifications ?? [])->map(fn ($v, $k) => ['key' => $k, 'value' => $v])->values()->all());
    $specRows = $specRows ?: [['key' => '', 'value' => '']];
    $variantRows = old('variants', $product->variants?->map->only(['id', 'name', 'sku', 'barcode', 'cost_price', 'selling_price', 'is_active'])->all() ?? []);
@endphp

<div class="fw-grid">
    <div class="fw-span-md-6"><x-form.input name="name" label="Name" :value="$product->name" required maxlength="255" /></div>
    <div class="fw-span-md-3"><x-form.input name="sku" label="SKU" :value="$product->sku" required maxlength="64" /></div>
    <div class="fw-span-md-3"><x-form.input name="barcode" label="Barcode" :value="$product->barcode" maxlength="64" /></div>

    <div class="fw-span-md-4"><x-form.select name="category_id" label="Category" :options="$categories" :value="$product->category_id" placeholder="Select category" required /></div>
    <div class="fw-span-md-4"><x-form.select name="brand_id" label="Brand" :options="$brands" :value="$product->brand_id" placeholder="No brand" /></div>
    <div class="fw-span-md-4"><x-form.select name="unit_id" label="Unit" :options="$units" :value="$product->unit_id" placeholder="Select unit" required /></div>

    <div class="fw-span-md-3"><x-form.input name="cost_price" label="Cost price" type="number" step="0.01" min="0" :value="$product->cost_price" required /></div>
    <div class="fw-span-md-3"><x-form.input name="selling_price" label="Selling price" type="number" step="0.01" min="0" :value="$product->selling_price" required /></div>
    <div class="fw-span-md-3"><x-form.select name="tax_id" label="Tax" :options="$taxes" :value="$product->tax_id" placeholder="No tax" /></div>
    <div class="fw-span-md-3"><x-form.input name="min_stock" label="Minimum stock" type="number" step="0.001" min="0" :value="$product->min_stock" /></div>

    <div class="fw-span-md-4"><x-form.select name="default_supplier_id" label="Default supplier" :options="$suppliers" :value="$product->default_supplier_id" placeholder="None" /></div>
    <div class="fw-span-md-4"><x-form.select name="status" label="Status" :options="$statuses" :value="$product->status" required /></div>
    <div class="fw-span-md-4 pt-md-4">
        <x-form.checkbox name="track_stock" label="Track stock" :checked="$product->track_stock" />
    </div>

    @unless ($product->exists)
        <div class="fw-span-md-4"><x-form.input name="opening_stock" label="Opening stock" type="number" step="0.001" min="0" help="Products without variants only." /></div>
    @endunless

    <div class="fw-span-12"><x-form.textarea name="description" label="Description" :value="$product->description" /></div>
</div>

<fieldset>
    <legend class="h6">Specifications</legend>
    <p class="small text-muted">Any attribute: dimensions, material, wattage, voltage, model, colour...</p>
    <div data-fw-repeater="specifications">
        @foreach ($specRows as $i => $row)
            <div class="fw-grid gap-2 mb-2" data-fw-row>
                <div class="fw-span-5"><input type="text" name="specifications[{{ $i }}][key]" value="{{ $row['key'] }}" class="form-control form-control-sm" placeholder="Attribute"></div>
                <div class="fw-span-6"><input type="text" name="specifications[{{ $i }}][value]" value="{{ $row['value'] }}" class="form-control form-control-sm" placeholder="Value"></div>
            </div>
        @endforeach
    </div>
</fieldset>

<fieldset>
    <legend class="h6">Variants</legend>
    <x-form.checkbox name="has_variants" label="This product has variants (stock is tracked per variant)" :checked="$product->has_variants" />
    <div data-fw-repeater="variants">
        @foreach ($variantRows as $i => $variant)
            <div class="fw-grid gap-2 mb-2" data-fw-row>
                <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $variant['id'] ?? '' }}">
                <div class="fw-span-md-3"><input type="text" name="variants[{{ $i }}][name]" value="{{ $variant['name'] ?? '' }}" class="form-control form-control-sm" placeholder="Name e.g. Red / 9W"></div>
                <div class="fw-span-md-2"><input type="text" name="variants[{{ $i }}][sku]" value="{{ $variant['sku'] ?? '' }}" class="form-control form-control-sm" placeholder="SKU"></div>
                <div class="fw-span-md-2"><input type="text" name="variants[{{ $i }}][barcode]" value="{{ $variant['barcode'] ?? '' }}" class="form-control form-control-sm" placeholder="Barcode"></div>
                <div class="fw-span-md-2"><input type="number" step="0.01" min="0" name="variants[{{ $i }}][cost_price]" value="{{ $variant['cost_price'] ?? '' }}" class="form-control form-control-sm" placeholder="Cost"></div>
                <div class="fw-span-md-2"><input type="number" step="0.01" min="0" name="variants[{{ $i }}][selling_price]" value="{{ $variant['selling_price'] ?? '' }}" class="form-control form-control-sm" placeholder="Price"></div>
                <div class="fw-span-md-1">
                    <input type="hidden" name="variants[{{ $i }}][is_active]" value="0">
                    <input type="checkbox" name="variants[{{ $i }}][is_active]" value="1" class="form-check-input" @checked($variant['is_active'] ?? true) title="Active">
                </div>
            </div>
        @endforeach
    </div>
    {{-- Add-row behaviour for repeaters is deferred to the page design phase. --}}
</fieldset>
