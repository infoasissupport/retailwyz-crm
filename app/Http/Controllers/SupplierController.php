<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatus;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:suppliers.view', only: ['index', 'show']),
            new Middleware('can:suppliers.manage', except: ['index', 'show']),
        ];
    }

    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->search($request->string('q')->trim()->toString())
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.create', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $supplier = DB::transaction(function () use ($request) {
            $data = $this->data($request);
            $generate = empty($data['code']);

            // Placeholder keeps the NOT NULL unique column valid until the id-based code is known.
            $supplier = Supplier::create(['code' => $generate ? 'TMP-'.uniqid() : $data['code']] + $data);
            if ($generate) {
                $supplier->update(['code' => 'SUP-'.str_pad((string) $supplier->id, 5, '0', STR_PAD_LEFT)]);
            }

            return $supplier;
        });

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier created.');
    }

    public function show(Request $request, Supplier $supplier): View
    {
        $purchases = $supplier->purchases()
            ->visibleTo($request->user())
            ->with('branch:id,name')
            ->latest('purchase_date')
            ->latest('id')
            ->limit(20)
            ->get();

        $payable = $supplier->purchases()
            ->visibleTo($request->user())
            ->whereNotIn('status', [PurchaseStatus::Cancelled->value, PurchaseStatus::Draft->value])
            ->selectRaw('COALESCE(SUM(grand_total - returned_amount - paid_amount), 0) as balance')
            ->value('balance');

        return view('suppliers.show', [
            'supplier' => $supplier,
            'purchases' => $purchases,
            'payable' => Money::round($payable ?? '0'),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $data = $this->data($request);
        if (empty($data['code'])) {
            unset($data['code']);
        }
        $supplier->update($data);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    /** Soft delete: past purchases keep their supplier reference. */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    private function data(SupplierRequest $request): array
    {
        $data = $request->validated();
        $data['opening_balance'] ??= '0';

        return $data;
    }
}
