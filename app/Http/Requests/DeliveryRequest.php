<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryRequest extends FormRequest
{
    /** Permission and branch checks happen in DeliveryController via DeliveryPolicy. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->route('delivery') === null;

        return [
            'sale_id' => [$creating ? 'nullable' : 'prohibited', 'integer', Rule::exists('sales', 'id')],
            'customer_id' => [$creating ? 'nullable' : 'prohibited', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'time_slot' => ['nullable', 'string', 'max:50'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'vehicle_no' => ['nullable', 'string', 'max:30'],
            'order_value' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999999'],
            'delivery_charge' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
