<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Http\Requests\PaymentRequest;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController extends Controller implements HasMiddleware
{
    private const BALANCE_SQL = 'grand_total - returned_amount - paid_amount';

    public function __construct(private PaymentService $payments) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:payments.view', only: ['index', 'receivables', 'payables']),
        ];
    }

    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->visibleTo($request->user())
            ->with(['payable', 'customer:id,name', 'supplier:id,name', 'branch:id,name', 'creator:id,name'])
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('direction'), fn ($q) => $q->where('direction', $request->string('direction')))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->when($request->date('from'), fn ($q, $d) => $q->whereDate('paid_on', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->whereDate('paid_on', '<=', $d))
            ->latest('paid_on')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'methods' => collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]),
        ]);
    }

    public function receivables(Request $request): View
    {
        $base = fn () => Sale::query()
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->where('status', '!=', SaleStatus::Cancelled->value)
            ->whereRaw(self::BALANCE_SQL.' > 0');

        return view('payments.receivables', [
            'sales' => $base()->with('customer:id,name')
                ->selectRaw('sales.*, '.self::BALANCE_SQL.' as balance')
                ->oldest('sale_date')->paginate(25)->withQueryString(),
            'byCustomer' => $this->totalsBy($base(), 'customer_id', \App\Models\Customer::class),
            'total' => $base()->sum(DB::raw(self::BALANCE_SQL)),
        ]);
    }

    public function payables(Request $request): View
    {
        $base = fn () => Purchase::query()
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->whereNotIn('status', [PurchaseStatus::Cancelled->value, PurchaseStatus::Draft->value])
            ->whereRaw(self::BALANCE_SQL.' > 0');

        return view('payments.payables', [
            'purchases' => $base()->with('supplier:id,name')
                ->selectRaw('purchases.*, '.self::BALANCE_SQL.' as balance')
                ->oldest('purchase_date')->paginate(25)->withQueryString(),
            'bySupplier' => $this->totalsBy($base(), 'supplier_id', \App\Models\Supplier::class),
            'total' => $base()->sum(DB::raw(self::BALANCE_SQL)),
        ]);
    }

    public function storeForSale(PaymentRequest $request, Sale $sale): RedirectResponse
    {
        return $this->store($request, $sale);
    }

    public function storeForPurchase(PaymentRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->store($request, $purchase);
    }

    private function store(PaymentRequest $request, Sale|Purchase $payable): RedirectResponse
    {
        $refund = $request->boolean('refund');
        Gate::authorize($refund ? 'refund' : 'recordPayment', $payable);

        $payment = $this->payments->record($payable, $request->safe()->except('refund'), $request->user(), refund: $refund);

        return back()->with('success', ($refund ? 'Refund' : 'Payment')." {$payment->payment_no} recorded.");
    }

    /**
     * Outstanding totals per party, with party names resolved in one query.
     *
     * @return \Illuminate\Support\Collection<int, object{name: string, balance: string, documents: int}>
     */
    private function totalsBy(Builder $query, string $column, string $partyModel)
    {
        $rows = $query->selectRaw("{$column}, SUM(".self::BALANCE_SQL.') as balance, COUNT(*) as documents')
            ->groupBy($column)
            ->orderByDesc('balance')
            ->get();

        $names = $partyModel::withTrashed()->whereIn('id', $rows->pluck($column)->filter())->pluck('name', 'id');

        return $rows->map(fn ($row) => (object) [
            'name' => $names[$row->{$column}] ?? 'Walk-in',
            'balance' => $row->balance,
            'documents' => (int) $row->documents,
        ]);
    }
}
