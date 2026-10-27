<?php

namespace App\Http\Requests;

use App\Enums\FollowUpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Scheduling a new follow-up on a lead. */
class FollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(FollowUpType::class)],
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
