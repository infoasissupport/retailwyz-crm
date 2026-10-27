<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseReturnRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PurchaseReturnController extends Controller
{
    public function __construct(private PurchaseService $purchases) {}

    public function store(PurchaseReturnRequest $request, Purchase $purchase): RedirectResponse
    {
        Gate::authorize('return', $purchase);

        $return = $this->purchases->createReturn($purchase, $request->returnData(), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('success', "Purchase return {$return->reference_no} recorded.");
    }
}
