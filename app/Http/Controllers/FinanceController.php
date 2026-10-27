<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:finance.view')];
    }

    public function index(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $base = fn () => $this->ledger($request)->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()]);

        $byAccount = $base()
            ->selectRaw('account, direction, SUM(amount) as total')
            ->groupBy('account', 'direction')
            ->get();

        $accounts = collect(['cash', 'bank'])->mapWithKeys(function ($account) use ($byAccount) {
            $in = $byAccount->where('account', $account)->firstWhere('direction', Payment::IN)?->total ?? '0';
            $out = $byAccount->where('account', $account)->firstWhere('direction', Payment::OUT)?->total ?? '0';

            return [$account => ['in' => Money::round($in), 'out' => Money::round($out), 'net' => Money::round(Money::sub($in, $out))]];
        });

        return view('finance.index', [
            'from' => $from,
            'to' => $to,
            'accounts' => $accounts,
            'byType' => $base()->selectRaw('type, direction, SUM(amount) as total, COUNT(*) as entries')
                ->groupBy('type', 'direction')->orderBy('type')->get(),
            'totalIn' => Money::round(Money::add($accounts['cash']['in'], $accounts['bank']['in'])),
            'totalOut' => Money::round(Money::add($accounts['cash']['out'], $accounts['bank']['out'])),
        ]);
    }

    public function transactions(Request $request): View
    {
        $transactions = $this->ledger($request)
            ->with(['branch:id,name', 'creator:id,name', 'source'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('account'), fn ($q) => $q->where('account', $request->string('account')))
            ->when($request->filled('direction'), fn ($q) => $q->where('direction', $request->string('direction')))
            ->when($request->date('from'), fn ($q, $d) => $q->whereDate('transaction_date', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->whereDate('transaction_date', '<=', $d))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('finance.transactions', [
            'transactions' => $transactions,
            'types' => [
                FinancialTransaction::TYPE_SALE_PAYMENT, FinancialTransaction::TYPE_SALE_REFUND,
                FinancialTransaction::TYPE_PURCHASE_PAYMENT, FinancialTransaction::TYPE_PURCHASE_REFUND,
                FinancialTransaction::TYPE_EXPENSE,
            ],
            'methods' => collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]),
        ]);
    }

    private function ledger(Request $request)
    {
        return FinancialTransaction::query()
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function period(Request $request): array
    {
        return [
            $request->date('from') ?? now()->startOfMonth(),
            $request->date('to') ?? now()->endOfMonth(),
        ];
    }
}
