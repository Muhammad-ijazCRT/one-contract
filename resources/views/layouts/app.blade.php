<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="@yield('meta_description', 'One-Contract™ — Smart USDT investment. Earn 1% daily. Withdraw every 7 days.')">
    <title>@yield('title', 'One-Contract™ — smart participation. Daily Rewards.')</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700|inter:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-navy text-white font-sans antialiased">
    @unless(request()->routeIs('home'))
        @include('partials.header')
    @endunless

    @yield('content')

    @unless(request()->routeIs('home'))
        @include('partials.footer')
    @endunless
    @stack('scripts')
</body>
</html>
