<?php

namespace Tests\Feature;

use App\Enums\AdjustmentStatus;
use App\Enums\PurchaseStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPurchasesInventoryHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    private function stock(Product $product): string
    {
        return app(InventoryService::class)->available($this->branch->id, $product->id);
    }

    private function makePurchase(Product $product, User $user): Purchase
    {
        return app(PurchaseService::class)->create([
            'branch_id' => $this->branch->id,
            'supplier_id' => Supplier::factory()->create()->id,
            'purchase_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '100']],
        ], $user);
    }

    private function makeSale(Product $product, User $user): Sale
    {
        return app(SaleService::class)->create([
            'branch_id' => $this->branch->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => '1']],
        ], $user);
    }

    public function test_super_admin_can_open_every_page_in_scope(): void
    {
        $admin = $this->userWithRole();
        $product = $this->productWithStock('10');
        $purchase = $this->makePurchase($product, $admin);
        $sale = $this->makeSale($product, $admin);

        $this->actingAs($admin);

        foreach ([
            route('inventory.index'), route('inventory.index', ['low_stock' => 1, 'q' => 'x']),
            route('inventory.movements', ['type' => 'sale']), route('inventory.stock-in'), route('inventory.stock-out'),
            route('inventory.adjustments.index'),
            route('purchases.index', ['status' => 'ordered']), route('purchases.create'),
            route('purchases.show', $purchase), route('purchases.edit', $purchase),
            route('sales.index', ['payment_status' => 'unpaid']), route('sales.create'),
            route('sales.show', $sale), route('sales.invoice', $sale), route('sales.returns.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sale_posted_over_http_deducts_stock_with_server_side_totals_and_is_idempotent(): void
    {
        $product = $this->productWithStock('10');
        $payload = [
            'sale_date' => now()->toDateString(),
            'idempotency_key' => 'form-token-1',
            'items' => [['product_id' => $product->id, 'quantity' => '2'], ['product_id' => '', 'quantity' => '']],
            'payments' => [['amount' => '100', 'method' => 'cash']],
        ];

        $this->actingAs($this->userWithRole())->post(route('sales.store'), $payload)->assertRedirect();
        $this->post(route('sales.store'), $payload)->assertRedirect();

        $this->assertSame(1, Sale::count());
        $sale = Sale::first();
        $this->assertSame('472.00', $sale->grand_total); // 2 x 200 + 18%
        $this->assertSame('100.00', $sale->paid_amount);
        $this->assertSame('8.000', $this->stock($product));
    }

    public function test_cashier_price_override_is_ignored(): void
    {
        $product = $this->productWithStock('10');

        $this->actingAs($this->userWithRole('Cashier'))->post(route('sales.store'), [
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => '1', 'unit_price' => '1.00']],
        ])->assertRedirect();

        $this->assertSame('200.00', Sale::first()->items()->first()->unit_price);
    }

    public function test_user_from_another_branch_cannot_view_sale(): void
    {
        $sale = $this->makeSale($this->productWithStock('5'), $this->userWithRole());
        $other = $this->userWithPermissions(['sales.view'], Branch::factory()->create());

        $this->actingAs($other)->get(route('sales.show', $sale))->assertForbidden();
        $this->get(route('sales.index'))->assertOk()->assertDontSee($sale->invoice_no);
    }

    public function test_adjustment_needs_a_different_approver(): void
    {
        $product = $this->productWithStock('10');
        $storekeeper = $this->userWithRole('Storekeeper');

        $this->actingAs($storekeeper)->post(route('inventory.adjustments.store'), [
            'type' => 'in',
            'adjustment_date' => now()->toDateString(),
            'reason' => 'Found in back room',
            'approve_now' => '1', // ignored: storekeepers cannot approve
            'items' => [['product_id' => $product->id, 'quantity' => '4'], ['product_id' => '', 'quantity' => '']],
        ])->assertRedirect(route('inventory.adjustments.index'));

        $adjustment = StockAdjustment::firstOrFail();
        $this->assertSame(AdjustmentStatus::Pending, $adjustment->status);
        $this->assertSame('10.000', $this->stock($product));

        $this->post(route('inventory.adjustments.approve', $adjustment))->assertForbidden();

        $this->actingAs($this->userWithRole('Branch Manager'))
            ->post(route('inventory.adjustments.approve', $adjustment))
            ->assertRedirect();

        $this->assertSame(AdjustmentStatus::Approved, $adjustment->refresh()->status);
        $this->assertSame('14.000', $this->stock($product));
    }

    public function test_stock_out_cannot_exceed_available_stock(): void
    {
        $product = $this->productWithStock('3');

        $this->actingAs($this->userWithRole())->from(route('inventory.stock-out'))->post(route('inventory.adjustments.store'), [
            'type' => 'out',
            'adjustment_date' => now()->toDateString(),
            'reason' => 'Damaged',
            'approve_now' => '1',
            'items' => [['product_id' => $product->id, 'quantity' => '5']],
        ])->assertRedirect(route('inventory.stock-out'))->assertSessionHas('error');

        $this->assertSame(0, StockAdjustment::count());
        $this->assertSame('3.000', $this->stock($product));
    }

    public function test_receiving_purchase_over_http_increases_stock(): void
    {
        $admin = $this->userWithRole();
        $product = $this->productWithStock('0');
        $purchase = $this->makePurchase($product, $admin);
        $item = $purchase->items()->first();

        $this->actingAs($admin)
            ->post(route('purchases.receive', $purchase), ['quantities' => [$item->id => '3']])
            ->assertRedirect(route('purchases.show', $purchase));

        $this->assertSame(PurchaseStatus::PartiallyReceived, $purchase->refresh()->status);
        $this->assertSame('3.000', $this->stock($product));
    }

    public function test_purchase_created_over_http_with_receive_now(): void
    {
        $product = $this->productWithStock('0');

        $this->actingAs($this->userWithRole())->post(route('purchases.store'), [
            'supplier_id' => Supplier::factory()->create()->id,
            'purchase_date' => now()->toDateString(),
            'status' => 'ordered',
            'receive_now' => '1',
            'items' => [['product_id' => $product->id, 'quantity' => '6', 'unit_cost' => '90']],
        ])->assertRedirect();

        $this->assertSame(PurchaseStatus::Received, Purchase::first()->status);
        $this->assertSame('6.000', $this->stock($product));
    }

    public function test_cashier_cannot_access_purchases(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))->get(route('purchases.index'))->assertForbidden();
    }

    public function test_sale_return_over_http_restocks(): void
    {
        $admin = $this->userWithRole();
        $product = $this->productWithStock('5');
        $sale = $this->makeSale($product, $admin);

        $this->actingAs($admin)->post(route('sales.returns.store', $sale), [
            'return_date' => now()->toDateString(),
            'restock' => '1',
            'items' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => '1']],
        ])->assertRedirect(route('sales.show', $sale));

        $this->assertSame('5.000', $this->stock($product));
        $this->assertSame('236.00', $sale->refresh()->returned_amount);
    }
}
