@extends('layouts.app')
@section('content')
@include('admin.bookings.partials.compact-style')
<div class="booking-workspace">
@include('admin.bookings.partials.navigation')
<div class="card mx-auto" style="max-width:800px">
    <div class="card-header"><h4 class="card-title">Edit Guest Details · {{ $booking->booking_reference }}</h4></div>
    <form method="POST" action="{{ route('admin.booking.update',$booking) }}">@csrf @method('PUT')
        <input type="hidden" name="edit_details_only" value="1">
        <div class="card-body"><p class="text-muted">{{ $booking->property?->name }} · {{ $booking->check_in?->format('d M Y') }} — {{ $booking->check_out?->format('d M Y') }}</p>
            <div class="alert alert-info">Contact details only. Use Invoices for charge corrections, or Extend / Renew for stay changes.</div>
            <div class="row g-3">
                <div class="col-12"><label for="guestName" class="form-label">Guest name</label><input id="guestName" name="guest_name" value="{{ old('guest_name',$booking->guest_name) }}" class="form-control" required></div>
                <div class="col-sm-6"><label for="guestEmail" class="form-label">Email</label><input id="guestEmail" type="email" name="guest_email" value="{{ old('guest_email',$booking->guest_email) }}" class="form-control" required></div>
                <div class="col-sm-6"><label for="guestPhone" class="form-label">Phone</label><input id="guestPhone" name="guest_phone" value="{{ old('guest_phone',$booking->guest_phone) }}" class="form-control" required></div>
                <div class="col-12"><label for="guestNotes" class="form-label">Notes</label><textarea id="guestNotes" name="notes" rows="2" class="form-control">{{ old('notes',$booking->notes) }}</textarea></div>
                <div class="col-12"><label for="guestReason" class="form-label">Reason for correction</label><textarea id="guestReason" name="reason" rows="2" minlength="5" class="form-control" required>{{ old('reason') }}</textarea></div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.booking.show',$booking) }}">Cancel</a><button class="btn btn-primary">Save Guest Details</button></div>
    </form>
</div></div>
@endsection
