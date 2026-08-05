<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <base href="{{ url('/') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b2f6b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="HHMS Guest">
    <title>{{ $title ?? 'Guest App' }}</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/tenant-pwa.css') }}" rel="stylesheet" type="text/css">
</head>
<body class="tenant-pwa-shell">
    @yield('content')
    <script src="{{ asset('assets/js/tenant-pwa.js') }}"></script>
</body>
</html>
