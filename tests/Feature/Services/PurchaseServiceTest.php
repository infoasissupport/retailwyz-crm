<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Supplier;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseService $purchases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->purchases = app(PurchaseService::class);
    }

    private function order(int $productId, string $qty = '10', string $cost = '80'): array
    {
        return [
            'branch_id' => $this->branch->id,
            'supplier_id' => Supplier::factory()->create()->id,
            'purchase_date' => now()->toDateString(),
            'shipping_cost' => '50',
            'items' => [['product_id' => $productId, 'quantity' => $qty, 'unit_cost' => $cost]],
        ];
    }

    public function test_ordering_does_not_touch_stock_until_received(): void
    {
        $product = $this->productWithStock('0');
        $purchase = $this->purchases->create($this->order($product->id), $this->userWithRole());

        $this->assertSame(PurchaseStatus::Ordered, $purchase->status);
        $this->assertSame('994.00', $purchase->grand_total); // 800 + 18% + 50 shipping
        $this->assertSame('0', app(InventoryService::class)->available($this->branch->id, $product->id));
    }

    public function test_partial_then_full_receipt_and_over_receipt_is_rejected(): void
    {
        $product = $this->productWithStock('0');
        $user = $this->userWithRole();
        $purchase = $this->purchases->create($this->order($product->id), $user);
        $item = $purchase->items()->first();

        $this->purchases->receive($purchase, [$item->id => '4'], $user);
        $this->assertSame(PurchaseStatus::PartiallyReceived, $purchase->refresh()->status);

        try {
            $this->purchases->receive($purchase, [$item->id => '7'], $user);
            $this->fail('Over-receipt should be rejected');
        } catch (BusinessRuleException) {
            // expected: only 6 pending
        }

        $this->purchases->receive($purchase, null, $user);
        $this->assertSame(PurchaseStatus::Received, $purchase->refresh()->status);
        $this->assertSame('10.000', app(InventoryService::class)->available($this->branch->id, $product->id));
        $this->assertSame('80.00', $product->refresh()->cost_price);

        // Receiving again (e.g. a retried request) changes nothing.
        $this->expectException(BusinessRuleException::class);
        $this->purchases->receive($purchase, null, $user);
    }

    public function test_return_reduces_stock_and_payable_and_allows_supplier_refund(): void
    {
        $product = $this->productWithStock('0');
        $user = $this->userWithRole();
        $purchase = $this->purchases->create($this->order($product->id, '10', '100') + ['receive_now' => true], $user);

        app(PaymentService::class)->record($purchase, ['amount' => $purchase->grand_total, 'method' => 'bank_transfer'], $user);
        $this->assertSame(PaymentStatus::Paid, $purchase->refresh()->payment_status);

        $item = $purchase->items()->first();
        $this->purchases->createReturn($purchase, [
            'return_date' => now()->toDateString(),
            'items' => [['purchase_item_id' => $item->id, 'quantity' => '2']],
            'refund' => ['amount' => '236.00', 'method' => 'bank_transfer'],
        ], $user);

        $purchase->refresh();
        $this->assertSame('236.00', $purchase->returned_amount); // 2 x 100 + 18%
        $this->assertSame(PaymentStatus::Paid, $purchase->payment_status);
        $this->assertSame('0.00', $purchase->balanceDue());
        $this->assertSame('8.000', app(InventoryService::class)->available($this->branch->id, $product->id));
    }

    public function test_received_purchase_cannot_be_cancelled_or_edited(): void
    {
        $product = $this->productWithStock('0');
        $user = $this->userWithRole();
        $purchase = $this->purchases->create($this->order($product->id) + ['receive_now' => true], $user);

        $this->expectException(BusinessRuleException::class);
        $this->purchases->cancel($purchase);
    }
}
