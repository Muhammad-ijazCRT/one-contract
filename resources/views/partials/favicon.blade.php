@php($faviconVersion = '3')
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}" sizes="any">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
<link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}?v={{ $faviconVersion }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $faviconVersion }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $faviconVersion }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $faviconVersion }}">
<meta name="theme-color" content="#0a0e14">
