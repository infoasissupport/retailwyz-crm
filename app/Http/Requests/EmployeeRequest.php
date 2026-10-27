<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    /** Permission and branch checks happen in EmployeeController via EmployeePolicy. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($employee)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'designation' => ['nullable', 'string', 'max:100'],
            'joining_date' => ['nullable', 'date', 'before_or_equal:today'],
            'monthly_salary' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999999'],
            'status' => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('employees', 'user_id')->ignore($employee)],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return ['user_id.unique' => 'That login account is already linked to another employee.'];
    }
}
