<?php

namespace Tests;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected Branch $branch;

    /** Seed permissions and a default branch. Call from setUp() in tests using RefreshDatabase. */
    protected function seedFoundation(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->branch = Branch::factory()->create(['code' => 'MAIN']);
    }

    /** User in the default branch with the given role, or with direct permissions. */
    protected function userWithRole(string $role = User::SUPER_ADMIN_ROLE, ?Branch $branch = null): User
    {
        $user = User::factory()->create(['branch_id' => ($branch ?? $this->branch)->id]);
        $user->assignRole($role);

        return $user;
    }

    /** @param  list<string>  $permissions */
    protected function userWithPermissions(array $permissions, ?Branch $branch = null): User
    {
        $user = User::factory()->create(['branch_id' => ($branch ?? $this->branch)->id]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    /** Active product with an 18% tax and the given opening stock in the default branch. */
    protected function productWithStock(string $stock = '10', array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes + [
            'tax_id' => Tax::factory()->create(['rate' => 18])->id,
            'cost_price' => '100.00',
            'selling_price' => '200.00',
        ]);

        if ((float) $stock > 0) {
            DB::transaction(fn () => app(\App\Services\InventoryService::class)->move(
                $this->branch->id, $product->id, null, $stock, \App\Enums\StockMovementType::Opening,
            ));
        }

        return $product;
    }
}
