@extends('layouts.app')

@section('content')
@include('admin.bookings.partials.compact-style')
<div class="booking-workspace">
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Booking Grid View</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.booking.index', request()->except('page')) }}" class="btn btn-sm btn-outline-primary">List View</a>
            <a href="{{ route('admin.booking.create') }}" class="btn btn-sm btn-primary">+ Create Booking</a>
        </div>
    </div>
</div>

@include('admin.bookings.partials.filters')
<div class="row">
    @forelse($bookings as $booking)
        <div class="col-lg-4 col-md-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <a href="{{ route('admin.booking.show', $booking->id) }}" class="text-dark fw-medium fs-16">{{ $booking->booking_reference }}</a>
                            <p class="text-muted mb-0">{{ $booking->property?->building?->name }} — {{ $booking->property?->name ?? 'N/A' }}</p>
                        </div>
                        <span class="badge {{ $booking->workflow_status_class }} text-white">{{ $booking->workflow_status_label }}</span>
                    </div>
                    <div class="row mt-3 g-2">
                        <div class="col-6"><span class="badge bg-light-subtle text-muted border fs-12">{{ $booking->check_in?->format('d M Y') }}</span></div>
                        <div class="col-6"><span class="badge bg-light-subtle text-muted border fs-12">{{ $booking->check_out?->format('d M Y') }}</span></div>
                        <div class="col-12"><p class="mb-0 fw-semibold">{{ $booking->guest_name }}</p><p class="text-muted mb-0">{{ $booking->guest_phone }}</p></div>
                        <div class="col-12"><p class="text-muted mb-0">Agent: {{ $booking->agent?->name ?? '-' }}</p></div>
                    </div>
                </div>
                <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                    <p class="fw-medium text-dark fs-16 mb-0">{{ number_format((float) $booking->total_amount, 2) }} AED</p>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.booking.show', $booking->id) }}" class="link-primary fw-medium">Details <i class="ri-arrow-right-line align-middle"></i></a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-4">No bookings found.</div></div></div>
    @endforelse
</div>

{{ $bookings->links('pagination::bootstrap-5') }}
</div>
@endsection
