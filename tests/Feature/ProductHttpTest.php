<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductHttpTest extends TestCase
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
            'name' => 'LED panel 18W',
            'sku' => 'LED-PNL-18',
            'category_id' => Category::factory()->create()->id,
            'unit_id' => Unit::factory()->create()->id,
            'cost_price' => '250',
            'selling_price' => '399.50',
            'status' => 'active',
            'track_stock' => '1',
            'has_variants' => '0',
            'specifications' => [['key' => 'wattage', 'value' => '18W'], ['key' => '', 'value' => '']],
            'opening_stock' => '12',
        ];
    }

    public function test_product_pages_render(): void
    {
        $product = $this->productWithStock('3');
        $this->actingAs($this->userWithRole());

        $this->get(route('products.index'))->assertOk()->assertSee($product->name);
        $this->get(route('products.create'))->assertOk();
        $this->get(route('products.show', $product))->assertOk();
        $this->get(route('products.edit', $product))->assertOk();
        $this->getJson(route('products.lookup', ['q' => $product->sku]))->assertOk()->assertJsonPath('0.id', $product->id);
    }

    public function test_creating_a_product_stores_specifications_and_opening_stock(): void
    {
        $this->actingAs($this->userWithRole('Storekeeper'))
            ->post(route('products.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $product = Product::where('sku', 'LED-PNL-18')->firstOrFail();
        $this->assertSame(['wattage' => '18W'], $product->specifications);
        $this->assertSame('12.000', app(InventoryService::class)->available($this->branch->id, $product->id));
    }

    public function test_products_with_variants_require_unique_variant_skus(): void
    {
        $this->actingAs($this->userWithRole())
            ->post(route('products.store'), $this->payload([
                'has_variants' => '1',
                'variants' => [
                    ['name' => 'Warm white', 'sku' => 'V-1'],
                    ['name' => 'Cool white', 'sku' => 'V-1'],
                ],
            ]))
            ->assertSessionHasErrors('variants.1.sku');
    }

    public function test_cashier_cannot_create_or_delete_products_and_cannot_see_cost(): void
    {
        $product = $this->productWithStock('1');
        $cashier = $this->userWithRole('Cashier');

        $this->actingAs($cashier)->get(route('products.create'))->assertForbidden();
        $this->actingAs($cashier)->post(route('products.store'), $this->payload())->assertForbidden();
        $this->actingAs($cashier)->delete(route('products.destroy', $product))->assertForbidden();
        $this->actingAs($cashier)->getJson(route('products.lookup'))->assertOk()->assertJsonPath('0.cost_price', null);
    }
}
