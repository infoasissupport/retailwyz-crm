<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxRequest extends FormRequest
{
    /** Route middleware already checks settings.manage. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('taxes', 'code')->ignore($this->route('tax'))],
            'rate' => ['required', 'decimal:0,3', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
