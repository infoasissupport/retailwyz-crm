{{-- $status: App\Enums\DeliveryStatus. Icon + label, never colour alone. --}}
@php
    [$class, $icon] = match ($status->value) {
        'pending' => ['fw-del-badge is-pending', '◷'],
        'out_for_delivery' => ['fw-del-badge is-transit', '➜'],
        'delivered' => ['fw-del-badge is-delivered', '✓'],
        'failed' => ['fw-del-badge is-failed', '!'],
        default => ['fw-del-badge is-cancelled', '✕'],
    };
@endphp
<span class="{{ $class }}"><span aria-hidden="true">{{ $icon }}</span> {{ $status->label() }}</span>
