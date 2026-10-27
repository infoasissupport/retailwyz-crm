<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    private function createLead(array $overrides = []): Lead
    {
        $this->post(route('leads.store'), $overrides + [
            'name' => 'Anita Sharma',
            'phone' => '9876543210',
            'source' => 'walk_in',
            'requirement' => 'Sofa set and ceiling fans for new flat',
            'expected_value' => '85000',
            'follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'follow_up_type' => 'call',
        ])->assertSessionHasNoErrors();

        return Lead::latest('id')->firstOrFail();
    }

    public function test_pages_render_for_super_admin(): void
    {
        $this->actingAs($this->userWithRole());
        $lead = $this->createLead();

        $this->get(route('leads.index'))->assertOk()->assertSee('Anita Sharma');
        $this->get(route('leads.create'))->assertOk();
        $this->get(route('leads.show', $lead))->assertOk();
        $this->get(route('leads.edit', $lead))->assertOk();
        foreach (['due', 'overdue', 'today', 'upcoming', 'done'] as $view) {
            $this->get(route('leads.follow-ups.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_creating_a_lead_schedules_its_first_follow_up_in_the_users_branch(): void
    {
        $this->actingAs($cashier = $this->userWithRole('Cashier'));
        $lead = $this->createLead(['branch_id' => Branch::factory()->create()->id]); // ignored for branch-bound users

        $this->assertSame($this->branch->id, $lead->branch_id);
        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertSame(1, $lead->followUps()->where('status', LeadFollowUp::PENDING)->count());
        $this->assertSame($cashier->id, $lead->created_by);
    }

    public function test_completing_a_follow_up_advances_the_lead_and_books_the_next_one(): void
    {
        $this->actingAs($this->userWithRole('Cashier'));
        $lead = $this->createLead();
        $followUp = $lead->followUps()->first();

        $this->post(route('leads.follow-ups.complete', $followUp), [
            'outcome' => 'Interested, wants a quotation',
            'lead_status' => 'qualified',
            'next_follow_up_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'next_follow_up_type' => 'visit',
        ])->assertSessionHasNoErrors();

        $this->assertSame(LeadFollowUp::DONE, $followUp->refresh()->status);
        $this->assertSame(LeadStatus::Qualified, $lead->refresh()->status);
        $this->assertSame(1, $lead->followUps()->where('status', LeadFollowUp::PENDING)->where('type', 'visit')->count());

        // Completing the same follow-up twice is refused.
        $this->post(route('leads.follow-ups.complete', $followUp), ['outcome' => 'again'])->assertSessionHas('error');
    }

    public function test_marking_a_lead_lost_requires_a_reason_and_cancels_pending_follow_ups(): void
    {
        $this->actingAs($this->userWithRole());
        $lead = $this->createLead();
        $payload = ['name' => $lead->name, 'phone' => $lead->phone, 'source' => 'walk_in', 'status' => 'lost'];

        $this->put(route('leads.update', $lead), $payload)->assertSessionHasErrors('lost_reason');

        $this->put(route('leads.update', $lead), $payload + ['lost_reason' => 'Bought elsewhere'])->assertSessionHasNoErrors();
        $this->assertSame(0, $lead->followUps()->where('status', LeadFollowUp::PENDING)->count());
    }

    public function test_converting_a_lead_creates_one_customer_and_reuses_an_existing_phone_match(): void
    {
        $this->actingAs($this->userWithRole());
        $lead = $this->createLead();

        $this->post(route('leads.convert', $lead))->assertSessionHasNoErrors();
        $lead->refresh();
        $this->assertSame(LeadStatus::Won, $lead->status);
        $this->assertNotNull($lead->customer_id);
        $this->assertStringStartsWith('CUS-', $lead->customer->code);

        // Converting again does nothing.
        $this->post(route('leads.convert', $lead))->assertSessionHas('error');

        $existing = Customer::factory()->create(['phone' => '9000000001']);
        $second = $this->createLead(['phone' => '9000000001']);
        $this->post(route('leads.convert', $second));
        $this->assertSame($existing->id, $second->refresh()->customer_id);
        $this->assertSame(2, Customer::count());
    }

    public function test_access_is_restricted_by_permission_and_branch(): void
    {
        $this->actingAs($this->userWithRole());
        $lead = $this->createLead();

        $this->actingAs($this->userWithRole('Storekeeper'))->get(route('leads.index'))->assertForbidden();

        $otherBranchUser = $this->userWithRole('Branch Manager', Branch::factory()->create());
        $this->actingAs($otherBranchUser)->get(route('leads.show', $lead))->assertForbidden();
        $this->actingAs($otherBranchUser)->get(route('leads.index'))->assertOk()->assertDontSee('Anita Sharma');
        $this->actingAs($otherBranchUser)->post(route('leads.follow-ups.complete', $lead->followUps()->first()), ['outcome' => 'x'])->assertForbidden();
    }
}
