<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    /** Authorized by SalePolicy::create in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    /** Drop blank item and payment rows rendered by the form. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => collect($this->input('items', []))->filter(fn ($row) => filled($row['product_id'] ?? null))->values()->all(),
            'payments' => collect($this->input('payments', []))->filter(fn ($row) => filled($row['amount'] ?? null))->values()->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'sale_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'decimal:0,2', 'min:0'],
            'items.*.discount' => ['nullable', 'decimal:0,2', 'min:0'],
            'payments' => ['nullable', 'array', 'max:10'],
            'payments.*.amount' => ['required', 'decimal:0,2', 'gt:0'],
            'payments.*.method' => ['required', Rule::enum(PaymentMethod::class)],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, mixed> */
    public function saleData(): array
    {
        $data = $this->validated();
        unset($data['branch_id']);

        return $data;
    }
}
