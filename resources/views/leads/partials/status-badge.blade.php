{{-- $status: App\Enums\LeadStatus --}}
@php
    [$class, $icon] = match ($status->value) {
        'won' => ['fw-status is-good', '✓'],
        'lost' => ['fw-status is-critical', '✕'],
        default => ['fw-chip', '●'],
    };
@endphp
<span class="{{ $class }}"><span aria-hidden="true" class="small">{{ $icon }}</span> {{ $status->label() }}</span>
