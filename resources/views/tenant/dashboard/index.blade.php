@extends('layouts.portal', [
    'portalTitle' => 'Guest / Tenant Portal',
    'portalEyebrow' => 'Guest / Tenant',
    'portalHeading' => 'My Stays',
])

@section('content')
<div class="portal-stat-grid">
    <div class="portal-card portal-stat"><div><span>Total Stays</span><strong>{{ $bookings->count() }}</strong></div><i class="ri-hotel-bed-line fs-28 text-primary"></i></div>
    <div class="portal-card portal-stat"><div><span>Active</span><strong>{{ $activeBookings->count() }}</strong></div><i class="ri-calendar-check-line fs-28 text-success"></i></div>
    <div class="portal-card portal-stat"><div><span>Paid</span><strong>{{ $bookings->where('invoice_status', 'paid')->count() }}</strong></div><i class="ri-bill-line fs-28 text-info"></i></div>
    <div class="portal-card portal-stat"><div><span>Documents</span><strong>{{ $bookings->whereNotNull('guest_document')->count() }}</strong></div><i class="ri-file-copy-2-line fs-28 text-warning"></i></div>
</div>

<div class="portal-grid">
    <section class="portal-card" id="stays">
        <h4>My Bookings</h4>
        <div class="portal-list">
            @forelse($bookings as $booking)
                <a class="portal-list-item text-decoration-none text-dark" href="{{ route('tenant.booking.show', $booking->id) }}">
                    <div>
                        <strong>{{ $booking->booking_reference }}</strong>
                        <p>{{ $booking->property?->name ?? 'Unit' }} | {{ $booking->check_in?->format('d M Y') }} to {{ $booking->check_out?->format('d M Y') }}</p>
                    </div>
                    <span class="badge {{ $booking->workflow_status_class }} text-white">{{ $booking->workflow_status_label }}</span>
                </a>
            @empty
                <p class="text-muted mb-0">No bookings found for this email.</p>
            @endforelse
        </div>
    </section>
    <section class="portal-card" id="documents">
        <h4>Documents</h4>
        <p class="text-muted">Invoices, confirmations, and inspection items will appear from your booking record.</p>
    </section>
</div>
@endsection
