<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Employee;
use App\Services\SaleService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'customer_name' => 'Kavitha Rao',
            'phone' => '+91 88001 22222',
            'address' => '12 MG Road, Pune',
            'scheduled_date' => today()->addDay()->toDateString(),
            'driver_name' => 'Mohd Arif',
            'vehicle_no' => 'MH-04-AB-1234',
            'order_value' => '28000',
        ];
    }

    private function schedule(array $overrides = []): Delivery
    {
        $this->post(route('deliveries.store'), $this->payload($overrides))->assertSessionHasNoErrors();

        return Delivery::latest('id')->firstOrFail();
    }

    public function test_pages_render_and_numbers_are_generated(): void
    {
        $this->actingAs($this->userWithRole());
        $delivery = $this->schedule();

        $this->assertSame('DEL-00001', $delivery->delivery_no);
        $this->get(route('deliveries.index'))->assertOk()->assertSee('DEL-00001')->assertSee('Kavitha Rao');
        $this->get(route('deliveries.index', ['status' => 'pending']))->assertOk();
        $this->get(route('deliveries.create'))->assertOk();
        $this->get(route('deliveries.show', $delivery))->assertOk();
        $this->get(route('deliveries.edit', $delivery))->assertOk();
    }

    public function test_full_status_flow_and_no_double_transitions(): void
    {
        $this->actingAs($this->userWithRole('Storekeeper'));
        $delivery = $this->schedule();

        $this->post(route('deliveries.start', $delivery))->assertSessionHasNoErrors();
        $this->post(route('deliveries.start', $delivery))->assertSessionHas('error'); // already out
        $this->assertSame(DeliveryStatus::OutForDelivery, $delivery->refresh()->status);
        $this->assertNotNull($delivery->dispatched_at);

        // Out-for-delivery details are locked.
        $this->put(route('deliveries.update', $delivery), $this->payload(['address' => 'Changed']))->assertSessionHas('error');

        $this->post(route('deliveries.complete', $delivery), ['received_by' => 'Kavitha'])->assertSessionHasNoErrors();
        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Delivered, $delivery->status);
        $this->assertSame('Kavitha', $delivery->received_by);
    }

    public function test_failed_delivery_can_be_rescheduled_and_start_requires_a_driver(): void
    {
        $this->actingAs($this->userWithRole());
        $delivery = $this->schedule(['driver_name' => '']);

        $this->post(route('deliveries.start', $delivery))->assertSessionHas('error');

        $this->put(route('deliveries.update', $delivery), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('deliveries.start', $delivery));
        $this->post(route('deliveries.fail', $delivery), ['reason' => 'Customer not home'])->assertSessionHasNoErrors();
        $this->assertSame(DeliveryStatus::Failed, $delivery->refresh()->status);

        $this->put(route('deliveries.update', $delivery), $this->payload(['scheduled_date' => today()->addDays(3)->toDateString()]))->assertSessionHasNoErrors();
        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        $this->assertNull($delivery->failure_reason);

        $this->post(route('deliveries.start', $delivery));
        $this->assertSame(2, $delivery->refresh()->attempts);
    }

    public function test_delivery_from_a_sale_uses_the_invoice_value_and_does_not_touch_stock(): void
    {
        $user = $this->userWithRole();
        $product = $this->productWithStock('5');
        $customer = Customer::factory()->create();
        $sale = app(SaleService::class)->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'sale_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => '2']],
        ], $user);

        $this->actingAs($user)->get(route('deliveries.create', ['sale_id' => $sale->id]))->assertOk()->assertSee($customer->name);

        $delivery = $this->schedule(['sale_id' => $sale->id, 'order_value' => '1']); // submitted value is ignored
        $this->assertSame($sale->grand_total, $delivery->order_value);
        $this->assertSame($customer->id, $delivery->customer_id);
        $this->assertSame('3.000', app(InventoryService::class)->available($this->branch->id, $product->id));
    }

    public function test_staff_driver_must_belong_to_the_branch_and_fills_driver_details(): void
    {
        $this->actingAs($this->userWithRole());
        $driver = Employee::create(['branch_id' => $this->branch->id, 'employee_code' => 'EMP-1', 'name' => 'Suresh', 'phone' => '9000011111', 'status' => 'active']);
        $foreign = Employee::create(['branch_id' => Branch::factory()->create()->id, 'employee_code' => 'EMP-2', 'name' => 'Other', 'status' => 'active']);

        $delivery = $this->schedule(['employee_id' => $driver->id, 'driver_name' => '']);
        $this->assertSame('Suresh', $delivery->driver_name);
        $this->assertSame('9000011111', $delivery->driver_phone);

        $this->post(route('deliveries.store'), $this->payload(['employee_id' => $foreign->id]))->assertSessionHas('error');
    }

    public function test_access_is_restricted_by_permission_and_branch(): void
    {
        $this->actingAs($this->userWithRole());
        $delivery = $this->schedule();

        $accountant = $this->userWithRole('Accountant');
        $this->actingAs($accountant)->get(route('deliveries.index'))->assertOk();
        $this->actingAs($accountant)->post(route('deliveries.start', $delivery))->assertForbidden();

        $other = $this->userWithRole('Branch Manager', Branch::factory()->create());
        $this->actingAs($other)->get(route('deliveries.show', $delivery))->assertForbidden();
        $this->actingAs($other)->get(route('deliveries.index'))->assertOk()->assertDontSee('Kavitha Rao');
    }
}
