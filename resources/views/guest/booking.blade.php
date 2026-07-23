@extends('layouts.portal', [
    'portalTitle' => 'Guest Booking Portal',
    'portalEyebrow' => 'Guest Portal',
    'portalHeading' => $booking->booking_reference,
])

@section('content')
<div class="portal-stat-grid">
    <div class="portal-card portal-stat"><div><span>Status</span><strong>{{ $booking->workflow_status_label }}</strong></div><i class="ri-calendar-check-line fs-28 text-primary"></i></div>
    <div class="portal-card portal-stat"><div><span>Unit</span><strong>{{ $booking->property?->name ?? '-' }}</strong></div><i class="ri-home-4-line fs-28 text-success"></i></div>
    <div class="portal-card portal-stat"><div><span>Check In</span><strong>{{ $booking->check_in?->format('d M') }}</strong></div><i class="ri-login-box-line fs-28 text-info"></i></div>
    <div class="portal-card portal-stat"><div><span>Check Out</span><strong>{{ $booking->check_out?->format('d M') }}</strong></div><i class="ri-logout-box-line fs-28 text-warning"></i></div>
</div>

<div class="portal-grid">
    <section class="portal-card">
        <h4>Stay Details</h4>
        <div class="portal-list">
            <div class="portal-list-item"><strong>Guest</strong><span>{{ $booking->guest_name }}</span></div>
            <div class="portal-list-item"><strong>Email</strong><span>{{ $booking->guest_email }}</span></div>
            <div class="portal-list-item"><strong>Phone</strong><span>{{ $booking->guest_phone }}</span></div>
            <div class="portal-list-item"><strong>Total</strong><span>AED {{ number_format((float) $booking->total_amount, 2) }}</span></div>
        </div>
    </section>
    <section class="portal-card">
        <h4>Documents</h4>
        <div class="portal-list">
            <a class="portal-list-item" href="{{ route('guest.booking.confirmation', $booking->booking_reference) }}"><strong>Booking Confirmation</strong><i class="ri-download-line"></i></a>
            <a class="portal-list-item" href="{{ route('guest.booking.invoice', $booking->booking_reference) }}"><strong>Invoice</strong><i class="ri-download-line"></i></a>
        </div>
    </section>
</div>
@endsection
