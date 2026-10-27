{{-- Status actions for one delivery. Expects $delivery. --}}
@can('update', $delivery)
    @switch($delivery->status->value)
        @case('pending')
            <form method="POST" action="{{ route('deliveries.start', $delivery) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm fw-del-action is-transit">Start Delivery</button>
            </form>
            @break

        @case('out_for_delivery')
            <details class="fw-complete d-inline-block text-start">
                <summary class="btn btn-sm fw-del-action is-delivered">Mark Delivered</summary>
                <form method="POST" action="{{ route('deliveries.complete', $delivery) }}" class="fw-del-pop">
                    @csrf
                    <label class="form-label small mb-1" for="rb-{{ $delivery->id }}">Received by</label>
                    <input type="text" name="received_by" id="rb-{{ $delivery->id }}" value="{{ $delivery->customer_name }}" class="form-control form-control-sm mb-2" maxlength="255">
                    <input type="text" name="notes" class="form-control form-control-sm mb-2" placeholder="Note (optional)" maxlength="1000">
                    <button type="submit" class="btn btn-sm btn-success">Confirm delivered</button>
                </form>
            </details>
            <details class="fw-complete d-inline-block text-start">
                <summary class="btn btn-sm btn-link text-danger">Failed</summary>
                <form method="POST" action="{{ route('deliveries.fail', $delivery) }}" class="fw-del-pop">
                    @csrf
                    <label class="form-label small mb-1" for="fr-{{ $delivery->id }}">What went wrong?</label>
                    <input type="text" name="reason" id="fr-{{ $delivery->id }}" class="form-control form-control-sm mb-2" required maxlength="255" placeholder="e.g. Customer not available">
                    <button type="submit" class="btn btn-sm btn-danger">Mark failed</button>
                </form>
            </details>
            @break

        @case('failed')
            <a href="{{ route('deliveries.edit', $delivery) }}" class="btn btn-sm fw-del-action is-pending">Reschedule</a>
            @break
    @endswitch
@endcan
