@extends('layouts.app')

@section('title', ($archive ?? false) ? 'Checked-Out Booking Archive' : 'Bookings')

@section('content')
@include('admin.bookings.partials.compact-style')
<div class="booking-workspace">
<div class="row">
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">{{ ($archive ?? false) ? 'Checked-Out Bookings' : 'Active Bookings' }}</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $totalBookings }}</p></div>
            <div class="avatar-md bg-primary bg-opacity-10 rounded"><iconify-icon icon="solar:calendar-bold" class="fs-32 text-primary avatar-title"></iconify-icon></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Paid Invoice Periods</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $paidInvoices }}</p></div>
            <div class="avatar-md bg-success bg-opacity-10 rounded"><iconify-icon icon="solar:bill-check-bold" class="fs-32 text-success avatar-title"></iconify-icon></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Partly Paid Periods</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $partialInvoices }}</p></div>
            <div class="avatar-md bg-warning bg-opacity-10 rounded"><iconify-icon icon="solar:bill-list-broken" class="fs-32 text-warning avatar-title"></iconify-icon></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Unpaid Invoice Periods</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $unpaidInvoices }}</p></div>
            <div class="avatar-md bg-warning bg-opacity-10 rounded"><iconify-icon icon="solar:bill-cross-bold" class="fs-32 text-warning avatar-title"></iconify-icon></div>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card" id="bookingTableCard">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center border-bottom">
                <div><h4 class="card-title mb-1">{{ ($archive ?? false) ? 'Checked-Out Booking Archive' : 'Active Bookings' }}</h4><p class="text-muted small mb-0">{{ ($archive ?? false) ? 'Every booking with a completed checkout is retained here with its invoices, payments and history.' : 'Current and upcoming stays. Completed checkouts move to the archive.' }}</p></div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown"><i class="ri-download-2-line me-1"></i>Export</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('admin.booking.export.excel', array_merge(request()->except(['page','per_page']), ($archive ?? false) ? ['status'=>'checked_out'] : [])) }}"><i class="ri-file-excel-2-line me-2 text-success"></i>Excel</a>
                            <a class="dropdown-item" href="{{ route('admin.booking.export.pdf', array_merge(request()->except(['page','per_page']), ($archive ?? false) ? ['status'=>'checked_out'] : [])) }}"><i class="ri-file-pdf-2-line me-2 text-danger"></i>PDF</a>
                        </div>
                    </div>
                    @if($archive ?? false)<a href="{{ route('admin.booking.index') }}" class="btn btn-sm btn-outline-primary"><i class="ri-calendar-check-line me-1"></i>Active Bookings</a>@else<a href="{{ route('admin.booking.checked-out') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-archive-line me-1"></i>Checked-Out Archive</a><a href="{{ route('admin.booking.grid', request()->except('page')) }}" class="btn btn-sm btn-outline-primary">Grid View</a><a href="{{ route('admin.booking.create') }}" class="btn btn-sm btn-primary">+ Create Booking</a>@endif
                </div>
            </div>

            @include('admin.bookings.partials.filters', ['embedded' => true])

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
                            <th>Payment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            @php($paymentSummary = \App\Support\BookingPaymentSummary::for($booking))
                            <tr>
                                <td>
                                    <a href="{{ route('admin.booking.show', $booking->id) }}" class="text-dark fw-medium">{{ $booking->booking_reference }}</a>
                                    <p class="text-muted mb-0">{{ $booking->created_at->format('d M Y') }}</p>
                                    <span class="small text-muted">{{ str($booking->status)->replace('_', ' ')->headline() }}</span>
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
                                    <span class="badge {{ ['paid'=>'bg-success','partial'=>'bg-warning','unpaid'=>'bg-danger'][$paymentSummary['status']] ?? 'bg-secondary' }} text-white">{{ $paymentSummary['status']==='partial' ? 'Partly paid' : ucfirst($paymentSummary['status']) }}</span>
                                    <div class="small mt-1">Received AED {{ number_format($paymentSummary['paid'], 2) }}<br>Balance AED {{ number_format($paymentSummary['balance'], 2) }}</div>
                                    @if($paymentSummary['invoices']->isNotEmpty())<div class="small text-muted">{{ $paymentSummary['invoices']->where('status','paid')->count() }} paid · {{ $paymentSummary['invoices']->where('status','partial')->count() }} partial · {{ $paymentSummary['invoices']->where('status','unpaid')->count() }} unpaid</div>
                                    <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#paymentBreakdown{{ $booking->id }}">View invoices</button>@endif
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
            @foreach($bookings as $booking)
                @php($paymentSummary = \App\Support\BookingPaymentSummary::for($booking))
                @if($paymentSummary['invoices']->isNotEmpty())
                <div class="modal fade" id="paymentBreakdown{{ $booking->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
                    <div class="modal-header"><div><h5 class="modal-title">Payment breakdown · {{ $booking->booking_reference }}</h5><small class="text-muted">{{ $booking->guest_name }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Invoice / Period</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead><tbody>
                    @foreach($paymentSummary['invoices'] as $row)<tr><td><strong>{{ $row['invoice']->invoice_number }}</strong><div class="small text-muted">{{ $row['invoice']->period_from?->format('d M Y') }}{{ $row['invoice']->period_to ? ' – '.$row['invoice']->period_to->format('d M Y') : '' }}</div></td><td class="text-end">AED {{ number_format($row['total'],2) }}</td><td class="text-end text-success">AED {{ number_format($row['paid'],2) }}</td><td class="text-end text-danger">AED {{ number_format($row['balance'],2) }}</td><td>{{ $row['status']==='partial' ? 'Partly paid' : ucfirst($row['status']) }}</td></tr>@endforeach
                    </tbody><tfoot><tr class="fw-semibold"><td>Total</td><td class="text-end">AED {{ number_format($paymentSummary['total'],2) }}</td><td class="text-end">AED {{ number_format($paymentSummary['paid'],2) }}</td><td class="text-end">AED {{ number_format($paymentSummary['balance'],2) }}</td><td></td></tr></tfoot></table></div></div>
                    <div class="modal-footer"><a href="{{ route('admin.booking.show',$booking) }}" class="btn btn-primary">Open booking</a><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
                </div></div></div>
                @endif
            @endforeach
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <label for="bookingPerPage" class="small mb-0">Rows per page</label>
                    <select id="bookingPerPage" name="per_page" form="bookingFilters" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                        @foreach([10,12,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',12)===$size)>{{ $size }}</option>@endforeach
                    </select>
                    <span class="small text-muted">{{ $bookings->firstItem() ?? 0 }}–{{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }} bookings</span>
                </div>
                <div>{{ $bookings->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
