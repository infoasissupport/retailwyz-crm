{{-- $status: App\Enums\IncentiveStatus --}}
@php
    [$class, $icon] = match ($status->value) {
        'paid' => ['fw-status is-good', '✓'],
        'approved' => ['fw-status is-good', '●'],
        'pending' => ['fw-status is-warning', '◷'],
        default => ['fw-status is-critical', '✕'],
    };
@endphp
<span class="{{ $class }}"><span aria-hidden="true">{{ $icon }}</span> {{ $status->label() }}</span>
