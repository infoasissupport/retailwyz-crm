@extends('layouts.app', ['breadcrumbs' => ['Deliveries' => route('deliveries.index'), $delivery->delivery_no => route('deliveries.show', $delivery), 'Edit' => null]])

@section('title', ($delivery->status->value === 'failed' ? 'Reschedule ' : 'Edit ').$delivery->delivery_no)

@section('content')
    @if ($delivery->status->value === 'failed')
        <div class="alert alert-warning small" style="max-width: 960px">
            Last attempt failed: {{ $delivery->failure_reason }}. Saving puts it back to <strong>Pending</strong> on the new date.
        </div>
    @endif

    <form method="POST" action="{{ route('deliveries.update', $delivery) }}" style="max-width: 960px">
        @csrf
        @method('PUT')
        @include('deliveries.partials.form')

        <button type="submit" class="btn fw-btn-accent">Save</button>
        <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-link">Back</a>
    </form>

    @can('update', $delivery)
        <details class="mt-4" style="max-width: 960px">
            <summary class="small text-danger">Cancel this delivery</summary>
            <form method="POST" action="{{ route('deliveries.cancel', $delivery) }}" class="d-flex gap-2 mt-2">
                @csrf
                <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" required maxlength="255">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Cancel this delivery?">Cancel delivery</button>
            </form>
        </details>
    @endcan
@endsection
