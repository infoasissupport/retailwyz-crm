{{-- Shell for unauthenticated pages (login). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width: 420px">
        @include('layouts.alerts')
        @yield('content')
    </main>
</body>
</html>
