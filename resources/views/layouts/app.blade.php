{{--
    Main application shell. Sidebar, header and footer are fixed; only <main> scrolls.
    Usage: @extends('layouts.app', ['breadcrumbs' => ['Products' => route('products.index'), 'Create' => null]])
           @section('title', 'Page title')            -> page heading
           @section('subtitle', 'Short description')   -> optional line under the heading
           @section('page-actions') ... @endsection    -> buttons beside the heading
           @section('header-tools') ... @endsection    -> controls in the top bar (e.g. a date range)
           @section('content') ... @endsection
           @push('scripts') for page JS.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ $appSettings->get('business_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="fw-body">
    <a href="#main-content" class="visually-hidden-focusable fw-skip-link">Skip to content</a>

    <div class="fw-shell">
        @include('layouts.sidebar')
        <div class="fw-sidebar-backdrop" data-fw-toggle="sidebar" aria-hidden="true"></div>

        <div class="fw-main">
            @include('layouts.header')

            <main id="main-content" class="fw-content" tabindex="-1">
                <div class="fw-page">
                    @hasSection('title')
                        <div class="fw-page-head">
                            <div class="min-w-0">
                                <h1 class="fw-page-title">@yield('title')</h1>
                           
                            </div>
                            @hasSection('page-actions')
                                <div class="fw-page-actions">@yield('page-actions')</div>
                            @endif
                        </div>
                    @endif

                    @include('layouts.alerts')

                    @yield('content')
                </div>
            </main>

            @include('layouts.footer')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
