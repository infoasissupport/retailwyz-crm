<footer class="fw-footer">
    <span>&copy; {{ date('Y') }} {{ $appSettings->get('business_name', config('app.name')) }}. All rights reserved.</span>
    <span class="d-none d-sm-inline">
        @if (auth()->user()->branch){{ auth()->user()->branch->name }} · @endif
        Version {{ config('app.version') }}
    </span>
</footer>
