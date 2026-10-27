@php
    $user = auth()->user();
    $alertCount = collect($alerts)->sum('count');
    $initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
@endphp

<header class="fw-topbar">
    <button type="button" class="fw-icon-btn d-lg-none" data-fw-toggle="sidebar" aria-label="Open navigation">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <form method="GET" action="{{ route('search') }}" class="fw-topbar-search" role="search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <label for="global-search" class="visually-hidden">Search</label>
        <input type="search" id="global-search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}"
               placeholder="Search leads, orders, customers, products..." autocomplete="off">
    </form>

    <div class="fw-topbar-tools">
        @yield('header-tools')

        <div class="dropdown">
            <button type="button" class="fw-icon-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false"
                    aria-label="Notifications{{ $alertCount ? ", {$alertCount} need attention" : '' }}">
                <i class="bi bi-bell" aria-hidden="true"></i>
                @if ($alertCount)
                    <span class="fw-bell-count" aria-hidden="true">{{ $alertCount > 99 ? '99+' : $alertCount }}</span>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end fw-dropdown fw-alerts">
                <div class="fw-dropdown-head">Needs attention</div>
                @forelse ($alerts as $alert)
                    <a href="{{ $alert['url'] }}" class="dropdown-item fw-alert-item">
                        <span class="fw-alert-icon is-{{ $alert['tone'] }}"><i class="bi {{ $alert['icon'] }}" aria-hidden="true"></i></span>
                        <span class="flex-grow-1 text-wrap">{{ $alert['label'] }}</span>
                        <span class="fw-alert-count">{{ $alert['count'] }}</span>
                    </a>
                @empty
                    <p class="px-3 py-2 mb-0 small text-secondary"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> You're all caught up.</p>
                @endforelse
            </div>
        </div>

        <div class="dropdown">
            <button type="button" class="fw-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="fw-user-avatar" aria-hidden="true">{{ $initials }}</span>
                <span class="fw-user-text d-none d-md-flex">
                    <span class="fw-user-name">{{ $user->name }}</span>
                    <span class="fw-user-role">{{ $user->getRoleNames()->first() ?? 'User' }}@if ($user->branch) · {{ $user->branch->name }}@endif</span>
                </span>
                <i class="bi bi-chevron-down small d-none d-md-inline" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end fw-dropdown">
                <div class="fw-dropdown-head">
                    <div class="fw-semibold text-body">{{ $user->name }}</div>
                    <div class="small text-secondary text-truncate">{{ $user->email }}</div>
                </div>
                @can('settings.manage')
                    <a href="{{ route('settings.general') }}" class="dropdown-item"><i class="bi bi-gear me-2" aria-hidden="true"></i>Settings</a>
                @endcan
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Log out</button>
                </form>
            </div>
        </div>
    </div>
</header>
