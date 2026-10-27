<?php

namespace App\Http\Requests;

use App\Enums\PurchaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    /** Route middleware checks purchases.create; updates are authorized by PurchasePolicy in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    /** Drop blank item rows rendered by the form. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => collect($this->input('items', []))
                ->filter(fn ($row) => filled($row['product_id'] ?? null))
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer'],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'purchase_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:64'],
            'shipping_cost' => ['nullable', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in([PurchaseStatus::Draft->value, PurchaseStatus::Ordered->value])],
            'receive_now' => ['boolean'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items.*.unit_cost' => ['required', 'decimal:0,2', 'min:0'],
            'items.*.discount' => ['nullable', 'decimal:0,2', 'min:0'],
        ];
    }

    /** @return array<string, mixed> */
    public function purchaseData(): array
    {
        $data = $this->validated();

        // Receiving moves stock, so it needs its own permission.
        $data['receive_now'] = ! $this->route('purchase')
            && ($data['receive_now'] ?? false)
            && $this->user()->can('purchases.receive');

        unset($data['branch_id']);

        return $data;
    }
}
