<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#03294f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Pattern Owner">
    <script>try{if(localStorage.getItem('owner-locale')==='ar'){document.documentElement.lang='ar';document.documentElement.dir='rtl'}}catch(e){}</script>
    <title>Pattern Owner App</title>
    <link rel="manifest" href="{{ asset('owner-manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/pwa-icon-192.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owner-pwa.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owner-pwa-rtl.css') }}">
</head>
<body>
    @yield('content')
    <script>window.ownerApp={sw:"{{ asset('owner-sw.js') }}"};</script>
    <script src="{{ asset('assets/js/owner-pwa.js') }}"></script>
</body>
</html>
