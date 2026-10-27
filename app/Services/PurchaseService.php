<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\User;
use App\Services\Concerns\HandlesReturns;
use App\Support\LineCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    use HandlesReturns;

    public function __construct(
        private InventoryService $inventory,
        private PaymentService $payments,
        private DocumentNumberService $numbers,
        private SettingsService $settings,
    ) {}

    /**
     * @param  array{branch_id: int, supplier_id: int, purchase_date: string, expected_date?: ?string, supplier_invoice_no?: ?string,
     *     shipping_cost?: ?string, notes?: ?string, status?: string, receive_now?: bool,
     *     items: list<array{product_id: int, product_variant_id?: ?int, quantity: string, unit_cost: string, discount?: ?string}>}  $data
     */
    public function create(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            $purchase = new Purchase;
            $purchase->fill($data);
            $purchase->forceFill([
                'reference_no' => $this->numbers->next('purchase', 'purchase_prefix'),
                'status' => ($data['status'] ?? null) === PurchaseStatus::Draft->value ? PurchaseStatus::Draft : PurchaseStatus::Ordered,
                'prices_include_tax' => $this->settings->bool('purchase_prices_include_tax'),
                'payment_status' => PaymentStatus::Unpaid,
                'created_by' => $user->id,
            ])->save();

            $this->syncItems($purchase, $data['items']);

            if (! empty($data['receive_now']) && $purchase->status === PurchaseStatus::Ordered) {
                $this->receive($purchase, null, $user);
            }

            return $purchase->refresh();
        });
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if (! $purchase->status->isEditable() || ! Money::isZero($purchase->paid_amount)) {
                throw new BusinessRuleException('Only unpaid draft or ordered purchases can be edited.');
            }

            $purchase->fill($data);
            if (isset($data['status']) && in_array($data['status'], [PurchaseStatus::Draft->value, PurchaseStatus::Ordered->value], true)) {
                $purchase->forceFill(['status' => PurchaseStatus::from($data['status'])]);
            }
            $purchase->save();

            $purchase->items()->delete();
            $this->syncItems($purchase, $data['items']);

            return $purchase->refresh();
        });
    }

    /**
     * Receive goods into stock. $quantities maps purchase_item_id => quantity;
     * null receives everything still pending.
     *
     * @param  array<int, string>|null  $quantities
     */
    public function receive(Purchase $purchase, ?array $quantities, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $quantities, $user) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if (! $purchase->status->canReceive()) {
                throw new BusinessRuleException("A {$purchase->status->label()} purchase cannot be received.");
            }

            $updateCost = $this->settings->bool('update_cost_on_purchase');
            $receivedAny = false;

            foreach ($purchase->items()->with(['product', 'variant'])->lockForUpdate()->get() as $item) {
                $pending = $item->pendingQuantity();
                $quantity = $quantities === null ? $pending : Money::normalize($quantities[$item->id] ?? '0');

                if (Money::cmp($quantity, '0') <= 0) {
                    continue;
                }
                if (Money::cmp($quantity, $pending) > 0) {
                    throw new BusinessRuleException("Cannot receive more than the {$pending} pending for {$item->product->name}.");
                }

                $unitCost = $this->netUnitCost($item);

                $this->inventory->move(
                    $purchase->branch_id, $item->product_id, $item->product_variant_id,
                    $quantity, StockMovementType::Purchase, $item, $unitCost, $purchase->reference_no,
                );

                $item->forceFill(['received_quantity' => Money::add($item->received_quantity, $quantity)])->save();
                $receivedAny = true;

                if ($updateCost) {
                    $target = $item->product_variant_id ? $item->variant : $item->product;
                    $target->forceFill(['cost_price' => $unitCost])->save();
                }
            }

            if (! $receivedAny) {
                throw new BusinessRuleException('Enter a quantity to receive for at least one item.');
            }

            $fullyReceived = $purchase->items()->get()->every(fn (PurchaseItem $i) => Money::cmp($i->received_quantity, $i->quantity) >= 0);

            $purchase->forceFill([
                'status' => $fullyReceived ? PurchaseStatus::Received : PurchaseStatus::PartiallyReceived,
                'received_at' => $fullyReceived ? now() : $purchase->received_at,
            ])->save();

            return $purchase;
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if (! $purchase->status->isEditable()) {
                throw new BusinessRuleException('Purchases with received goods cannot be cancelled; record a purchase return instead.');
            }
            if (! Money::isZero($purchase->paid_amount)) {
                throw new BusinessRuleException('Record a supplier refund for the amount paid before cancelling.');
            }

            $purchase->forceFill(['status' => PurchaseStatus::Cancelled])->save();

            return $purchase;
        });
    }

    /**
     * Return received goods to the supplier.
     *
     * @param  array{return_date: string, reason?: ?string,
     *     items: list<array{purchase_item_id: int, quantity: string}>,
     *     refund?: ?array{amount: string, method: string, reference?: ?string}}  $data
     */
    public function createReturn(Purchase $purchase, array $data, User $user): PurchaseReturn
    {
        return DB::transaction(function () use ($purchase, $data, $user) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $items = $purchase->items()->with('product')->lockForUpdate()->get()->keyBy('id');

            $return = new PurchaseReturn;
            $return->forceFill([
                'branch_id' => $purchase->branch_id,
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'reference_no' => $this->numbers->next('purchase_return', 'purchase_return_prefix'),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'created_by' => $user->id,
            ])->save();

            $subtotal = $taxTotal = $grandTotal = '0';

            foreach ($this->mergeReturnLines($data['items'], 'purchase_item_id') as $itemId => $quantity) {
                /** @var PurchaseItem|null $item */
                $item = $items->get($itemId) ?? throw new BusinessRuleException('Returned item does not belong to this purchase.');

                if (Money::cmp($quantity, $item->returnableQuantity()) > 0) {
                    throw new BusinessRuleException("Cannot return more than the {$item->returnableQuantity()} received of {$item->product->name}.");
                }

                [$lineTotal, $lineTax] = $this->returnShare($item, $quantity, 'purchase_return_items', 'purchase_item_id');

                $return->items()->create([
                    'purchase_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $quantity,
                    'unit_cost' => $item->unit_cost,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $item->forceFill(['returned_quantity' => Money::add($item->returned_quantity, $quantity)])->save();

                $this->inventory->move(
                    $purchase->branch_id, $item->product_id, $item->product_variant_id,
                    Money::mul($quantity, '-1'), StockMovementType::PurchaseReturn, $return,
                    $this->netUnitCost($item), $return->reference_no, allowNegative: false,
                );

                $subtotal = Money::add($subtotal, Money::sub($lineTotal, $lineTax));
                $taxTotal = Money::add($taxTotal, $lineTax);
                $grandTotal = Money::add($grandTotal, $lineTotal);
            }

            $return->forceFill([
                'subtotal' => Money::round($subtotal),
                'tax_total' => Money::round($taxTotal),
                'grand_total' => Money::round($grandTotal),
            ])->save();

            $purchase->forceFill(['returned_amount' => Money::round(Money::add($purchase->returned_amount, $grandTotal))]);
            $this->payments->refreshStatus($purchase);

            if (! empty($data['refund']['amount']) && Money::cmp($data['refund']['amount'], '0') > 0) {
                $this->payments->record($purchase, $data['refund'] + ['paid_on' => $data['return_date']], $user, refund: true);
            }

            return $return;
        });
    }

    private function syncItems(Purchase $purchase, array $lines): void
    {
        $inclusive = (bool) $purchase->prices_include_tax;
        $products = Product::with('tax')->whereIn('id', array_column($lines, 'product_id'))->get()->keyBy('id');
        $subtotal = $discountTotal = $taxTotal = $linesTotal = '0';

        foreach ($lines as $line) {
            $product = $products->get($line['product_id']) ?? throw new BusinessRuleException('Invalid product on purchase.');
            $variantId = $line['product_variant_id'] ?? null;

            if ($variantId !== null && ! $product->variants()->whereKey($variantId)->exists()) {
                throw new BusinessRuleException("Invalid variant selected for {$product->name}.");
            }
            if ($variantId === null && $product->has_variants) {
                throw new BusinessRuleException("Select a variant for {$product->name}.");
            }

            $rate = $product->tax?->rate ?? '0';
            $amounts = LineCalculator::calculate($line['quantity'], $line['unit_cost'], $line['discount'] ?? '0', $rate, $inclusive);

            $purchase->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'quantity' => $line['quantity'],
                'unit_cost' => Money::round($line['unit_cost']),
                'discount' => $amounts['discount'],
                'tax_rate' => $rate,
                'tax_amount' => $amounts['tax'],
                'line_total' => $amounts['total'],
            ]);

            $subtotal = Money::add($subtotal, $amounts['gross']);
            $discountTotal = Money::add($discountTotal, $amounts['discount']);
            $taxTotal = Money::add($taxTotal, $amounts['tax']);
            $linesTotal = Money::add($linesTotal, $amounts['total']);
        }

        $shipping = Money::round($purchase->shipping_cost ?? '0');

        $purchase->forceFill([
            'subtotal' => Money::round($subtotal),
            'discount_total' => Money::round($discountTotal),
            'tax_total' => Money::round($taxTotal),
            'shipping_cost' => $shipping,
            'grand_total' => Money::round(Money::add($linesTotal, $shipping)),
        ]);
        $this->payments->refreshStatus($purchase);
    }

    /** Cost per unit excluding tax, after line discount: the inventory valuation cost. */
    private function netUnitCost(PurchaseItem $item): string
    {
        return Money::round(Money::div(Money::sub($item->line_total, $item->tax_amount), $item->quantity));
    }
}
