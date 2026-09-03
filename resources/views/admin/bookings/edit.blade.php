@extends('layouts.app')

@section('content')
@include('admin.bookings.partials.compact-style')
<div class="booking-workspace">
@include('admin.bookings.partials.navigation')
<form action="{{ route('admin.booking.update', $booking->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Edit Booking</h4></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6"><label class="form-label" for="guest_name">Guest Name</label><input type="text" id="guest_name" name="guest_name" value="{{ old('guest_name', $booking->guest_name) }}" class="form-control">@error('guest_name')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_email">Email</label><input type="email" id="guest_email" name="guest_email" value="{{ old('guest_email', $booking->guest_email) }}" class="form-control">@error('guest_email')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_phone">Phone</label><input type="text" id="guest_phone" name="guest_phone" value="{{ old('guest_phone', $booking->guest_phone) }}" class="form-control">@error('guest_phone')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_passport_id_no">Passport/ID No.</label><input type="text" id="guest_passport_id_no" name="guest_passport_id_no" value="{{ old('guest_passport_id_no', $booking->guest_passport_id_no) }}" class="form-control">@error('guest_passport_id_no')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-12">
                            <label class="form-label" for="guest_document">Attachment</label>
                            <input type="file" id="guest_document" name="guest_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @if($booking->guest_document)
                                <a href="{{ asset($booking->guest_document) }}" target="_blank" class="d-inline-block mt-2">View current attachment</a>
                            @endif
                            @error('guest_document')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Booking Details</h4></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3"><label class="form-label" for="check_in">Check In</label><input type="date" id="check_in" name="check_in" value="{{ old('check_in', $booking->check_in?->format('Y-m-d')) }}" class="form-control">@error('check_in')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_in_time">Check In Time</label><input type="time" id="check_in_time" name="check_in_time" value="{{ old('check_in_time', $booking->check_in_time ? \Carbon\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00') }}" class="form-control">@error('check_in_time')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_out">Check Out Date</label><input type="date" id="check_out" name="check_out" value="{{ old('check_out', $booking->check_out?->format('Y-m-d')) }}" class="form-control">@error('check_out')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_out_time">Check Out Time</label><input type="time" id="check_out_time" name="check_out_time" value="{{ old('check_out_time', $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00') }}" class="form-control">@error('check_out_time')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-12">
                            <label class="form-label" for="property_id">Unit Select</label>
                            <select id="property_id" name="property_id" class="form-control">
                                @foreach($properties as $property)
                                    <option value="{{ $property->id }}" @selected(old('property_id', $booking->property_id) === $property->id) data-rent="{{ $property->rent ?? 0 }}">{{ $property->name }} - {{ optional($property->building)->building_name ?? 'No Building' }}</option>
                                @endforeach
                            </select>
                            @error('property_id')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-lg-12">
                            <label class="form-label" for="agent_id">Select Agent</label>
                            <select id="agent_id" name="agent_id" class="form-control">
                                <option value="">No Agent</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected(old('agent_id', $booking->agent_id) === $agent->id)>{{ $agent->name }} - {{ $agent->email }}</option>
                                @endforeach
                            </select>
                            @error('agent_id')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-lg-12"><label class="form-label" for="notes">History / Notes</label><textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $booking->notes) }}</textarea></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Invoice Charges</h4><small class="text-muted">Enter rent only. Other fees and deposit are separate. Saving does not record payment.</small></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label" for="rent_amount">Rent amount entered (AED)</label><input type="number" step="0.01" min="0" id="rent_amount" name="rent_amount" value="{{ old('rent_amount', (float)$booking->rent_amount + ($booking->vat_included ? (float)$booking->vat_amount : 0)) }}" class="form-control booking-money"></div>
                    <div class="btn-group w-100 mb-3" role="group" aria-label="VAT treatment">
                        <input type="radio" class="btn-check booking-money" id="vat_included" name="vat_included" value="1" @checked(old('vat_included', $booking->vat_included))><label class="btn btn-outline-primary" for="vat_included">VAT Included</label>
                        <input type="radio" class="btn-check booking-money" id="vat_added" name="vat_included" value="0" @checked(!(old('vat_included', $booking->vat_included)))><label class="btn btn-outline-primary" for="vat_added">Add VAT</label>
                    </div>
                    <div class="mb-3"><label class="form-label" for="base_rent">Rent excluding VAT</label><input id="base_rent" class="form-control" readonly></div>
                    <div class="mb-3"><label class="form-label" for="vat_amount">VAT 5%</label><input type="number" step="0.01" id="vat_amount" class="form-control" readonly></div>
                    <div class="mb-3"><label class="form-label" for="dtcm_fee">DTCM Fee</label><input type="number" step="0.01" min="0" id="dtcm_fee" name="dtcm_fee" value="{{ old('dtcm_fee', $booking->dtcm_fee) }}" class="form-control booking-money"></div>
                    <div class="mb-3"><label class="form-label" for="cleaning_fee">Cleaning Fee</label><input type="number" step="0.01" min="0" id="cleaning_fee" name="cleaning_fee" value="{{ old('cleaning_fee', $booking->cleaning_fee) }}" class="form-control booking-money"></div>
                    <div class="mb-3"><label class="form-label" for="agency_fee">Agency Fee</label><input type="number" step="0.01" min="0" id="agency_fee" name="agency_fee" value="{{ old('agency_fee', $booking->agency_fee) }}" class="form-control booking-money"></div>
                    <div class="mb-3"><label class="form-label" for="security_deposit">Refundable security deposit (company held)</label><input type="number" step="0.01" min="0" id="security_deposit" name="security_deposit" value="{{ old('security_deposit', $booking->security_deposit) }}" class="form-control booking-money"></div>
                    <div class="border rounded p-3 bg-light-subtle">
                        <p class="text-muted mb-1">Invoice total</p>
                        <h4 class="mb-0"><span id="booking_total">0.00</span> AED</h4>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Update Booking</button>
                <a href="{{ route('admin.booking.show', $booking->id) }}" class="btn btn-danger">Cancel</a>
            </div>
        </div>
    </div>
</form>
</div>
@endsection

@section('script')
<script>
    const money = (id) => Number.parseFloat(document.getElementById(id)?.value || 0) || 0;
    const calculateBookingTotal = () => {
        const rentInput = money('rent_amount');
        const vatIncluded = document.getElementById('vat_included').checked;
        const vat = vatIncluded ? rentInput - (rentInput / 1.05) : rentInput * 0.05;
        const rent = vatIncluded ? rentInput - vat : rentInput;
        const total = rent + vat + money('dtcm_fee') + money('cleaning_fee') + money('agency_fee') + money('security_deposit');
        document.getElementById('base_rent').value = rent.toFixed(2);
        document.getElementById('vat_amount').value = vat.toFixed(2);
        document.getElementById('booking_total').textContent = total.toFixed(2);
    };

    document.querySelectorAll('.booking-money, #vat_included').forEach((input) => input.addEventListener('input', calculateBookingTotal));
    document.getElementById('vat_included').addEventListener('change', calculateBookingTotal);
    calculateBookingTotal();
</script>
@endsection
