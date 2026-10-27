{{-- Date range (+ branch for all-branch users) filter shared by report and finance pages. Expects $from, $to as Y-m-d strings or dates. --}}
@php
    $branchOptions = auth()->user()->canAccessAllBranches()
        ? \App\Models\Branch::orderBy('name')->pluck('name', 'id')
        : collect();
@endphp

<form method="GET" class="fw-grid gap-2">
    <div class="fw-span-md-3"><input type="date" name="from" value="{{ \Illuminate\Support\Carbon::parse($from)->toDateString() }}" class="form-control" aria-label="From"></div>
    <div class="fw-span-md-3"><input type="date" name="to" value="{{ \Illuminate\Support\Carbon::parse($to)->toDateString() }}" class="form-control" aria-label="To"></div>
    @if ($branchOptions->isNotEmpty())
        <div class="fw-span-md-3">
            <select name="branch_id" class="form-select" aria-label="Branch">
                <option value="">All branches</option>
                @foreach ($branchOptions as $id => $name)
                    <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="fw-span-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Apply</button></div>
</form>
