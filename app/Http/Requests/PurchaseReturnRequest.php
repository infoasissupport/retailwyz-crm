<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseReturnRequest extends FormRequest
{
    /** Authorized by PurchasePolicy::return in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'decimal:0,3', 'min:0'],
            'refund' => ['nullable', 'array'],
            'refund.amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'refund.method' => ['required_with:refund.amount', 'nullable', Rule::enum(PaymentMethod::class)],
            'refund.reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Refund details are kept only when the user may record refunds. */
    public function returnData(): array
    {
        $data = $this->validated();

        if (empty($data['refund']['amount']) || ! $this->user()->can('refund', $this->route('purchase'))) {
            unset($data['refund']);
        }

        return $data;
    }
}
