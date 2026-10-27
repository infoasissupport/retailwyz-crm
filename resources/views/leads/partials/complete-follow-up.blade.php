{{-- Inline "mark done" form for a pending follow-up. Expects $followUp, $statuses, $followUpTypes. --}}
<details class="fw-complete">
    <summary class="btn btn-sm btn-outline-success">Mark done</summary>
    <form method="POST" action="{{ route('leads.follow-ups.complete', $followUp) }}" class="mt-2 p-3 border rounded bg-light">
        @csrf
        <div class="mb-2">
            <label class="form-label small mb-1" for="outcome-{{ $followUp->id }}">Outcome</label>
            <textarea name="outcome" id="outcome-{{ $followUp->id }}" rows="2" class="form-control form-control-sm" required placeholder="What happened?"></textarea>
        </div>
        <div class="fw-grid gap-2">
            <div class="fw-span-sm-6">
                <label class="form-label small mb-1" for="lead-status-{{ $followUp->id }}">Move lead to</label>
                <select name="lead_status" id="lead-status-{{ $followUp->id }}" class="form-select form-select-sm">
                    <option value="">Keep current status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fw-span-sm-6">
                <label class="form-label small mb-1" for="lost-{{ $followUp->id }}">Lost reason</label>
                <input type="text" name="lost_reason" id="lost-{{ $followUp->id }}" class="form-control form-control-sm" maxlength="255" placeholder="If lost">
            </div>
            <div class="fw-span-sm-6">
                <label class="form-label small mb-1" for="next-{{ $followUp->id }}">Next follow-up</label>
                <input type="datetime-local" name="next_follow_up_at" id="next-{{ $followUp->id }}" class="form-control form-control-sm">
            </div>
            <div class="fw-span-sm-6">
                <label class="form-label small mb-1" for="next-type-{{ $followUp->id }}">Type</label>
                <select name="next_follow_up_type" id="next-type-{{ $followUp->id }}" class="form-select form-select-sm">
                    @foreach ($followUpTypes as $value => $label)
                        <option value="{{ $value }}" @selected($value === $followUp->type->value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-sm btn-success mt-3">Save</button>
    </form>
</details>
