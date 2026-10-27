<?php

namespace Tests\Feature\Services;

use App\Enums\AdjustmentStatus;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\StockAdjustment;
use App\Services\InventoryService;
use App\Services\StockAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_stock_is_tracked_per_branch(): void
    {
        $product = $this->productWithStock('5');
        $other = Branch::factory()->create();

        $this->assertSame('0', app(InventoryService::class)->available($other->id, $product->id));
        $this->assertSame('5.000', app(InventoryService::class)->available($this->branch->id, $product->id));
    }

    public function test_untracked_products_never_create_movements(): void
    {
        $product = $this->productWithStock('0', ['track_stock' => false]);

        $movement = DB::transaction(fn () => app(InventoryService::class)
            ->move($this->branch->id, $product->id, null, '-3', StockMovementType::Sale));

        $this->assertNull($movement);
    }

    public function test_adjustment_changes_stock_only_after_approval_and_only_once(): void
    {
        $product = $this->productWithStock('10');
        $service = app(StockAdjustmentService::class);
        $creator = $this->userWithRole('Storekeeper');
        $manager = $this->userWithRole('Branch Manager');

        $adjustment = $service->create([
            'branch_id' => $this->branch->id,
            'type' => StockAdjustment::TYPE_OUT,
            'adjustment_date' => now()->toDateString(),
            'reason' => 'Damaged in transit',
            'items' => [['product_id' => $product->id, 'quantity' => '3']],
        ], $creator);

        $this->assertSame(AdjustmentStatus::Pending, $adjustment->status);
        $this->assertSame('10.000', app(InventoryService::class)->available($this->branch->id, $product->id));

        $service->approve($adjustment, $manager);
        $this->assertSame('7.000', app(InventoryService::class)->available($this->branch->id, $product->id));

        $this->expectException(BusinessRuleException::class);
        $service->approve($adjustment, $manager);
    }
}
