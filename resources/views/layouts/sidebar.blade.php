{{-- Permission-aware navigation built from config/navigation.php. Groups open when they hold the current page. --}}
@php
    $user = auth()->user();
    $visible = fn (array $item) => ! isset($item['can']) || $user->can($item['can']);
    $isActive = fn (array $item) => request()->routeIs(...(array) $item['active']);
@endphp

<nav class="fw-sidebar" aria-label="Main navigation">
    <a href="{{ route('dashboard') }}" class="fw-brand">
        <span class="fw-brand-mark" aria-hidden="true"><i class="bi bi-house-heart-fill"></i></span>
        <span class="min-w-0">
            <span class="fw-brand-name">{{ $appSettings->get('business_name', config('app.name')) }}</span>
            <span class="fw-brand-tag">Management System</span>
        </span>
    </a>

    <ul class="fw-nav list-unstyled">
        @foreach (config('navigation') as $index => $item)
            @if (isset($item['children']))
                @php
                    $children = array_filter($item['children'], $visible);
                    $groupActive = collect($children)->contains(fn ($child) => $isActive($child));
                    $groupId = 'nav-group-'.$index;
                @endphp
                @continue(empty($children))

                <li class="fw-nav-group">
                    {{-- Expanded by default; app.js re-collapses groups the user closed (never the current one). --}}
                    <button type="button" class="fw-nav-heading"
                            data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}"
                            aria-expanded="true" aria-controls="{{ $groupId }}"
                            data-fw-nav-group="{{ $item['label'] }}" @if ($groupActive) data-fw-nav-current @endif>
                        <i class="bi {{ $item['icon'] ?? 'bi-folder' }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                        <i class="bi bi-chevron-down fw-nav-caret" aria-hidden="true"></i>
                    </button>
                    <ul id="{{ $groupId }}" class="fw-nav-sub list-unstyled collapse show">
                        @foreach ($children as $child)
                            <li>
                                <a href="{{ route($child['route']) }}" @class(['fw-nav-link', 'is-active' => $isActive($child)])
                                   @if ($isActive($child)) aria-current="page" @endif>
                                    <i class="bi {{ $child['icon'] ?? 'bi-dot' }}" aria-hidden="true"></i>
                                    <span>{{ $child['label'] }}</span>
                                    <i class="bi bi-chevron-right fw-nav-arrow" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @elseif ($visible($item))
                <li>
                    <a href="{{ route($item['route']) }}" @class(['fw-nav-link fw-nav-top', 'is-active' => $isActive($item)])
                       @if ($isActive($item)) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] ?? 'bi-dot' }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endif
        @endforeach
    </ul>

    <div class="fw-sidebar-foot">
        <span class="fw-sidebar-foot-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($appSettings->get('business_name', config('app.name')), 0, 1)) }}</span>
        <span class="min-w-0">
            <span class="d-block fw-semibold text-truncate">{{ $appSettings->get('business_name', config('app.name')) }}</span>
            <span class="d-block small">v{{ config('app.version') }}</span>
        </span>
    </div>
</nav>
