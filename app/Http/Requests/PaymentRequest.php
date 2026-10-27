<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    /** Authorized by the payable's policy (recordPayment / refund) in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999999'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            // Optional: PaymentService defaults to today.
            'paid_on' => ['nullable', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'refund' => ['boolean'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }
}
