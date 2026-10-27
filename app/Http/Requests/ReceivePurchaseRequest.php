<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseRequest extends FormRequest
{
    /** Authorized by PurchasePolicy::receive in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'decimal:0,3', 'min:0'],
        ];
    }

    /** @return array<int, string> purchase_item_id => quantity */
    public function quantities(): array
    {
        return collect($this->validated('quantities'))
            ->filter(fn ($qty) => filled($qty))
            ->mapWithKeys(fn ($qty, $itemId) => [(int) $itemId => (string) $qty])
            ->all();
    }
}
