<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->admin = $this->userWithRole();
    }

    public function test_super_admin_can_open_every_catalog_and_admin_page(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $unit = Unit::factory()->create();
        $customer = Customer::factory()->create();
        $supplier = Supplier::factory()->create();
        $other = $this->userWithRole('Cashier');
        $role = Role::findByName('Cashier');

        $urls = [
            route('categories.index'), route('categories.create'), route('categories.edit', $category),
            route('brands.index'), route('brands.create'), route('brands.edit', $brand),
            route('units.index'), route('units.create'), route('units.edit', $unit),
            route('customers.index'), route('customers.create'), route('customers.show', $customer), route('customers.edit', $customer),
            route('suppliers.index'), route('suppliers.create'), route('suppliers.show', $supplier), route('suppliers.edit', $supplier),
            route('users.index'), route('users.create'), route('users.show', $other), route('users.edit', $other),
            route('roles.index'), route('roles.permissions', $role),
            route('settings.general'), route('settings.invoice'), route('settings.branches'), route('settings.taxes'),
        ];

        foreach ($urls as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_category_and_brand_get_unique_slugs(): void
    {
        $this->actingAs($this->admin)->post(route('categories.store'), ['name' => 'Ceiling Fans', 'is_active' => 1])
            ->assertRedirect(route('categories.index'));
        $this->actingAs($this->admin)->post(route('categories.store'), ['name' => 'Ceiling Fans', 'is_active' => 1]);

        $this->assertEqualsCanonicalizing(['ceiling-fans', 'ceiling-fans-2'], Category::pluck('slug')->all());

        $this->actingAs($this->admin)->post(route('brands.store'), ['name' => 'Anchor Electricals', 'is_active' => 1]);
        $this->assertSame('anchor-electricals', Brand::first()->slug);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->delete(route('categories.destroy', $product->category_id))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('categories', ['id' => $product->category_id]);
    }

    public function test_customer_and_supplier_codes_are_generated_when_blank(): void
    {
        $this->actingAs($this->admin)->post(route('customers.store'), ['name' => 'Asha Traders', 'is_active' => 1])->assertRedirect();
        $customer = Customer::firstOrFail();
        $this->assertSame('CUS-'.str_pad((string) $customer->id, 5, '0', STR_PAD_LEFT), $customer->code);

        $this->actingAs($this->admin)->put(route('customers.update', $customer), ['name' => 'Asha Traders Pvt', 'code' => '', 'is_active' => 1]);
        $this->assertSame('Asha Traders Pvt', $customer->fresh()->name);
        $this->assertSame($customer->code, $customer->fresh()->code);

        $this->actingAs($this->admin)->post(route('suppliers.store'), ['name' => 'Havells Distributor', 'code' => 'HAV-01', 'is_active' => 1]);
        $this->assertSame('HAV-01', Supplier::firstOrFail()->code);
    }

    public function test_settings_and_taxes_can_be_saved(): void
    {
        $this->actingAs($this->admin)->put(route('settings.general.update'), [
            'business_name' => 'Wyz Retail', 'currency_code' => 'inr', 'currency_symbol' => '₹', 'date_format' => 'd-m-Y',
            'prices_include_tax' => 1, 'allow_negative_stock' => 0, 'purchase_prices_include_tax' => 0, 'update_cost_on_purchase' => 1,
        ])->assertRedirect(route('settings.general'));

        $settings = app(SettingsService::class);
        $this->assertSame('Wyz Retail', $settings->get('business_name'));
        $this->assertSame('INR', $settings->get('currency_code'));
        $this->assertTrue($settings->bool('prices_include_tax'));

        $this->actingAs($this->admin)->put(route('settings.invoice.update'), ['invoice_prefix' => 'bad prefix!'])
            ->assertSessionHasErrors('invoice_prefix');

        $this->actingAs($this->admin)->post(route('settings.taxes.store'), ['name' => 'GST 5%', 'rate' => '5', 'is_active' => 1])
            ->assertRedirect(route('settings.taxes'));
        $this->assertDatabaseHas('taxes', ['name' => 'GST 5%']);
    }

    public function test_role_permissions_can_be_updated_but_super_admin_role_is_locked(): void
    {
        $role = Role::findByName('Cashier');

        $this->actingAs($this->admin)->put(route('roles.permissions.update', $role), ['permissions' => ['sales.view', 'not.a.permission']])
            ->assertSessionHasErrors('permissions.1');

        $this->actingAs($this->admin)->put(route('roles.permissions.update', $role), ['permissions' => ['sales.view']])
            ->assertRedirect();
        $this->assertSame(['sales.view'], $role->fresh()->permissions->pluck('name')->all());

        $this->actingAs($this->admin)->put(route('roles.update', Role::findByName(User::SUPER_ADMIN_ROLE)), ['name' => 'Boss'])
            ->assertForbidden();
    }

    public function test_cashier_is_denied_admin_and_catalog_pages(): void
    {
        $cashier = $this->userWithRole('Cashier');

        foreach (['categories.index', 'users.index', 'roles.index', 'settings.general'] as $route) {
            $this->actingAs($cashier)->get(route($route))->assertForbidden();
        }
    }

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
    {
        $manager = $this->userWithPermissions(['users.view', 'users.manage']);

        $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'branch_id' => $this->branch->id,
            'password' => 'password123', 'password_confirmation' => 'password123',
            'roles' => [User::SUPER_ADMIN_ROLE], 'is_active' => 1,
        ])->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);

        $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'Clerk', 'email' => 'clerk@example.com', 'branch_id' => $this->branch->id,
            'password' => 'password123', 'password_confirmation' => 'password123',
            'roles' => ['Cashier'], 'is_active' => 1,
        ])->assertRedirect();

        $this->assertTrue(User::where('email', 'clerk@example.com')->firstOrFail()->hasRole('Cashier'));
    }

    public function test_user_cannot_delete_themselves_even_as_super_admin(): void
    {
        $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin))->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);

        $other = $this->userWithRole('Cashier');
        $this->actingAs($this->admin)->delete(route('users.destroy', $other))->assertRedirect(route('users.index'));
        $this->assertFalse($other->fresh()->is_active);
    }
}
