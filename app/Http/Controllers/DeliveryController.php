<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Http\Requests\DeliveryRequest;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Employee;
use App\Models\Sale;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private DeliveryService $deliveries) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Delivery::class);
        $user = $request->user();
        $status = DeliveryStatus::tryFrom((string) $request->query('status'));

        $base = fn () => Delivery::query()
            ->visibleTo($user)
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id));

        $deliveries = $base()
            ->with(['sale:id,invoice_no'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->search($request->string('q')->trim()->toString())
            ->when($request->date('date'), fn ($q, $d) => $q->whereDate('scheduled_date', $d))
            // Open work first (soonest date), then finished ones (most recent first).
            ->orderByRaw("CASE WHEN status IN ('pending', 'out_for_delivery', 'failed') THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status IN ('pending', 'out_for_delivery', 'failed') THEN scheduled_date END ASC")
            ->latest('scheduled_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = $base()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('deliveries.index', [
            'deliveries' => $deliveries,
            'counts' => $counts,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Delivery::class);

        $delivery = new Delivery(['scheduled_date' => today()->addDay()]);
        $sale = null;

        // Prefill from an invoice when scheduling from a sale page.
        if ($saleId = $request->integer('sale_id')) {
            $sale = Sale::visibleTo($request->user())->with('customer')->findOrFail($saleId);
            $delivery->fill([
                'customer_name' => $sale->customer?->name,
                'phone' => $sale->customer?->phone,
                'address' => $sale->customer?->address,
            ]);
        }

        return view('deliveries.create', ['delivery' => $delivery, 'sale' => $sale] + $this->formOptions($request->user()));
    }

    public function store(DeliveryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Delivery::class);

        $branchId = $request->filled('sale_id')
            ? Sale::visibleTo($request->user())->findOrFail($request->integer('sale_id'))->branch_id
            : $this->resolveBranchId($request);

        $delivery = $this->deliveries->create($request->validated(), $branchId, $request->user());

        return redirect()->route('deliveries.show', $delivery)->with('success', "Delivery {$delivery->delivery_no} scheduled.");
    }

    public function show(Delivery $delivery): View
    {
        Gate::authorize('view', $delivery);
        $delivery->load(['sale:id,branch_id,invoice_no,grand_total', 'customer:id,name,code', 'driver:id,name,employee_code', 'creator:id,name']);

        return view('deliveries.show', compact('delivery'));
    }

    public function edit(Request $request, Delivery $delivery): View
    {
        Gate::authorize('update', $delivery);
        abort_unless($delivery->status->isEditable(), 403, 'This delivery can no longer be edited.');
        $delivery->load('sale:id,invoice_no');

        return view('deliveries.edit', ['delivery' => $delivery, 'sale' => $delivery->sale] + $this->formOptions($request->user()));
    }

    public function update(DeliveryRequest $request, Delivery $delivery): RedirectResponse
    {
        Gate::authorize('update', $delivery);
        $this->deliveries->update($delivery, $request->validated());

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery updated.');
    }

    public function start(Delivery $delivery): RedirectResponse
    {
        Gate::authorize('update', $delivery);
        $this->deliveries->start($delivery);

        return back()->with('success', "{$delivery->delivery_no} is out for delivery.");
    }

    public function complete(Request $request, Delivery $delivery): RedirectResponse
    {
        Gate::authorize('update', $delivery);
        $data = $request->validate([
            'received_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->deliveries->complete($delivery, $data['received_by'] ?? null, $data['notes'] ?? null);

        return back()->with('success', "{$delivery->delivery_no} delivered.");
    }

    public function fail(Request $request, Delivery $delivery): RedirectResponse
    {
        Gate::authorize('update', $delivery);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->deliveries->fail($delivery, $data['reason']);

        return back()->with('success', "{$delivery->delivery_no} marked as failed. Edit it to reschedule.");
    }

    public function cancel(Request $request, Delivery $delivery): RedirectResponse
    {
        Gate::authorize('update', $delivery);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->deliveries->cancel($delivery, $data['reason']);

        return back()->with('success', "{$delivery->delivery_no} cancelled.");
    }

    private function formOptions(User $user): array
    {
        return [
            'drivers' => Employee::query()->visibleTo($user)->active()->orderBy('name')
                ->get(['id', 'name', 'designation', 'phone'])
                ->mapWithKeys(fn ($e) => [$e->id => $e->name.($e->designation ? " ({$e->designation})" : '')]),
            // TODO(page design): swap for a search box once customer lists grow beyond a few hundred.
            'customers' => Customer::active()->orderBy('name')->limit(500)->get(['id', 'name', 'phone', 'address']),
        ];
    }
}
