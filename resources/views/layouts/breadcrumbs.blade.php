{{-- $breadcrumbs: ['Label' => url|null, ...]; the last item is the current page. --}}
@if (! empty($breadcrumbs))
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb fw-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door" aria-hidden="true"></i><span class="visually-hidden">Dashboard</span></a></li>
            @foreach ($breadcrumbs as $label => $url)
                @if ($url && ! $loop->last)
                    <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
