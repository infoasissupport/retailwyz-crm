<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Sale;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:customers.view', only: ['index', 'show']),
            new Middleware('can:customers.manage', except: ['index', 'show']),
        ];
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $customers = Customer::query()
            ->search($request->string('q')->trim()->toString())
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            // Outstanding balance on sales the user may see, in the same query (no N+1).
            ->when($user->can('sales.view'), fn ($q) => $q->addSelect(['balance_due' => Sale::query()
                ->visibleTo($user)
                ->whereColumn('sales.customer_id', 'customers.id')
                ->where('status', '!=', SaleStatus::Cancelled->value)
                ->selectRaw('COALESCE(SUM(grand_total - returned_amount - paid_amount), 0)'),
            ]))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create', ['customer' => new Customer(['is_active' => true])]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = DB::transaction(function () use ($request) {
            $data = $this->data($request);
            $generate = empty($data['code']);

            // Placeholder keeps the NOT NULL unique column valid until the id-based code is known.
            $customer = Customer::create(['code' => $generate ? 'TMP-'.uniqid() : $data['code']] + $data);
            if ($generate) {
                $customer->update(['code' => 'CUS-'.str_pad((string) $customer->id, 5, '0', STR_PAD_LEFT)]);
            }

            return $customer;
        });

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Request $request, Customer $customer): View
    {
        $sales = $customer->sales()
            ->visibleTo($request->user())
            ->with('branch:id,name')
            ->latest('sale_date')
            ->latest('id')
            ->limit(20)
            ->get();

        $outstanding = $customer->sales()
            ->visibleTo($request->user())
            ->where('status', '!=', SaleStatus::Cancelled->value)
            ->selectRaw('COALESCE(SUM(grand_total - returned_amount - paid_amount), 0) as balance')
            ->value('balance');

        return view('customers.show', [
            'customer' => $customer,
            'sales' => $sales,
            'outstanding' => Money::round($outstanding ?? '0'),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $this->data($request);
        if (empty($data['code'])) {
            unset($data['code']);
        }
        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    /** Soft delete: past sales keep their customer reference. */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    private function data(CustomerRequest $request): array
    {
        $data = $request->validated();
        $data['opening_balance'] ??= '0';

        return $data;
    }
}
