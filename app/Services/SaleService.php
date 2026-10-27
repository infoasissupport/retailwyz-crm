<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\Concerns\HandlesReturns;
use App\Support\LineCalculator;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class SaleService
{
    use HandlesReturns;

    public function __construct(
        private InventoryService $inventory,
        private PaymentService $payments,
        private DocumentNumberService $numbers,
        private SettingsService $settings,
    ) {}

    /**
     * Create a completed sale (invoice), deduct stock and record any payments.
     *
     * Prices and tax rates come from the product catalogue. A submitted
     * unit_price is honoured only when the user may override prices.
     *
     * @param  array{branch_id: int, customer_id?: ?int, sale_date: string, notes?: ?string, idempotency_key?: ?string,
     *     items: list<array{product_id: int, product_variant_id?: ?int, quantity: string, unit_price?: ?string, discount?: ?string}>,
     *     payments?: list<array{amount: string, method: string, reference?: ?string}>}  $data
     */
    public function create(array $data, User $user): Sale
    {
        $key = $data['idempotency_key'] ?? null;
        if ($key && $existing = Sale::where('idempotency_key', $key)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(fn () => $this->createInTransaction($data, $user));
        } catch (UniqueConstraintViolationException $e) {
            // Same form submitted twice concurrently: hand back the sale that won.
            return ($key ? Sale::where('idempotency_key', $key)->first() : null) ?? throw $e;
        }
    }

    public function cancel(Sale $sale, User $user): Sale
    {
        return DB::transaction(function () use ($sale, $user) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status === SaleStatus::Cancelled) {
                throw new BusinessRuleException('This sale is already cancelled.');
            }
            if ($sale->returns()->exists()) {
                throw new BusinessRuleException('A sale with returns cannot be cancelled.');
            }
            if (! Money::isZero($sale->paid_amount)) {
                throw new BusinessRuleException('Refund the payments received before cancelling this sale.');
            }

            foreach ($sale->items as $item) {
                $this->inventory->move(
                    $sale->branch_id, $item->product_id, $item->product_variant_id,
                    $item->quantity, StockMovementType::SaleCancellation, $item, $item->unit_cost,
                    "Cancelled {$sale->invoice_no}",
                );
            }

            $sale->forceFill([
                'status' => SaleStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
            ])->save();

            return $sale;
        });
    }

    /**
     * @param  array{return_date: string, reason?: ?string, restock?: bool,
     *     items: list<array{sale_item_id: int, quantity: string}>,
     *     refund?: ?array{amount: string, method: string, reference?: ?string}}  $data
     */
    public function createReturn(Sale $sale, array $data, User $user): SaleReturn
    {
        return DB::transaction(function () use ($sale, $data, $user) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status === SaleStatus::Cancelled) {
                throw new BusinessRuleException('Items cannot be returned on a cancelled sale.');
            }

            $restock = (bool) ($data['restock'] ?? true);
            $saleItems = $sale->items()->lockForUpdate()->get()->keyBy('id');

            $return = new SaleReturn;
            $return->forceFill([
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'reference_no' => $this->numbers->next('sale_return', 'sale_return_prefix'),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'restock' => $restock,
                'created_by' => $user->id,
            ])->save();

            $subtotal = $taxTotal = $grandTotal = '0';

            foreach ($this->mergeReturnLines($data['items'], 'sale_item_id') as $saleItemId => $quantity) {
                /** @var SaleItem|null $item */
                $item = $saleItems->get($saleItemId) ?? throw new BusinessRuleException('Returned item does not belong to this sale.');

                if (Money::cmp($quantity, $item->returnableQuantity()) > 0) {
                    throw new BusinessRuleException("Cannot return more than {$item->returnableQuantity()} of {$item->description}.");
                }

                [$lineTotal, $lineTax] = $this->returnShare($item, $quantity, 'sale_return_items', 'sale_item_id');

                $return->items()->create([
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $quantity,
                    'unit_price' => $item->unit_price,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $item->forceFill(['returned_quantity' => Money::add($item->returned_quantity, $quantity)])->save();

                if ($restock) {
                    $this->inventory->move(
                        $sale->branch_id, $item->product_id, $item->product_variant_id,
                        $quantity, StockMovementType::SaleReturn, $return, $item->unit_cost,
                    );
                }

                $subtotal = Money::add($subtotal, Money::sub($lineTotal, $lineTax));
                $taxTotal = Money::add($taxTotal, $lineTax);
                $grandTotal = Money::add($grandTotal, $lineTotal);
            }

            $return->forceFill([
                'subtotal' => Money::round($subtotal),
                'tax_total' => Money::round($taxTotal),
                'grand_total' => Money::round($grandTotal),
            ])->save();

            $sale->forceFill(['returned_amount' => Money::round(Money::add($sale->returned_amount, $grandTotal))]);
            $this->payments->refreshStatus($sale);

            if (! empty($data['refund']['amount']) && Money::cmp($data['refund']['amount'], '0') > 0) {
                $this->payments->record($sale, $data['refund'] + ['paid_on' => $data['return_date']], $user, refund: true);
            }

            return $return;
        });
    }

    private function createInTransaction(array $data, User $user): Sale
    {
        $inclusive = $this->settings->bool('prices_include_tax');
        $canOverridePrice = $user->can('sales.override-price');

        $sale = new Sale;
        $sale->fill([
            'branch_id' => $data['branch_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'sale_date' => $data['sale_date'],
            'notes' => $data['notes'] ?? null,
        ]);
        $sale->forceFill([
            'invoice_no' => $this->numbers->next('sale', 'invoice_prefix'),
            'status' => SaleStatus::Completed,
            'prices_include_tax' => $inclusive,
            'payment_status' => PaymentStatus::Unpaid,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'created_by' => $user->id,
        ])->save();

        $products = Product::with('tax')->whereIn('id', array_column($data['items'], 'product_id'))->get()->keyBy('id');
        $subtotal = $discountTotal = $taxTotal = $grandTotal = '0';

        foreach ($data['items'] as $line) {
            $product = $products->get($line['product_id']);
            if (! $product || $product->status !== ProductStatus::Active) {
                throw new BusinessRuleException('One of the selected products is not available for sale.');
            }

            $variant = $this->resolveVariant($product, $line['product_variant_id'] ?? null);
            $listPrice = $variant ? $variant->effectiveSellingPrice() : $product->selling_price;
            $unitPrice = ($canOverridePrice && isset($line['unit_price']) && $line['unit_price'] !== '')
                ? Money::round($line['unit_price'])
                : $listPrice;

            $amounts = LineCalculator::calculate($line['quantity'], $unitPrice, $line['discount'] ?? '0', $product->tax?->rate ?? '0', $inclusive);

            $item = $sale->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'description' => $variant ? "{$product->name} - {$variant->name}" : $product->name,
                'quantity' => $line['quantity'],
                'unit_price' => $unitPrice,
                'unit_cost' => $variant ? $variant->effectiveCostPrice() : $product->cost_price,
                'discount' => $amounts['discount'],
                'tax_rate' => $product->tax?->rate ?? '0',
                'tax_amount' => $amounts['tax'],
                'line_total' => $amounts['total'],
            ]);

            $this->inventory->move(
                $sale->branch_id, $product->id, $variant?->id,
                Money::mul($line['quantity'], '-1'), StockMovementType::Sale, $item, $item->unit_cost,
                $sale->invoice_no,
            );

            $subtotal = Money::add($subtotal, $amounts['gross']);
            $discountTotal = Money::add($discountTotal, $amounts['discount']);
            $taxTotal = Money::add($taxTotal, $amounts['tax']);
            $grandTotal = Money::add($grandTotal, $amounts['total']);
        }

        $sale->forceFill([
            'subtotal' => Money::round($subtotal),
            'discount_total' => Money::round($discountTotal),
            'tax_total' => Money::round($taxTotal),
            'grand_total' => Money::round($grandTotal),
        ])->save();

        foreach ($data['payments'] ?? [] as $payment) {
            if (! empty($payment['amount']) && Money::cmp($payment['amount'], '0') > 0) {
                $this->payments->record($sale, $payment + ['paid_on' => $data['sale_date']], $user);
            }
        }

        return $sale->refresh();
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if ($variantId === null) {
            if ($product->has_variants) {
                throw new BusinessRuleException("Select a variant for {$product->name}.");
            }

            return null;
        }

        return $product->variants()->where('is_active', true)->find($variantId)
            ?? throw new BusinessRuleException("Invalid variant selected for {$product->name}.");
    }
}
