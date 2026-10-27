@extends('layouts.app')

@section('title', 'Search')
@section('subtitle', $q ? 'Results for “'.$q.'”' : 'Search leads, orders, customers, products and deliveries.')

@section('content')
    @if (mb_strlen($q) < 2)
        <div class="fw-card p-5 text-center text-secondary">Type at least two characters in the search box above.</div>
    @elseif (empty($results))
        <div class="fw-card p-5 text-center text-secondary">Nothing matches “{{ $q }}”.</div>
    @else
        <div class="fw-grid gap-3">
            @foreach ($results as $group => $items)
                <div class="fw-span-lg-6">
                    <section class="fw-card fw-panel h-100">
                        <h2 class="fw-panel-title">{{ $group }} <span class="text-secondary fw-normal small">{{ $items->count() }}</span></h2>
                        <ul class="list-unstyled mb-0">
                            @foreach ($items as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="fw-search-hit">
                                        <span class="fw-search-hit-icon"><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i></span>
                                        <span class="min-w-0">
                                            <span class="d-block fw-semibold text-truncate">{{ $item['title'] }}</span>
                                            <span class="d-block small text-secondary text-truncate">{{ $item['meta'] }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                </div>
            @endforeach
        </div>
    @endif
@endsection
