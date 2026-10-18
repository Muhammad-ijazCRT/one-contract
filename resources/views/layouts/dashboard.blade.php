<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard — One-Contract™')</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    @vite(['resources/css/app.css', 'resources/css/dashboard.css', 'resources/css/dashboard-home-mock.css', 'resources/css/azubogu.css', 'resources/css/prediction.css', 'resources/css/select-currency.css', 'resources/css/convert-mock.css', 'resources/js/dashboard.js', 'resources/js/azubogu.js', 'resources/js/prediction.js'])
    @stack('styles')
</head>
<body class="dash-body @yield('body_class')">
    <div class="dash-layout dash-layout--user sidebar-collapsed dash-layout--no-sidebar @yield('layout_class')" id="dash-layout">
        @include('user.partials.topbar')
        @include('user.partials.sidebar')

        <div class="dash-main">
            <div class="dash-content">
                @include('user.partials.alerts')
                @unless (View::hasSection('hide_page_heading'))
                    <header class="oc-page-head">
                        <h1>@yield('page_title', 'Dashboard')</h1>
                        @hasSection('page_subtitle')
                            <p>@yield('page_subtitle')</p>
                        @else
                            <p>Welcome, {{ auth()->user()->name }}</p>
                        @endif
                    </header>
                @endunless
                @yield('content')
            </div>
        </div>
    </div>
    @include('user.partials.bottom-dock')
    @include('user.partials.currency-picker-modal')
    <div class="dash-overlay" id="dash-overlay"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
