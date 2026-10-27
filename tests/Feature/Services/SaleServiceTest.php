<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\FinancialTransaction;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Services\SaleService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $sales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->sales = app(SaleService::class);
    }

    private function saleData(int $productId, string $qty = '2', array $extra = []): array
    {
        return $extra + [
            'branch_id' => $this->branch->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $productId, 'quantity' => $qty]],
        ];
    }

    public function test_sale_calculates_tax_exclusive_totals_server_side_and_deducts_stock(): void
    {
        $product = $this->productWithStock('10');
        $user = $this->userWithRole('Cashier');

        // Cashier cannot override price: the submitted 1.00 must be ignored.
        $sale = $this->sales->create($this->saleData($product->id, '3', [
            'items' => [['product_id' => $product->id, 'quantity' => '3', 'unit_price' => '1.00', 'discount' => '10']],
        ]), $user);

        $this->assertSame('600.00', $sale->subtotal);      // 3 x 200
        $this->assertSame('10.00', $sale->discount_total);
        $this->assertSame('106.20', $sale->tax_total);     // 18% of 590
        $this->assertSame('696.20', $sale->grand_total);
        $this->assertSame(PaymentStatus::Unpaid, $sale->payment_status);
        $this->assertSame('7.000', app(InventoryService::class)->available($this->branch->id, $product->id));
    }

    public function test_tax_inclusive_pricing_extracts_tax_from_price(): void
    {
        app(SettingsService::class)->setMany('sales', ['prices_include_tax' => '1']);
        $product = $this->productWithStock('5', ['selling_price' => '118.00']);

        $sale = $this->sales->create($this->saleData($product->id, '1'), $this->userWithRole());

        $this->assertSame('118.00', $sale->grand_total);
        $this->assertSame('18.00', $sale->tax_total);
        $this->assertTrue($sale->prices_include_tax);
    }

    public function test_overselling_is_rejected_and_nothing_is_persisted(): void
    {
        $product = $this->productWithStock('2');

        try {
            $this->sales->create($this->saleData($product->id, '3'), $this->userWithRole());
            $this->fail('Expected InsufficientStockException');
        } catch (InsufficientStockException) {
            // expected
        }

        $this->assertSame(0, Sale::count());
        $this->assertSame('2.000', app(InventoryService::class)->available($this->branch->id, $product->id));
    }

    public function test_retried_submission_with_same_idempotency_key_does_not_deduct_twice(): void
    {
        $product = $this->productWithStock('10');
        $user = $this->userWithRole();
        $data = $this->saleData($product->id, '2', ['idempotency_key' => 'abc-123']);

        $first = $this->sales->create($data, $user);
        $second = $this->sales->create($data, $user);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Sale::count());
        $this->assertSame('8.000', app(InventoryService::class)->available($this->branch->id, $product->id));
    }

    public function test_partial_payment_then_full_payment_updates_status_and_ledger(): void
    {
        $product = $this->productWithStock('10');
        $user = $this->userWithRole();

        $sale = $this->sales->create($this->saleData($product->id, '1', [
            'payments' => [['amount' => '100', 'method' => 'cash']],
        ]), $user);

        $this->assertSame(PaymentStatus::Partial, $sale->payment_status);
        $this->assertSame('136.00', $sale->balanceDue()); // 236 - 100

        app(\App\Services\PaymentService::class)->record($sale, ['amount' => '136', 'method' => 'upi'], $user);
        $sale->refresh();

        $this->assertSame(PaymentStatus::Paid, $sale->payment_status);
        $this->assertSame(0, \App\Support\Money::cmp('236.00', FinancialTransaction::where('direction', 'in')->sum('amount')));
    }

    public function test_overpayment_is_rejected(): void
    {
        $product = $this->productWithStock('10');
        $sale = $this->sales->create($this->saleData($product->id, '1'), $user = $this->userWithRole());

        $this->expectException(BusinessRuleException::class);
        app(\App\Services\PaymentService::class)->record($sale, ['amount' => '500', 'method' => 'cash'], $user);
    }

    public function test_partial_returns_restock_and_final_return_leaves_no_rounding_residue(): void
    {
        $product = $this->productWithStock('10', ['selling_price' => '33.33']);
        $user = $this->userWithRole();
        $sale = $this->sales->create($this->saleData($product->id, '3'), $user);
        $item = $sale->items()->first();

        foreach (['1', '1', '1'] as $qty) {
            $this->sales->createReturn($sale, [
                'return_date' => now()->toDateString(),
                'items' => [['sale_item_id' => $item->id, 'quantity' => $qty]],
            ], $user);
        }

        $sale->refresh();
        $this->assertSame($sale->grand_total, $sale->returned_amount);
        $this->assertSame('10.000', app(InventoryService::class)->available($this->branch->id, $product->id));

        $this->expectException(BusinessRuleException::class);
        $this->sales->createReturn($sale, [
            'return_date' => now()->toDateString(),
            'items' => [['sale_item_id' => $item->id, 'quantity' => '1']],
        ], $user);
    }

    public function test_cancelling_restocks_and_requires_refund_first(): void
    {
        $product = $this->productWithStock('5');
        $user = $this->userWithRole();

        $paid = $this->sales->create($this->saleData($product->id, '1', ['payments' => [['amount' => '50', 'method' => 'cash']]]), $user);
        try {
            $this->sales->cancel($paid, $user);
            $this->fail('Expected refund-first rule');
        } catch (BusinessRuleException) {
            // expected
        }

        $unpaid = $this->sales->create($this->saleData($product->id, '2'), $user);
        $this->sales->cancel($unpaid, $user);

        $this->assertSame(SaleStatus::Cancelled, $unpaid->refresh()->status);
        $this->assertSame('4.000', app(InventoryService::class)->available($this->branch->id, $product->id));
        $this->assertSame(4, StockMovement::count()); // opening, 2 sales, 1 cancellation
    }
}
