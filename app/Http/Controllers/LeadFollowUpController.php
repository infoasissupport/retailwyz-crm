<?php

namespace App\Http\Controllers;

use App\Enums\FollowUpType;
use App\Enums\LeadStatus;
use App\Http\Requests\CompleteFollowUpRequest;
use App\Http\Requests\FollowUpRequest;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LeadFollowUpController extends Controller
{
    public function __construct(private LeadService $leads) {}

    /** Follow-ups grouped by due date: overdue, today, upcoming, or completed. */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Lead::class);
        $user = $request->user();
        $view = in_array($request->query('view'), ['overdue', 'today', 'upcoming', 'done'], true) ? $request->query('view') : 'due';

        $base = fn () => LeadFollowUp::query()
            ->visibleTo($user)
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $user->id));

        $followUps = $base()
            ->with(['lead:id,name,phone,status', 'assignee:id,name', 'completer:id,name'])
            ->when($view === 'overdue', fn ($q) => $q->where('status', LeadFollowUp::PENDING)->where('scheduled_at', '<', today()))
            ->when($view === 'today', fn ($q) => $q->where('status', LeadFollowUp::PENDING)->whereBetween('scheduled_at', [today(), today()->endOfDay()]))
            ->when($view === 'upcoming', fn ($q) => $q->where('status', LeadFollowUp::PENDING)->where('scheduled_at', '>', today()->endOfDay()))
            ->when($view === 'due', fn ($q) => $q->where('status', LeadFollowUp::PENDING)->where('scheduled_at', '<=', today()->endOfDay()))
            ->when($view === 'done', fn ($q) => $q->where('status', LeadFollowUp::DONE)->reorder()->latest('completed_at'))
            ->orderBy('scheduled_at')
            ->paginate(25)
            ->withQueryString();

        $pending = fn () => $base()->where('status', LeadFollowUp::PENDING);
        $counts = [
            'overdue' => $pending()->where('scheduled_at', '<', today())->count(),
            'today' => $pending()->whereBetween('scheduled_at', [today(), today()->endOfDay()])->count(),
            'upcoming' => $pending()->where('scheduled_at', '>', today()->endOfDay())->count(),
        ];

        return view('leads.follow-ups', [
            'followUps' => $followUps,
            'view' => $view,
            'counts' => $counts,
            'statuses' => collect(LeadStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
            'followUpTypes' => collect(FollowUpType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]),
        ]);
    }

    public function store(FollowUpRequest $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('update', $lead);

        $data = $request->validated();
        $data['assigned_to'] = ($data['assigned_to'] ?? null) ?: ($lead->assigned_to ?? $request->user()->id);

        $this->leads->scheduleFollowUp($lead, $data, $request->user());

        return back()->with('success', 'Follow-up scheduled.');
    }

    public function complete(CompleteFollowUpRequest $request, LeadFollowUp $followUp): RedirectResponse
    {
        Gate::authorize('update', $followUp);

        $this->leads->completeFollowUp($followUp, $request->validated(), $request->user());

        return back()->with('success', 'Follow-up completed.');
    }

    public function cancel(LeadFollowUp $followUp): RedirectResponse
    {
        Gate::authorize('update', $followUp);

        $this->leads->cancelFollowUp($followUp);

        return back()->with('success', 'Follow-up cancelled.');
    }
}
