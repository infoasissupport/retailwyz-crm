@extends('layouts.app')

@section('title', 'All Leads')

@section('page-actions')
    @can('create', App\Models\Lead::class)
        <div class="btn-group fw-btn-split">
            <a href="{{ route('leads.create') }}" class="btn fw-btn-orange">
                <i class="bi bi-plus-circle" aria-hidden="true"></i> New Lead
            </a>
            <button type="button" class="btn fw-btn-orange dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">More lead actions</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('leads.follow-ups.index') }}"><i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Follow-ups</a></li>
                <li><a class="dropdown-item" href="{{ route('leads.index', ['mine' => 1]) }}"><i class="bi bi-person me-2" aria-hidden="true"></i>My leads</a></li>
            </ul>
        </div>
    @endcan
@endsection

@section('content')
    @php
        $sourceIcons = [
            'walk_in' => 'bi-shop',
            'phone' => 'bi-telephone',
            'whatsapp' => 'bi-whatsapp',
            'website' => 'bi-globe2',
            'social_media' => 'bi-instagram',
            'referral' => 'bi-share',
            'other' => 'bi-three-dots',
        ];
        $avatarTones = ['rose', 'amber', 'green', 'blue', 'violet', 'orange'];
        $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
        $tone = fn (?string $name) => $avatarTones[crc32((string) $name) % count($avatarTones)];
    @endphp


    {{-- Pipeline summary: each tile filters the list by its status --}}
    <div class="fw-lstats">
        @foreach ($stats as $stat)
            @php
                $active = request('status') === $stat['status'] || (! $stat['status'] && ! request('status'));
                $dir = $stat['delta'] === null ? null : ($stat['delta'] > 0 ? 'up' : ($stat['delta'] < 0 ? 'down' : 'flat'));
            @endphp
            <a href="{{ route('leads.index', array_filter(array_merge(request()->except('status', 'page'), ['status' => $stat['status']]))) }}"
               @class(['fw-lstat', 'tone-'.$stat['tone'], 'is-active' => $active])>
                <span class="fw-lstat-head">
                    <span class="fw-lstat-icon" aria-hidden="true"><i class="bi {{ $stat['icon'] }}"></i></span>
                    <span class="min-w-0">
                        <span class="fw-lstat-label">{{ $stat['label'] }}</span>
                        <span class="fw-lstat-value fw-num">{{ number_format($stat['count']) }}</span>
                    </span>
                </span>
                <span class="fw-lstat-foot">
                    <span>
                        @if ($dir)
                            <span class="fw-delta-chip is-{{ $dir }}">
                                <i class="bi {{ ['up' => 'bi-arrow-up', 'down' => 'bi-arrow-down', 'flat' => 'bi-arrow-right'][$dir] }}" aria-hidden="true"></i>
                                <span class="visually-hidden">{{ ['up' => 'Up', 'down' => 'Down', 'flat' => 'No change,'][$dir] }}</span>
                                {{ abs($stat['delta']) }}%
                            </span>
                        @endif
                        <span class="fw-lstat-caption">vs prev. 30 days</span>
                    </span>
                    @if (array_sum($stat['spark']) > 0)
                        <x-sparkline :values="$stat['spark']" class="fw-lstat-spark" />
                    @endif
                </span>
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" id="leads-filter" class="fw-card fw-lfilter">
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @if (request('mine'))<input type="hidden" name="mine" value="1">@endif
        <label class="fw-lfield is-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="visually-hidden">Search</span>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, phone, email or company...">
        </label>
        <label class="fw-lfield">
            <i class="bi bi-globe2" aria-hidden="true"></i>
            <span class="visually-hidden">Source</span>
            <select name="source" class="form-select">
                <option value="">All Sources</option>
                @foreach ($sources as $value => $label)
                    <option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="fw-lfield">
            <i class="bi bi-tag" aria-hidden="true"></i>
            <span class="visually-hidden">Status</span>
            <select name="status" class="form-select">
                <option value="">All Status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="fw-lfield">
            <i class="bi bi-person" aria-hidden="true"></i>
            <span class="visually-hidden">Assigned to</span>
            <select name="assigned_to" class="form-select">
                <option value="">All Users</option>
                @foreach ($staff as $id => $name)
                    <option value="{{ $id }}" @selected(request('assigned_to') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
        <label class="fw-lfield">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <span class="visually-hidden">Created</span>
            <select name="period" class="form-select">
                <option value="">All Time</option>
                @foreach ($periods as $days => $label)
                    <option value="{{ $days }}" @selected(request()->integer('period') === $days)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <a href="{{ route('leads.index') }}" class="btn fw-btn-soft">Reset</a>
        <button type="submit" class="btn btn-primary fw-btn-filter"><i class="bi bi-funnel" aria-hidden="true"></i> Filter</button>
    </form>

    {{-- Leads table --}}
    <div class="fw-card fw-ltable-card">
        <div class="table-responsive">
            <table class="table fw-ltable align-middle mb-0">
                <thead>
                    <tr>
                        <th class="fw-lcheck"><input type="checkbox" class="form-check-input" data-lead-check-all aria-label="Select all leads"></th>
                        <th>#</th>
                        <th>Lead Name</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Source</th>
                        <th>Expected Value</th>
                        <th>Status</th>
                        <th>Next Follow-up</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        @php $srcIcon = $sourceIcons[$lead->source->value] ?? 'bi-three-dots'; @endphp
                        <tr>
                            <td class="fw-lcheck"><input type="checkbox" class="form-check-input" data-lead-check value="{{ $lead->id }}" aria-label="Select {{ $lead->name }}"></td>
                            <td class="text-secondary fw-num">{{ $leads->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('leads.show', $lead) }}" class="fw-lname">
                                    <span class="fw-lavatar tone-{{ $tone($lead->name) }}" aria-hidden="true">{{ $initials($lead->name) }}</span>
                                    <span>{{ $lead->name }}</span>
                                </a>
                            </td>
                            <td>{{ $lead->company ?: '—' }}</td>
                            <td class="fw-lcontact">
                                @if ($lead->phone || $lead->email)
                                    @if ($lead->phone)<div>{{ $lead->phone }}</div>@endif
                                    @if ($lead->email)<div class="text-secondary">{{ $lead->email }}</div>@endif
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-lsource">
                                    <i class="bi {{ $srcIcon }} fw-src-{{ $lead->source->value }}" aria-hidden="true"></i>
                                    {{ $lead->source->label() }}
                                </span>
                            </td>
                            <td class="fw-num text-nowrap">{{ $lead->expected_value ? money($lead->expected_value) : '—' }}</td>
                            <td><span class="fw-lstatus is-{{ $lead->status->value }}">{{ $lead->status->label() }}</span></td>
                            <td class="text-nowrap">
                                @if ($next = $lead->nextFollowUp)
                                    <span @class(['fw-ldate', 'is-overdue' => $next->isOverdue()]) title="{{ $next->type->label() }}{{ $next->isOverdue() ? ' · overdue' : '' }}">
                                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                                        {{ $next->scheduled_at->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($lead->assignee)
                                    <span class="fw-lname is-plain">
                                        <span class="fw-lavatar is-sm tone-{{ $tone($lead->assignee->name) }}" aria-hidden="true">{{ $initials($lead->assignee->name) }}</span>
                                        <span>{{ $lead->assignee->name }}</span>
                                    </span>
                                @else
                                    <span class="text-secondary">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $lead->created_at?->format('d M Y') }}</td>
                            <td>
                                <div class="fw-lactions">
                                    <a href="{{ route('leads.show', $lead) }}" class="fw-icon-btn is-blue" title="View"><i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">View {{ $lead->name }}</span></a>
                                    @can('update', $lead)
                                        <a href="{{ route('leads.edit', $lead) }}" class="fw-icon-btn is-rose" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Edit {{ $lead->name }}</span></a>
                                    @endcan
                                    <div class="dropdown">
                                        <button type="button" class="fw-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="More">
                                            <i class="bi bi-three-dots-vertical" aria-hidden="true"></i><span class="visually-hidden">More actions for {{ $lead->name }}</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('leads.show', $lead) }}"><i class="bi bi-calendar-plus me-2" aria-hidden="true"></i>Follow-ups</a></li>
                                            @can('convert', $lead)
                                                @unless ($lead->customer_id || $lead->status->value === 'lost')
                                                    <li>
                                                        <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item" data-confirm="Convert this lead into a customer?"><i class="bi bi-person-check me-2" aria-hidden="true"></i>Convert to customer</button>
                                                        </form>
                                                    </li>
                                                @endunless
                                            @endcan
                                            @can('delete', $lead)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('leads.destroy', $lead) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" data-confirm="Delete this lead?"><i class="bi bi-trash me-2" aria-hidden="true"></i>Delete</button>
                                                    </form>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-secondary py-5">
                                No leads found.
                                @can('create', App\Models\Lead::class)<a href="{{ route('leads.create') }}">Add the first lead</a>.@endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="fw-lfoot">
            <div class="d-flex align-items-center gap-3">
                <label class="visually-hidden" for="leads-per-page">Rows per page</label>
                <select id="leads-per-page" class="form-select form-select-sm fw-lperpage" data-leads-per-page>
                    @foreach ($perPageOptions as $n)
                        <option value="{{ $n }}" @selected($leads->perPage() === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                <span class="text-secondary small">
                    @if ($leads->total())
                        Showing {{ $leads->firstItem() }}–{{ $leads->lastItem() }} of {{ number_format($leads->total()) }} leads
                    @else
                        No leads
                    @endif
                </span>
            </div>
            <div class="fw-lpager">{{ $leads->onEachSide(1)->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Rows per page: resubmit the filter form so the other filters are kept.
    document.querySelector('[data-leads-per-page]')?.addEventListener('change', (event) => {
        const form = document.getElementById('leads-filter');
        form.elements.per_page.value = event.target.value;
        form.submit();
    });

    const checkAll = document.querySelector('[data-lead-check-all]');
    const rowChecks = [...document.querySelectorAll('[data-lead-check]')];
    checkAll?.addEventListener('change', () => rowChecks.forEach((box) => { box.checked = checkAll.checked; }));
    rowChecks.forEach((box) => box.addEventListener('change', () => {
        const checked = rowChecks.filter((b) => b.checked).length;
        checkAll.checked = checked === rowChecks.length;
        checkAll.indeterminate = checked > 0 && checked < rowChecks.length;
    }));
</script>
@endpush
