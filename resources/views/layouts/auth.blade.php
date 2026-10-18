<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'One-Contract™')</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/auth-onboarding.css', 'resources/js/app.js'])
    @vite(['resources/js/auth.js'])
</head>
<body class="auth-body auth-body--onboarding @yield('body_class')">
    <div class="auth-wrapper @yield('auth_wrapper_class')">
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
