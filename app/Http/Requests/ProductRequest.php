<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /** Route middleware already checks products.create / products.update. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $variantIds = $product?->variants()->pluck('id')->all() ?? [];

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($product)],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'unit_id' => ['required', Rule::exists('units', 'id')],
            'tax_id' => ['nullable', Rule::exists('taxes', 'id')],
            'default_supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'cost_price' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999999'],
            'selling_price' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999999'],
            'min_stock' => ['nullable', 'decimal:0,3', 'min:0'],
            'track_stock' => ['boolean'],
            'has_variants' => ['boolean'],
            'status' => ['required', Rule::enum(ProductStatus::class)],

            // Free-form specifications as key/value rows.
            'specifications' => ['nullable', 'array', 'max:50'],
            'specifications.*.key' => ['required_with:specifications.*.value', 'nullable', 'string', 'max:100'],
            'specifications.*.value' => ['nullable', 'string', 'max:255'],

            'variants' => ['exclude_unless:has_variants,1', 'array', 'max:200'],
            'variants.*.id' => ['nullable', 'integer', Rule::in($variantIds)],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'distinct'],
            'variants.*.barcode' => ['nullable', 'string', 'max:64', 'distinct'],
            'variants.*.cost_price' => ['nullable', 'decimal:0,2', 'min:0'],
            'variants.*.selling_price' => ['nullable', 'decimal:0,2', 'min:0'],
            'variants.*.is_active' => ['boolean'],

            'opening_stock' => [$product ? 'prohibited' : 'nullable', 'decimal:0,3', 'min:0'],
            'opening_branch_id' => ['nullable', Rule::exists('branches', 'id')],
        ];
    }

    /**
     * Variant SKUs must be unique across the variants table, ignoring each row's own id.
     */
    public function after(): array
    {
        return [function ($validator) {
            foreach ($this->input('variants', []) as $i => $variant) {
                foreach (['sku', 'barcode'] as $field) {
                    if (empty($variant[$field])) {
                        continue;
                    }
                    $exists = \App\Models\ProductVariant::withTrashed()
                        ->where($field, $variant[$field])
                        ->when(! empty($variant['id']), fn ($q) => $q->whereKeyNot($variant['id']))
                        ->exists();
                    if ($exists) {
                        $validator->errors()->add("variants.{$i}.{$field}", "The variant {$field} has already been taken.");
                    }
                }
            }
        }];
    }

    /** @return array<string, mixed> */
    public function productData(): array
    {
        $data = $this->validated();

        $data['specifications'] = collect($data['specifications'] ?? [])
            ->filter(fn ($row) => filled($row['key'] ?? null))
            ->mapWithKeys(fn ($row) => [trim($row['key']) => $row['value'] ?? ''])
            ->all() ?: null;

        // Only all-branch users may choose where opening stock lands.
        if (! $this->user()->canAccessAllBranches()) {
            unset($data['opening_branch_id']);
        }

        return $data;
    }
}
