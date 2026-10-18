<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — One-Contract™')</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/dashboard.css', 'resources/js/dashboard.js'])
    @stack('styles')
</head>
<body class="dash-body dash-body-admin">
    <div class="dash-layout dash-layout-admin sidebar-collapsed" id="dash-layout">
        <script>if (window.innerWidth >= 1024) { document.getElementById('dash-layout').classList.remove('sidebar-collapsed'); }</script>
        @include('admin.partials.sidebar', ['treasury' => $treasury ?? null])

        <div class="dash-main">
            @include('admin.partials.topbar', ['notification_count' => $notification_count ?? 0])

            <div class="dash-content">
                @include('user.partials.alerts')
                @yield('content')
            </div>
        </div>
    </div>
    <div class="dash-overlay" id="dash-overlay"></div>
    @stack('scripts')
</body>
</html>
