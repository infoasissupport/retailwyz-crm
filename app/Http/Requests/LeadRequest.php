<?php

namespace App\Http\Requests;

use App\Enums\FollowUpType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    /** Permission and branch checks happen in LeadController via LeadPolicy. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->route('lead') === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:30'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'source' => ['required', Rule::enum(LeadSource::class)],
            'status' => [$creating ? 'nullable' : 'required', Rule::enum(LeadStatus::class)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'requirement' => ['nullable', 'string', 'max:2000'],
            'expected_value' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999999'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'lost_reason' => ['nullable', 'required_if:status,lost', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],

            // Optional first follow-up when creating.
            'follow_up_at' => [$creating ? 'nullable' : 'prohibited', 'date', 'after_or_equal:today'],
            'follow_up_type' => ['nullable', Rule::enum(FollowUpType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without' => 'Enter a phone number or an email address.',
            'email.required_without' => 'Enter a phone number or an email address.',
            'lost_reason.required_if' => 'Give a reason when marking a lead as lost.',
        ];
    }
}
