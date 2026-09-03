@extends('layouts.tenant-pwa')
@section('content')
@include('tenant.partials.request-style')
<main class="guest-workspace" style="max-width:540px">
    <section class="guest-panel">
        <h1>Welcome to your Guest App</h1>
        <p>Manage your stay and submit maintenance requests to our team.</p>
        <a class="guest-button" href="{{ route('tenant.maintenance.index', ['unit' => $unitId]) }}">Sign in / Open My Requests</a>
    </section>
    <section class="guest-panel">
        <h2>First visit? Activate your account</h2>
        <p>Use the email and reference from your booking. We’ll email a secure link to set your password. Then sign in and complete your profile.</p>
        @if(session('status'))<div class="guest-alert" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="guest-error" role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('guest.access.activate', $unitId) }}">@csrf
            <label for="email">Booking email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255">
            <label for="booking_reference">Booking reference</label><input id="booking_reference" name="booking_reference" value="{{ old('booking_reference') }}" required maxlength="100">
            <button class="guest-button">Email My Password Setup Link</button>
        </form>
        <p class="guest-muted">No booking or different email? Contact management. Scanning this code does not grant access to another guest’s booking.</p>
    </section>
</main>
@endsection
