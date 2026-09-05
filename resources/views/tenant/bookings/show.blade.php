@extends('layouts.tenant-pwa', ['title' => 'Booking Details'])

@section('content')
<div class="tenant-screen">
    @include('tenant.partials.header', ['title' => 'Booking Details'])
    <main class="tenant-content">
        <section class="tenant-booking-card">
            <div class="tenant-card-head">
                <strong>{{ $booking->booking_reference }}</strong>
                <span>{{ $booking->workflow_status_label }}</span>
            </div>
            <h2>{{ $booking->property?->building?->name ?? 'Property' }} - {{ $booking->property?->name ?? 'Unit' }}</h2>
            <p>{{ $booking->property?->community ?? 'Dubai' }}</p>
            <div class="tenant-unit-photo"></div>
            <div class="tenant-date-grid">
                <div><small>Check-in</small><b>{{ $booking->check_in?->format('d M Y') }}</b></div>
                <div><small>Check-out</small><b>{{ $booking->check_out?->format('d M Y') }}</b></div>
            </div>
            <div class="tenant-info-list">
                <div><span>Guest</span><strong>{{ $booking->guest_name }}</strong></div>
                <div><span>Security Deposit</span><strong>AED {{ number_format((float) $booking->security_deposit, 2) }}</strong></div>
                <div><span>Unit Type</span><strong>{{ (int) ($booking->property?->bedrooms ?? 0) === 0 ? 'Studio' : (int) $booking->property->bedrooms . ' BHK' }}</strong></div>
            </div>
        </section>

        <section class="tenant-section">
            <h3>Inspections</h3>
            @php
                $checkIn = $booking->inspections->firstWhere('type', 'check_in');
                $checkOut = $booking->inspections->firstWhere('type', 'check_out');
            @endphp
            <form action="{{ route('tenant.inspection.start', [$booking->id, 'check_in']) }}" method="POST">
                @csrf
                <button class="tenant-primary" type="submit">{{ $checkIn ? 'Open Check-In Inspection' : 'Start Check-In Inspection' }}</button>
            </form>
            <form action="{{ route('tenant.inspection.start', [$booking->id, 'check_out']) }}" method="POST">
                @csrf
                <button class="tenant-secondary" type="submit">{{ $checkOut ? 'Open Check-Out Inspection' : 'Start Check-Out Inspection' }}</button>
            </form>
        </section>

        <section class="tenant-section">
            <h3>Invoices & Payments</h3>
            @include('guest.partials.invoice-payment-status')
        </section>
    </main>
    @include('tenant.partials.bottom-nav')
</div>
@endsection
