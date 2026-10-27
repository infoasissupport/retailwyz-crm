<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeadService
{
    /** @param  array<string, mixed>  $data  validated lead fields plus optional first follow-up */
    public function create(array $data, User $user): Lead
    {
        return DB::transaction(function () use ($data, $user) {
            $lead = new Lead;
            $lead->fill($data + ['status' => LeadStatus::New->value]);
            $lead->forceFill(['created_by' => $user->id])->save();

            if (! empty($data['follow_up_at'])) {
                $this->scheduleFollowUp($lead, [
                    'type' => $data['follow_up_type'] ?? 'call',
                    'scheduled_at' => $data['follow_up_at'],
                    'assigned_to' => $data['assigned_to'] ?? $user->id,
                ], $user);
            }

            return $lead;
        });
    }

    public function update(Lead $lead, array $data): Lead
    {
        if ($lead->customer_id && ($data['status'] ?? null) !== LeadStatus::Won->value) {
            throw new BusinessRuleException('A converted lead stays won.');
        }

        $lead->update($data);

        // A closed lead has nothing left to follow up.
        if (! $lead->status->isOpen()) {
            $lead->followUps()->where('status', LeadFollowUp::PENDING)->update(['status' => LeadFollowUp::CANCELLED]);
        }

        return $lead;
    }

    public function scheduleFollowUp(Lead $lead, array $data, User $user): LeadFollowUp
    {
        if (! $lead->status->isOpen()) {
            throw new BusinessRuleException("Follow-ups cannot be scheduled on a {$lead->status->value} lead.");
        }

        $followUp = $lead->followUps()->make($data);
        $followUp->forceFill([
            'branch_id' => $lead->branch_id,
            'status' => LeadFollowUp::PENDING,
            'created_by' => $user->id,
        ])->save();

        return $followUp;
    }

    /** Complete a follow-up, optionally moving the lead on and booking the next one. */
    public function completeFollowUp(LeadFollowUp $followUp, array $data, User $user): LeadFollowUp
    {
        return DB::transaction(function () use ($followUp, $data, $user) {
            $followUp = LeadFollowUp::whereKey($followUp->id)->lockForUpdate()->firstOrFail();

            if (! $followUp->isPending()) {
                throw new BusinessRuleException('This follow-up is already closed.');
            }

            $followUp->forceFill([
                'status' => LeadFollowUp::DONE,
                'outcome' => $data['outcome'] ?? null,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ])->save();

            $lead = $followUp->lead()->firstOrFail();

            if (! empty($data['lead_status'])) {
                $this->update($lead, ['status' => $data['lead_status'], 'lost_reason' => $data['lost_reason'] ?? $lead->lost_reason]);
            } elseif ($lead->status === LeadStatus::New) {
                $lead->update(['status' => LeadStatus::Contacted]);
            }

            if (! empty($data['next_follow_up_at']) && $lead->refresh()->status->isOpen()) {
                $this->scheduleFollowUp($lead, [
                    'type' => $data['next_follow_up_type'] ?? $followUp->type->value,
                    'scheduled_at' => $data['next_follow_up_at'],
                    'assigned_to' => $followUp->assigned_to,
                ], $user);
            }

            return $followUp;
        });
    }

    public function cancelFollowUp(LeadFollowUp $followUp): void
    {
        if (! $followUp->isPending()) {
            throw new BusinessRuleException('This follow-up is already closed.');
        }

        $followUp->forceFill(['status' => LeadFollowUp::CANCELLED])->save();
    }

    /**
     * Turn a lead into a customer. Reuses an existing customer with the same
     * phone number instead of creating a duplicate.
     */
    public function convert(Lead $lead): Customer
    {
        return DB::transaction(function () use ($lead) {
            $lead = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($lead->customer_id) {
                throw new BusinessRuleException('This lead has already been converted.');
            }
            if ($lead->status === LeadStatus::Lost) {
                throw new BusinessRuleException('A lost lead cannot be converted. Reopen it first.');
            }

            $customer = $lead->phone ? Customer::where('phone', $lead->phone)->first() : null;

            if (! $customer) {
                // Same id-based code scheme as CustomerController.
                $customer = Customer::create([
                    'code' => 'TMP-'.uniqid(),
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                    'email' => $lead->email,
                    'address' => $lead->address,
                    'notes' => trim("Converted from lead #{$lead->id}. ".($lead->company ? "Company: {$lead->company}" : '')),
                ]);
                $customer->update(['code' => 'CUS-'.str_pad((string) $customer->id, 5, '0', STR_PAD_LEFT)]);
            }

            $lead->forceFill([
                'customer_id' => $customer->id,
                'status' => LeadStatus::Won,
                'converted_at' => now(),
            ])->save();

            $lead->followUps()->where('status', LeadFollowUp::PENDING)->update(['status' => LeadFollowUp::CANCELLED]);

            return $customer;
        });
    }
}
