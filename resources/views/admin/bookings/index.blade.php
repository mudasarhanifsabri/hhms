@extends('layouts.app')

@section('content')
@include('admin.bookings.partials.filters')
<div class="row">
    <div class="col-md-4">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Total Bookings</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $totalBookings }}</p></div>
            <div class="avatar-md bg-primary bg-opacity-10 rounded"><iconify-icon icon="solar:calendar-bold" class="fs-32 text-primary avatar-title"></iconify-icon></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Paid Invoices</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $paidInvoices }}</p></div>
            <div class="avatar-md bg-success bg-opacity-10 rounded"><iconify-icon icon="solar:bill-check-bold" class="fs-32 text-success avatar-title"></iconify-icon></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Unpaid Invoices</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $unpaidInvoices }}</p></div>
            <div class="avatar-md bg-warning bg-opacity-10 rounded"><iconify-icon icon="solar:bill-cross-bold" class="fs-32 text-warning avatar-title"></iconify-icon></div>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                <h4 class="card-title mb-0">List Of Booking</h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.booking.grid', request()->except('page')) }}" class="btn btn-sm btn-outline-primary">Grid View</a>
                    <a href="{{ route('admin.booking.create') }}" class="btn btn-sm btn-primary">+ Create Booking</a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table align-middle text-nowrap table-hover table-centered mb-0">
                    <thead class="bg-light-subtle">
                        <tr>
                            <th>Booking</th>
                            <th>Guest</th>
                            <th>Unit</th>
                            <th>Agent</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Total</th>
                            <th>Invoice</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.booking.show', $booking->id) }}" class="text-dark fw-medium">{{ $booking->booking_reference }}</a>
                                    <p class="text-muted mb-0">{{ $booking->created_at->format('d M Y') }}</p>
                                </td>
                                <td>
                                    {{ $booking->guest_name }}
                                    <p class="text-muted mb-0">{{ $booking->guest_email }}</p>
                                </td>
                                <td>{{ $booking->property?->name ?? 'N/A' }}<div class="small text-muted">{{ $booking->property?->building?->name }}</div></td>
                                <td>{{ $booking->agent?->name ?? '-' }}</td>
                                <td>{{ $booking->check_in?->format('d M Y') }}</td>
                                <td>{{ $booking->check_out?->format('d M Y') }}</td>
                                <td>{{ number_format((float) $booking->total_amount, 2) }} AED</td>
                                <td>
                                    <span class="badge {{ $booking->workflow_status_class }} text-white">
                                        {{ $booking->workflow_status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.booking.show', $booking->id) }}" class="btn btn-light btn-sm"><iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon></a>
                                        <a href="{{ route('admin.booking.invoice', $booking->id) }}" class="btn btn-soft-primary btn-sm" title="Invoice"><iconify-icon icon="solar:bill-list-broken" class="align-middle fs-18"></iconify-icon></a>
                                        <a href="{{ route('admin.booking.confirmation', $booking->id) }}" class="btn btn-soft-success btn-sm" title="Booking Confirmation"><iconify-icon icon="solar:document-add-broken" class="align-middle fs-18"></iconify-icon></a>
                                        <a href="{{ route('admin.booking.history', $booking->id) }}" class="btn btn-soft-warning btn-sm" title="History"><iconify-icon icon="solar:history-2-broken" class="align-middle fs-18"></iconify-icon></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4"><h5 class="text-muted mb-0">No bookings found.</h5></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $bookings->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>
@endsection
