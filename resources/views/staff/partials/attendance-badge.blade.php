{{-- $status: App\Enums\AttendanceStatus. Icon + label, never colour alone. --}}
@php
    [$class, $icon] = match ($status->value) {
        'present' => ['fw-status is-good', '✓'],
        'half_day' => ['fw-status is-warning', '◐'],
        'absent' => ['fw-status is-critical', '✕'],
        default => ['fw-chip', '–'],
    };
@endphp
<span class="{{ $class }}"><span aria-hidden="true">{{ $icon }}</span> {{ $status->label() }}</span>
