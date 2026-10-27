<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CashRegisterController extends Controller implements HasMiddleware
{
    public function __construct(private FinanceService $finance) {}

    public static function middleware(): array
    {
        return [new Middleware('can:cash-register.manage', only: ['index', 'open'])];
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $current = $user->branch_id ? $this->finance->openRegisterFor($user, $user->branch_id) : null;

        return view('finance.cash-register', [
            'current' => $current,
            'expected' => $current ? $this->finance->expectedCash($current) : null,
            'registers' => CashRegister::query()
                ->visibleTo($user)
                ->with(['user:id,name', 'branch:id,name'])
                ->latest('opened_at')
                ->paginate(25),
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        Gate::authorize('open', CashRegister::class);

        $data = $request->validate([
            'opening_balance' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->finance->openRegister($request->user(), $this->resolveBranchId($request), $data['opening_balance'], $data['notes'] ?? null);

        return redirect()->route('finance.cash-register')->with('success', 'Cash register opened.');
    }

    public function close(Request $request, CashRegister $cashRegister): RedirectResponse
    {
        Gate::authorize('close', $cashRegister);

        $data = $request->validate([
            'closing_balance' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $register = $this->finance->closeRegister($cashRegister, $data['closing_balance'], $data['notes'] ?? null);

        return redirect()->route('finance.cash-register')
            ->with('success', "Register closed. Expected {$register->expected_balance}, counted {$register->closing_balance}.");
    }
}
