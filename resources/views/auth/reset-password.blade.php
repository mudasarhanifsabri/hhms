<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
     <!-- Title Meta -->
     <meta charset="utf-8" />
     <base href="{{ asset('') }}">
     <title>{{ config('app.name', 'Holiday Homes Management System') }}</title>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta name="description" content="Set a new Holiday Homes Management System password." />
     <meta name="csrf-token" content="{{ csrf_token() }}">

     <meta http-equiv="X-UA-Compatible" content="IE=edge" />

     <!-- App favicon -->
     <link rel="shortcut icon" href="assets/images/favicon.ico">

     <!-- Vendor css (Require in all Page) -->
     <link href="assets/css/vendor.min.css" rel="stylesheet" type="text/css" />

     <!-- Icons css (Require in all Page) -->
     <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />

     <!-- App css (Require in all Page) -->
     <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />

     <!-- Theme Config js (Require in all Page) -->
     <script src="assets/js/config.min.js"></script>

</head>

<body class="authentication-bg">

     <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
          <div class="container">
               <div class="row justify-content-center">
                    <div class="col-xl-5">
                         <div class="card auth-card">
                              <div class="card-body px-3 py-5">
                                   <div class="mx-auto mb-4 text-center auth-logo">
                                        <a href="{{ route('login') }}" class="logo-dark">
                                             <img src="assets/images/logo-dark.png" height="32" alt="logo dark">
                                        </a>

                                        <a href="{{ route('login') }}" class="logo-light">
                                             <img src="assets/images/logo-light.png" height="28" alt="logo light">
                                        </a>
                                   </div>

                                   <h2 class="fw-bold text-uppercase text-center fs-18">Setup New Password</h2>
                                   <p class="text-muted text-center mt-1 mb-4">Enter your new password.</p>

                                   <div class="px-4">
                                        <form action="{{ route('password.store') }}" method="post" class="authentication-form">
                                            @csrf

                                        <!-- Password Reset Token -->
                                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                                             <div class="mb-3">
                                                  <label class="form-label" for="example-email">Email</label>
                                                  <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}"
                                                  class="form-control bg-light bg-opacity-50 border-light py-2"
                                                  placeholder="Enter your email" readonly>                                                  <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                                </div>

                                             <div class="mb-3">
                                                <label class="form-label" for="password">Password</label>
                                                <input type="password" id="example-password" name="password" class="form-control bg-light bg-opacity-50 border-light py-2" placeholder="Enter your password">
                                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                           </div>

                                           <div class="mb-3">

                                            <label class="form-label" for="password_confirmation">Confirm Password</label>
                                            <input type="password" id="password_confirmation"  name="password_confirmation" required autocomplete="new-password" class="form-control bg-light bg-opacity-50 border-light py-2" placeholder="Enter your password">
                                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                                            </div>
                                             <div class="mb-1 text-center d-grid">
                                                  <button class="btn btn-danger py-2 fw-medium" type="submit">Reset Password</button>
                                             </div>
                                        </form>
                                   </div> <!-- end col -->
                              </div> <!-- end card-body -->
                         </div> <!-- end card -->
                         <p class="mb-0 text-center text-white">Back to <a href="{{ route('login') }}" class="text-reset text-unline-dashed fw-bold ms-1">Sign In</a></p>
                    </div> <!-- end col -->
               </div> <!-- end row -->
          </div>
     </div>

     <!-- Vendor Javascript (Require in all Page) -->
     <script src="assets/js/vendor.js"></script>

     <!-- App Javascript (Require in all Page) -->
     <script src="assets/js/app.js"></script>


</body>

</html>
