<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
<base href="{{ url('/') }}">
@php
    $isMaintainerApp = auth()->check() && auth()->user()->role === 'maintainer';
    $appLogoUrl = \App\Support\MediaStorage::url(config('hhms.logo_path'));
    $appFaviconUrl = \App\Support\MediaStorage::url(config('hhms.favicon_path'));
@endphp

    <!-- Title Meta -->
    <meta charset="utf-8" />
    <title>{{ config('app.name', 'Holiday Homes Management System') }}</title>
   <meta name="viewport" content="{{ $isMaintainerApp ? 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover' : 'width=device-width, initial-scale=1.0' }}">
    <meta name="description" content="Holiday homes management dashboard for properties, landlords, tenants, agents, and maintainers." />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="theme-color" content="#0b2f6b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="HHMS Maintainer">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ $appFaviconUrl ?: asset('assets/images/favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ $appLogoUrl ?: asset('assets/images/logo-sm.png') }}">

    <!-- Vendor css (Require in all Page) -->
    <link href="assets/css/vendor.min.css" rel="stylesheet" type="text/css" />

    <!-- Icons css (Require in all Page) -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />

    <!-- App css (Require in all Page) -->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/maintainer-pwa.css') }}" rel="stylesheet" type="text/css" />

    <!-- Theme Config js (Require in all Page) -->
    <script src="assets/js/config.min.js"></script>

    @stack('styles')


</head>

<body class="{{ $isMaintainerApp ? 'maintainer-pwa-shell' : '' }}">

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
                    @unless($isMaintainerApp || request()->is('*/create'))
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

     <!-- Vendor Javascript (Require in all Page) -->
     <script src="assets/js/vendor.js"></script>

     <!-- App Javascript (Require in all Page) -->
     <script src="assets/js/app.js"></script>
     <script src="{{ asset('assets/js/pwa-register.js') }}"></script>

     @stack('scripts')
     @yield('script')

</body>

</html>
