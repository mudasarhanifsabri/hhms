<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
<base href="{{ url('/') }}">
@php
    $isMaintainerApp = auth()->check() && auth()->user()->role === 'maintainer';
    $isAdminPwa = auth()->check() && ! in_array(auth()->user()->role, ['maintainer', 'landlord', 'tenant'], true);
    $appLogoUrl = \App\Support\MediaStorage::url(config('hhms.logo_path'));
    $appFaviconUrl = \App\Support\MediaStorage::url(config('hhms.favicon_path'));
    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);

        return asset($path).'?v='.(is_file($absolutePath) ? filemtime($absolutePath) : '1');
    };
@endphp

    <!-- Title Meta -->
    <meta charset="utf-8" />
    <title>{{ config('app.name', 'Holiday Homes Management System') }}</title>
   <meta name="viewport" content="{{ $isMaintainerApp ? 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover' : 'width=device-width, initial-scale=1.0' }}">
    <meta name="description" content="Holiday homes management dashboard for properties, landlords, tenants, agents, and maintainers." />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="theme-color" content="{{ $isMaintainerApp ? '#0a4167' : '#0b2f6b' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Pattern Field">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ $appFaviconUrl ?: asset('assets/images/favicon.ico') }}">
    <link rel="manifest" href="{{ asset($isAdminPwa ? 'admin-manifest.webmanifest' : 'manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ $appLogoUrl ?: asset('assets/images/logo-sm.png') }}">

    <!-- Vendor css (Require in all Page) -->
    <link href="{{ $versionedAsset('assets/css/vendor.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Icons css (Require in all Page) -->
    <link href="{{ $versionedAsset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- App css (Require in all Page) -->
    <link href="{{ $versionedAsset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ $versionedAsset('assets/css/maintainer-pwa.css') }}" rel="stylesheet" type="text/css" />
    @if($isAdminPwa)<link href="{{ $versionedAsset('assets/css/admin-pwa.css') }}" rel="stylesheet" type="text/css" />@endif

    <style>
        /* Keep the desktop footer in document flow so long tables and pagination
           always finish above it. The vendor theme positions it absolutely. */
        body:not(.maintainer-pwa-shell) .page-content {
            display: flex;
            flex-direction: column;
            padding-bottom: 0;
        }
        body:not(.maintainer-pwa-shell) .page-content > .container-fluid {
            flex: 1 0 auto;
            width: 100%;
        }
        body:not(.maintainer-pwa-shell) .page-content > .footer {
            position: static;
            flex: 0 0 var(--bs-footer-height);
            width: 100%;
            min-height: var(--bs-footer-height);
            margin-top: 1.25rem;
        }
    </style>

    <!-- Theme Config js (Require in all Page) -->
    <script src="{{ $versionedAsset('assets/js/config.min.js') }}"></script>

    @stack('styles')


</head>

<body class="{{ $isMaintainerApp ? 'maintainer-pwa-shell' : ($isAdminPwa ? 'admin-pwa-shell' : '') }}">

     @if($isMaintainerApp)
     <div class="pwa-launch-splash" id="pwa-launch-splash" aria-hidden="true">
         <div class="pwa-splash-orb one"></div><div class="pwa-splash-orb two"></div>
         <div class="pwa-splash-logo"><img src="{{ $appLogoUrl ?: asset('assets/images/logo-sm.png') }}" alt="Pattern Vacation Homes"></div>
         <h1>Pattern Field</h1><p>Fast work. Clear results.</p>
         <span class="pwa-splash-loader"></span>
     </div>
     @endif

            <!-- ========== Topbar Start ========== -->

          @unless($isMaintainerApp)
              @include('layouts.topbar')
          @endunless

            <!-- ========== Topbar End ========== -->


          <!-- ========== App Menu Start ========== -->

          @unless($isMaintainerApp)
              @include('layouts.navigation')
          @endunless

          <!-- ========== App Menu End ========== -->

          <!-- ==================================================== -->
          <!-- Start right Content here -->
          <!-- ==================================================== -->

          <div class="page-content">

               <!-- Start Container Fluid -->

               <div class="container-fluid">

                    <!-- ========== Page Title Start ========== -->
                    @unless($isMaintainerApp || request()->is('*/create') || trim($__env->yieldContent('hide-page-title')) === 'true')
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="mb-0 fw-semibold">
                                    @php
                                        $segments = request()->segments();
                                    @endphp
                                    {{ ucfirst(end($segments) ?? 'Dashboard') }}
                                </h4>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('dashboard') }}">Dashboard</a>
                                    </li>

                                    @foreach(request()->segments() as $index => $segment)
                                        @php
                                            $url = url(implode('/', array_slice(request()->segments(), 0, $index + 1)));
                                        @endphp

                                        @if ($loop->last)
                                            <li class="breadcrumb-item active">{{ ucfirst($segment) }}</li>
                                        @else
                                            <li class="breadcrumb-item">
                                                <a href="{{ $url }}">{{ ucfirst($segment) }}</a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    </div>
                    @endunless
                    <!-- ========== Page Title End ========== -->

                    @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @elseif(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @elseif(session('warning'))
                    <div class="alert alert-warning">
                        <i class="ri-shield-check-line me-1"></i>{{ session('warning') }}
                    </div>
                @endif



                @if (isset($errors) && $errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
                    <!-- ========== Page Content Start ========== -->

                    @yield('content')

                    <!-- ========== Page Content End ========== -->


               </div>
               <!-- End Container Fluid -->

               <!-- ========== Footer Start ========== -->
               @include('layouts.footer')
               <!-- ========== Footer End ========== -->

          </div>
          <!-- ==================================================== -->
          <!-- End Page Content -->
          <!-- ==================================================== -->

     @unless($isMaintainerApp)
         </div>
         <!-- END Wrapper -->
     @endunless

     @if($isAdminPwa)
         @include('layouts.admin-mobile-nav')
     @endif

     <!-- Vendor Javascript (Require in all Page) -->
     <script src="{{ $versionedAsset('assets/js/vendor.min.js') }}"></script>

     <!-- App Javascript (Require in all Page) -->
     <script src="{{ $versionedAsset('assets/js/app.min.js') }}"></script>
     <script src="{{ $versionedAsset('assets/js/pwa-register.js') }}"></script>
     @if($isMaintainerApp)
     <script>
       (() => {
         const splash = document.getElementById('pwa-launch-splash');
         if (!splash) return;
         let seen = false;
         try { seen = sessionStorage.getItem('hhms-bright-splash') === '1'; } catch (_) {}
         if (seen) { splash.remove(); return; }
         try { sessionStorage.setItem('hhms-bright-splash', '1'); } catch (_) {}
         const close = () => { splash.classList.add('is-leaving'); setTimeout(() => splash.remove(), 360); };
         window.addEventListener('load', () => setTimeout(close, 900), { once: true });
         setTimeout(close, 2400);
       })();
     </script>
     @endif
     @if($isAdminPwa)<script src="{{ $versionedAsset('assets/js/admin-pwa.js') }}"></script>@endif

     @stack('scripts')
     @yield('script')

</body>

</html>
