<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    /** ExpensePolicy is checked in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', Rule::exists('expense_categories', 'id')->where('is_active', true)],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999999'],
            'tax_amount' => ['nullable', 'decimal:0,2', 'min:0', 'lte:amount'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }
}
