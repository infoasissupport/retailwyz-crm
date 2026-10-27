<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_dashboard_accepts_presets_and_custom_ranges(): void
    {
        $this->actingAs($this->userWithRole());
        $this->productWithStock('1', ['min_stock' => 5]);

        foreach (array_keys(\App\Http\Controllers\DashboardController::PRESETS) as $preset) {
            $this->get(route('dashboard', ['range' => $preset]))->assertOk()->assertSee('Total sales');
        }

        $this->get(route('dashboard', ['from' => today()->subDays(10)->toDateString(), 'to' => today()->toDateString()]))
            ->assertOk()->assertSee('vs previous 11 days');

        // Reversed or junk ranges fall back to today instead of erroring.
        $this->get(route('dashboard', ['from' => today()->toDateString(), 'to' => today()->subDays(3)->toDateString()]))->assertOk()->assertSee('vs yesterday');
        $this->get(route('dashboard', ['range' => 'forever']))->assertOk();
    }

    public function test_bell_shows_low_stock_for_inventory_users_only(): void
    {
        $this->productWithStock('1', ['min_stock' => 5]);

        $this->actingAs($this->userWithRole('Storekeeper'))->get(route('dashboard'))
            ->assertSee('Products at or below minimum stock');

        $this->actingAs($this->userWithRole('Cashier'))->get(route('dashboard'))
            ->assertDontSee('Products at or below minimum stock');
    }

    public function test_search_respects_permissions_and_branches(): void
    {
        Customer::factory()->create(['name' => 'Zubin Mehta']);
        $foreignBranch = Branch::factory()->create();
        Lead::forceCreate(['branch_id' => $foreignBranch->id, 'name' => 'Zubin Lead Elsewhere', 'phone' => '1', 'source' => 'walk_in', 'status' => 'new']);
        Lead::forceCreate(['branch_id' => $this->branch->id, 'name' => 'Zubin Lead Here', 'phone' => '2', 'source' => 'walk_in', 'status' => 'new']);

        $this->actingAs($this->userWithRole('Cashier'))->get(route('search', ['q' => 'Zubin']))
            ->assertOk()
            ->assertSee('Zubin Mehta')
            ->assertSee('Zubin Lead Here')
            ->assertDontSee('Zubin Lead Elsewhere');

        // Storekeeper has no customer or lead access.
        $this->actingAs($this->userWithRole('Storekeeper'))->get(route('search', ['q' => 'Zubin']))
            ->assertOk()
            ->assertDontSee('Zubin Mehta')
            ->assertDontSee('Zubin Lead Here');

        $this->get(route('search', ['q' => 'Z']))->assertOk()->assertSee('at least two characters');
    }
}
