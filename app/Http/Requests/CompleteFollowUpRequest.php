<?php

namespace App\Http\Requests;

use App\Enums\FollowUpType;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', 'string', 'max:2000'],
            'lead_status' => ['nullable', Rule::enum(LeadStatus::class)],
            'lost_reason' => ['nullable', 'required_if:lead_status,lost', 'string', 'max:255'],
            'next_follow_up_at' => ['nullable', 'date', 'after_or_equal:today'],
            'next_follow_up_type' => ['nullable', Rule::enum(FollowUpType::class)],
        ];
    }
}
