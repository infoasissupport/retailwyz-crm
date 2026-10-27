<?php

namespace Tests\Feature;

use App\Enums\IncentiveStatus;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Incentive;
use App\Models\StaffTarget;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    private function employee(?User $login = null, array $attributes = []): Employee
    {
        $this->post(route('staff.employees.store'), $attributes + [
            'name' => 'Ravi Kumar',
            'designation' => 'Sales executive',
            'status' => 'active',
            'monthly_salary' => '18000',
            'user_id' => $login?->id,
        ])->assertSessionHasNoErrors();

        return Employee::latest('id')->firstOrFail();
    }

    public function test_pages_render_for_super_admin(): void
    {
        $this->actingAs($this->userWithRole());
        $employee = $this->employee();

        $this->get(route('staff.employees.index'))->assertOk()->assertSee('Ravi Kumar');
        $this->get(route('staff.employees.create'))->assertOk();
        $this->get(route('staff.employees.show', $employee))->assertOk();
        $this->get(route('staff.employees.edit', $employee))->assertOk();
        $this->get(route('staff.attendance.index'))->assertOk()->assertSee('Ravi Kumar');
        $this->get(route('staff.attendance.register'))->assertOk();
        $this->get(route('staff.targets.index'))->assertOk();
        $this->get(route('staff.incentives.index'))->assertOk();
    }

    public function test_employee_code_is_generated_and_branch_comes_from_the_user(): void
    {
        $this->actingAs($this->userWithRole('Branch Manager'));
        $employee = $this->employee(attributes: ['branch_id' => Branch::factory()->create()->id]);

        $this->assertSame('EMP-'.str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT), $employee->employee_code);
        $this->assertSame($this->branch->id, $employee->branch_id);
    }

    public function test_attendance_sheet_saves_and_updates_without_duplicates(): void
    {
        $this->actingAs($this->userWithRole('Branch Manager'));
        $a = $this->employee();
        $b = $this->employee(attributes: ['name' => 'Meena']);
        $date = today()->toDateString();

        $this->post(route('staff.attendance.store'), ['date' => $date, 'rows' => [
            $a->id => ['status' => 'present', 'check_in' => '09:30', 'check_out' => '18:00'],
            $b->id => ['status' => ''], // unmarked rows are skipped
        ]])->assertSessionHasNoErrors();

        $this->post(route('staff.attendance.store'), ['date' => $date, 'rows' => [
            $a->id => ['status' => 'half_day'],
        ]])->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertSame(1, Attendance::count());
        $this->assertSame('half_day', Attendance::first()->status->value);

        $this->post(route('staff.attendance.store'), ['date' => today()->addDay()->toDateString(), 'rows' => [$a->id => ['status' => 'present']]])
            ->assertSessionHasErrors('date');
    }

    public function test_attendance_cannot_be_marked_for_another_branch_employee(): void
    {
        $this->actingAs($this->userWithRole());
        $foreign = $this->employee(attributes: ['branch_id' => Branch::factory()->create()->id]);

        $this->actingAs($this->userWithRole('Branch Manager'))
            ->post(route('staff.attendance.store'), ['date' => today()->toDateString(), 'rows' => [$foreign->id => ['status' => 'present']]])
            ->assertSessionHas('error');

        $this->assertSame(0, Attendance::count());
    }

    public function test_met_target_raises_one_incentive_which_needs_independent_approval_and_posts_an_expense_when_paid(): void
    {
        $manager = $this->userWithRole('Branch Manager');
        $seller = $this->userWithRole('Cashier');
        $accountant = $this->userWithRole('Accountant');
        $accountant->givePermissionTo('expenses.manage');

        // Seller bills 2 x 200 + 18% = 472.00.
        $product = $this->productWithStock('10');
        app(SaleService::class)->create([
            'branch_id' => $this->branch->id,
            'sale_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => '2']],
        ], $seller);

        $this->actingAs($manager);
        $employee = $this->employee($seller);

        $this->post(route('staff.targets.store'), [
            'employee_id' => $employee->id,
            'month' => now()->format('Y-m'),
            'target_amount' => '400',
            'incentive_type' => 'percent',
            'incentive_value' => '5',
        ])->assertSessionHasNoErrors();

        $this->post(route('staff.targets.generate-incentives', ['month' => now()->format('Y-m')]))->assertSessionHasNoErrors();
        $this->post(route('staff.targets.generate-incentives', ['month' => now()->format('Y-m')])); // idempotent

        $incentive = Incentive::sole();
        $this->assertSame('23.60', $incentive->amount); // 5% of 472.00
        $this->assertSame(IncentiveStatus::Pending, $incentive->status);

        // The manager who raised it, and the seller who earns it, cannot approve it.
        $this->post(route('staff.incentives.approve', $incentive))->assertForbidden();
        $this->actingAs($seller)->post(route('staff.incentives.approve', $incentive))->assertForbidden();

        // A locked target cannot be edited once its incentive exists.
        $this->actingAs($manager)->put(route('staff.targets.update', StaffTarget::sole()), [
            'target_amount' => '100', 'incentive_type' => 'fixed', 'incentive_value' => '999',
        ])->assertSessionHas('error');

        $this->actingAs($accountant)->post(route('staff.incentives.approve', $incentive))->assertSessionHasNoErrors();
        $this->actingAs($accountant)->post(route('staff.incentives.pay', $incentive), [
            'payment_method' => 'bank_transfer',
            'paid_on' => today()->toDateString(),
        ])->assertSessionHasNoErrors();

        $incentive->refresh();
        $this->assertSame(IncentiveStatus::Paid, $incentive->status);
        $this->assertSame('23.60', Expense::findOrFail($incentive->expense_id)->amount);

        // Paying twice is refused.
        $this->actingAs($accountant)->post(route('staff.incentives.pay', $incentive), [
            'payment_method' => 'cash', 'paid_on' => today()->toDateString(),
        ])->assertSessionHas('error');
        $this->assertSame(1, Expense::count());
    }

    public function test_access_rules(): void
    {
        $this->actingAs($this->userWithRole());
        $employee = $this->employee();

        $this->actingAs($this->userWithRole('Cashier'))->get(route('staff.employees.index'))->assertForbidden();
        $this->actingAs($this->userWithRole('Storekeeper'))->get(route('staff.attendance.index'))->assertForbidden();

        // Accountant can view staff but cannot mark attendance or see salaries.
        $accountant = $this->userWithRole('Accountant');
        $this->actingAs($accountant)->get(route('staff.employees.show', $employee))->assertOk()->assertDontSee('18,000.00');
        $this->actingAs($accountant)->post(route('staff.attendance.store'), ['date' => today()->toDateString(), 'rows' => [$employee->id => ['status' => 'present']]])->assertForbidden();

        $other = $this->userWithRole('Branch Manager', Branch::factory()->create());
        $this->actingAs($other)->get(route('staff.employees.show', $employee))->assertForbidden();
    }
}
