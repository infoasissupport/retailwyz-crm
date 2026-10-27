<?php

namespace App\Http\Requests;

use App\Models\StockAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    /** Route middleware already checks inventory.adjust. */
    public function authorize(): bool
    {
        return true;
    }

    /** Drop blank item rows rendered by the form. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => collect($this->input('items', []))
                ->filter(fn ($row) => filled($row['product_id'] ?? null) || filled($row['quantity'] ?? null))
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        $quantityRule = $this->input('type') === StockAdjustment::TYPE_CORRECTION
            ? ['required', 'decimal:0,3', 'not_in:0']
            : ['required', 'decimal:0,3', 'gt:0'];

        return [
            'branch_id' => ['nullable', 'integer'],
            'type' => ['required', Rule::in([StockAdjustment::TYPE_IN, StockAdjustment::TYPE_OUT, StockAdjustment::TYPE_CORRECTION])],
            'adjustment_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'approve_now' => ['boolean'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => $quantityRule,
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
