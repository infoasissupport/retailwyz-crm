<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /** Route middleware already checks catalog.manage. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'short_name' => ['required', 'string', 'max:20', Rule::unique('units', 'short_name')->ignore($this->route('unit'))],
            'allow_decimal' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
