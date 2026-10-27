<?php

namespace App\Http\Controllers;

use App\Enums\FollowUpType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Requests\LeadRequest;
use App\Models\Category;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeadController extends Controller
{
    private const PERIODS = [7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 90 Days', 365 => 'Last 12 Months'];

    private const PER_PAGE = [10, 25, 50, 100];

    public function __construct(private LeadService $leads) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Lead::class);
        $user = $request->user();
        $period = array_key_exists($request->integer('period'), self::PERIODS) ? $request->integer('period') : null;
        $perPage = in_array($request->integer('per_page'), self::PER_PAGE, true) ? $request->integer('per_page') : self::PER_PAGE[0];

        $leads = Lead::query()
            ->visibleTo($user)
            ->with(['assignee:id,name', 'category:id,name', 'nextFollowUp'])
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->search($request->string('q')->trim()->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->when($request->integer('assigned_to'), fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $user->id))
            ->when($period, fn ($q, $days) => $q->where('created_at', '>=', now()->subDays($days - 1)->startOfDay()))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $statusCounts = Lead::query()->visibleTo($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('leads.index', [
            'leads' => $leads,
            'stats' => $this->leadStats($user, $statusCounts),
            'staff' => $this->staffOptions($user),
            'periods' => self::PERIODS,
            'perPageOptions' => self::PER_PAGE,
        ] + $this->enumOptions());
    }

    /**
     * Summary tiles: current count per status (plus a total), the change in
     * leads created over the last 30 days vs the 30 before, and a 60-day trend.
     */
    private function leadStats(User $user, $statusCounts): array
    {
        $today = now()->startOfDay();
        $windowStart = $today->copy()->subDays(29);
        $daily = Lead::query()->visibleTo($user)
            ->where('created_at', '>=', $today->copy()->subDays(59))
            ->selectRaw('status, DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('status', 'day')
            ->toBase()
            ->get();

        $tile = function (?string $status, string $label, string $tone, string $icon, int $count) use ($daily, $today, $windowStart) {
            $rows = $status ? $daily->where('status', $status) : $daily;
            $current = $rows->filter(fn ($r) => $r->day >= $windowStart->toDateString())->sum('total');
            $previous = $rows->sum('total') - $current;
            // Ten 6-day buckets, oldest first.
            $spark = array_fill(0, 10, 0);
            foreach ($rows as $r) {
                $spark[9 - intdiv((int) Carbon::parse($r->day)->diffInDays($today), 6)] += $r->total;
            }

            return [
                'label' => $label,
                'tone' => $tone,
                'icon' => $icon,
                'count' => $count,
                'delta' => $previous ? round(($current - $previous) / $previous * 100) : null,
                'spark' => $spark,
                'status' => $status,
            ];
        };

        // Statuses that get their own tile, in display order.
        $meta = [
            'new' => ['blue', 'bi-file-earmark-text-fill'],
            'won' => ['red', 'bi-trophy-fill'],
            'lost' => ['slate', 'bi-x-circle-fill'],
        ];

        return [
            $tile(null, 'Total Leads', 'rose', 'bi-people', (int) $statusCounts->sum()),
            ...array_map(function (string $value) use ($tile, $meta, $statusCounts) {
                [$tone, $icon] = $meta[$value];

                return $tile($value, LeadStatus::from($value)->label(), $tone, $icon, (int) ($statusCounts[$value] ?? 0));
            }, array_keys($meta)),
        ];
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Lead::class);

        return view('leads.create', [
            'lead' => new Lead(['source' => LeadSource::WalkIn, 'status' => LeadStatus::New, 'assigned_to' => $request->user()->id]),
        ] + $this->formOptions($request->user()));
    }

    public function store(LeadRequest $request): RedirectResponse
    {
        Gate::authorize('create', Lead::class);
        $data = $request->validated();
        $data['branch_id'] = $this->resolveBranchId($request);
        $this->ensureAssignableUser($request->user(), $data['assigned_to'] ?? null, $data['branch_id']);

        $lead = $this->leads->create($data, $request->user());

        return redirect()->route('leads.show', $lead)->with('success', 'Lead created.');
    }

    public function show(Lead $lead): View
    {
        Gate::authorize('view', $lead);

        $lead->load([
            'assignee:id,name', 'category:id,name', 'customer:id,name,code', 'creator:id,name',
            'followUps' => fn ($q) => $q->with(['assignee:id,name', 'completer:id,name'])->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->orderBy('scheduled_at'),
        ]);

        return view('leads.show', [
            'lead' => $lead,
            'staff' => $this->staffOptions(request()->user()),
        ] + $this->enumOptions());
    }

    public function edit(Request $request, Lead $lead): View
    {
        Gate::authorize('update', $lead);

        return view('leads.edit', ['lead' => $lead] + $this->formOptions($request->user()));
    }

    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('update', $lead);
        $data = $request->validated();
        $this->ensureAssignableUser($request->user(), $data['assigned_to'] ?? null, $lead->branch_id);

        $this->leads->update($lead, $data);

        return redirect()->route('leads.show', $lead)->with('success', 'Lead updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        Gate::authorize('delete', $lead);
        $lead->delete();

        return redirect()->route('leads.index')->with('success', 'Lead deleted.');
    }

    public function convert(Lead $lead): RedirectResponse
    {
        Gate::authorize('convert', $lead);
        $customer = $this->leads->convert($lead);

        return redirect()->route('leads.show', $lead)->with('success', "Converted to customer {$customer->name} ({$customer->code}).");
    }

    /** Leads can only be assigned to active staff of the lead's branch. */
    private function ensureAssignableUser(User $user, ?int $assigneeId, int $branchId): void
    {
        if ($assigneeId === null) {
            return;
        }

        $assignee = User::find($assigneeId);
        if (! $assignee || ($assignee->branch_id !== $branchId && ! $assignee->canAccessAllBranches())) {
            throw ValidationException::withMessages(['assigned_to' => 'Choose a staff member from this lead’s branch.']);
        }
    }

    private function staffOptions(User $user)
    {
        return User::query()
            ->where('is_active', true)
            ->when(! $user->canAccessAllBranches(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function enumOptions(): array
    {
        return [
            'statuses' => collect(LeadStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
            'sources' => collect(LeadSource::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
            'followUpTypes' => collect(FollowUpType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]),
        ];
    }

    private function formOptions(User $user): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->pluck('name', 'id'),
            'staff' => $this->staffOptions($user),
        ] + $this->enumOptions();
    }
}
