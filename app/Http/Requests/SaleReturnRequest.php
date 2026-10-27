<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleReturnRequest extends FormRequest
{
    /** Authorized by SalePolicy::return in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'restock' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer'],
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
        $data['restock'] = $this->boolean('restock', true);

        if (empty($data['refund']['amount']) || ! $this->user()->can('refund', $this->route('sale'))) {
            unset($data['refund']);
        }

        return $data;
    }
}
