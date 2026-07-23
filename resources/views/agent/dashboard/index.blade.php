@extends('layouts.portal', [
    'portalTitle' => 'Agent Portal',
    'portalEyebrow' => 'Agent',
    'portalHeading' => 'My Bookings & Commission',
])

@section('content')
<div class="portal-stat-grid">
    <div class="portal-card portal-stat"><div><span>Total Bookings</span><strong>{{ $bookings->count() }}</strong></div><i class="ri-calendar-check-line fs-28 text-primary"></i></div>
    <div class="portal-card portal-stat"><div><span>Paid</span><strong>{{ $paidBookings->count() }}</strong></div><i class="ri-bill-line fs-28 text-success"></i></div>
    <div class="portal-card portal-stat"><div><span>Commission %</span><strong>{{ number_format($commissionPercent, 2) }}</strong></div><i class="ri-percent-line fs-28 text-info"></i></div>
    <div class="portal-card portal-stat"><div><span>Estimated</span><strong>AED {{ number_format($estimatedCommission, 2) }}</strong></div><i class="ri-money-dirham-circle-line fs-28 text-warning"></i></div>
</div>

<section class="portal-card" id="bookings">
    <h4>Assigned Bookings</h4>
    <div class="portal-list">
        @forelse($bookings as $booking)
            <div class="portal-list-item">
                <div>
                    <strong>{{ $booking->booking_reference }} - {{ $booking->guest_name }}</strong>
                    <p>{{ $booking->property?->name ?? 'Unit' }} | {{ $booking->check_in?->format('d M Y') }} to {{ $booking->check_out?->format('d M Y') }}</p>
                </div>
                <span class="badge {{ $booking->workflow_status_class }} text-white">{{ $booking->workflow_status_label }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">No bookings assigned.</p>
        @endforelse
    </div>
</section>
@endsection
