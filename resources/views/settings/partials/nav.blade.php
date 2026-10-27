{{-- Tabs shared by the settings screens. --}}
<ul class="nav nav-tabs mb-3">
    @can('settings.manage')
        <li class="nav-item"><a href="{{ route('settings.general') }}" @class(['nav-link', 'active' => request()->routeIs('settings.general')])>General</a></li>
        <li class="nav-item"><a href="{{ route('settings.invoice') }}" @class(['nav-link', 'active' => request()->routeIs('settings.invoice')])>Invoice &amp; numbering</a></li>
        <li class="nav-item"><a href="{{ route('settings.taxes') }}" @class(['nav-link', 'active' => request()->routeIs('settings.taxes')])>Taxes</a></li>
    @endcan
    @can('branches.manage')
        <li class="nav-item"><a href="{{ route('settings.branches') }}" @class(['nav-link', 'active' => request()->routeIs('settings.branches')])>Branches</a></li>
    @endcan
    @can('catalog.manage')
        <li class="nav-item"><a href="{{ route('units.index') }}" class="nav-link">Units</a></li>
    @endcan
</ul>
