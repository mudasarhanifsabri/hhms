@extends('layouts.app')

@section('content')
@include('admin.bookings.partials.compact-style')
<div class="booking-workspace">
<form action="{{ route('admin.booking.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Guest Information</h4></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6"><label class="form-label" for="guest_name">Guest Name</label><input type="text" id="guest_name" name="guest_name" value="{{ old('guest_name') }}" class="form-control">@error('guest_name')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_email">Email</label><input type="email" id="guest_email" name="guest_email" value="{{ old('guest_email') }}" class="form-control">@error('guest_email')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_phone">Phone</label><input type="text" id="guest_phone" name="guest_phone" value="{{ old('guest_phone') }}" class="form-control">@error('guest_phone')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_passport_id_no">Passport/ID No.</label><input type="text" id="guest_passport_id_no" name="guest_passport_id_no" value="{{ old('guest_passport_id_no') }}" class="form-control">@error('guest_passport_id_no')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-12"><label class="form-label" for="guest_document">Attachment</label><input type="file" id="guest_document" name="guest_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">@error('guest_document')<span class="text-danger">{{ $message }}</span>@enderror</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Booking Details</h4></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3"><label class="form-label" for="check_in">Check In</label><input type="date" id="check_in" name="check_in" value="{{ old('check_in') }}" class="form-control">@error('check_in')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_in_time">Check In Time</label><input type="time" id="check_in_time" name="check_in_time" value="{{ old('check_in_time', '15:00') }}" class="form-control">@error('check_in_time')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_out">Check Out Date</label><input type="date" id="check_out" name="check_out" value="{{ old('check_out') }}" class="form-control">@error('check_out')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_out_time">Check Out Time</label><input type="time" id="check_out_time" name="check_out_time" value="{{ old('check_out_time', '11:00') }}" class="form-control">@error('check_out_time')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-12">
                            <label class="form-label" for="property_id">Unit Select</label>
                            <select id="property_id" name="property_id" class="form-control">
                                <option value="">Select Unit</option>
                                @foreach($properties as $property)
                                    <option value="{{ $property->id }}" @selected(old('property_id') === $property->id) data-rent="{{ $property->rent ?? 0 }}" data-management-fee-percent="{{ $property->management_fee_percent ?? 0 }}">{{ $property->name }} - {{ optional($property->building)->building_name ?? 'No Building' }}</option>
                                @endforeach
                            </select>
                            @error('property_id')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-lg-12">
                            <label class="form-label" for="agent_id">Select Agent</label>
                            <select id="agent_id" name="agent_id" class="form-control">
                                <option value="">No Agent</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected(old('agent_id') === $agent->id)>{{ $agent->name }} - {{ $agent->email }}</option>
                                @endforeach
                            </select>
                            @error('agent_id')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-lg-12"><label class="form-label" for="notes">History / Notes</label><textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Invoice Charges</h4><small class="text-muted">Enter rent only. Other fees and deposit are separate. Saving does not record payment.</small></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label" for="rent_amount">Rent amount entered (AED)</label><input type="number" step="0.01" min="0" id="rent_amount" name="rent_amount" value="{{ old('rent_amount', 0) }}" class="form-control booking-money"></div>
                    <div class="btn-group w-100 mb-3" role="group" aria-label="VAT treatment">
                        <input type="radio" class="btn-check booking-money" id="vat_included" name="vat_included" value="1" @checked(old('vat_included', false))><label class="btn btn-outline-primary" for="vat_included">VAT Included</label>
                        <input type="radio" class="btn-check booking-money" id="vat_added" name="vat_included" value="0" @checked(!(old('vat_included', false)))><label class="btn btn-outline-primary" for="vat_added">Add VAT</label>
                    </div>
                    <div class="mb-3"><label class="form-label" for="dtcm_fee">DTCM Fee <span class="badge bg-light text-muted">No VAT</span></label><input type="number" step="0.01" min="0" id="dtcm_fee" name="dtcm_fee" value="{{ old('dtcm_fee', 0) }}" class="form-control booking-money"></div>
                    <div class="mb-3"><label class="form-label" for="cleaning_fee">Cleaning Fee <span class="badge bg-primary-subtle text-primary">+ 5% VAT</span></label><input type="number" step="0.01" min="0" id="cleaning_fee" name="cleaning_fee" value="{{ old('cleaning_fee', 0) }}" class="form-control booking-money"></div>
                    <div class="mb-3"><label class="form-label" for="agency_fee">Agency Fee <span class="badge bg-primary-subtle text-primary">+ 5% VAT</span></label><input type="number" step="0.01" min="0" id="agency_fee" name="agency_fee" value="{{ old('agency_fee', 0) }}" class="form-control booking-money"></div>
                    <div class="mb-3"><label class="form-label" for="security_deposit">Refundable security deposit (company held)</label><input type="number" step="0.01" min="0" id="security_deposit" name="security_deposit" value="{{ old('security_deposit', 0) }}" class="form-control booking-money"></div>
                    <div class="table-responsive border rounded mt-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Charge</th><th class="text-end">Net</th><th class="text-center">VAT</th><th class="text-end">VAT Amount</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                <tr><td>Rent</td><td class="text-end" id="summary_rent_net">0.00</td><td class="text-center">5%</td><td class="text-end" id="summary_rent_vat">0.00</td><td class="text-end" id="summary_rent_total">0.00</td></tr>
                                <tr><td>Cleaning Fee</td><td class="text-end" id="summary_cleaning_net">0.00</td><td class="text-center">5%</td><td class="text-end" id="summary_cleaning_vat">0.00</td><td class="text-end" id="summary_cleaning_total">0.00</td></tr>
                                <tr><td>Agency Fee</td><td class="text-end" id="summary_agency_net">0.00</td><td class="text-center">5%</td><td class="text-end" id="summary_agency_vat">0.00</td><td class="text-end" id="summary_agency_total">0.00</td></tr>
                                <tr><td>DTCM Fee</td><td class="text-end" id="summary_dtcm">0.00</td><td class="text-center text-muted">No VAT</td><td class="text-end">0.00</td><td class="text-end" id="summary_dtcm_total">0.00</td></tr>
                                <tr><td>Security Deposit</td><td class="text-end" id="summary_deposit">0.00</td><td class="text-center text-muted">No VAT</td><td class="text-end">0.00</td><td class="text-end" id="summary_deposit_total">0.00</td></tr>
                            </tbody>
                            <tfoot class="table-light fw-semibold">
                                <tr><td colspan="3">Total VAT</td><td class="text-end" id="vat_amount">0.00</td><td></td></tr>
                                <tr><td colspan="4">Grand Total</td><td class="text-end"><span id="booking_total">0.00</span> AED</td></tr>
                            </tfoot>
                        </table>
                        <input type="hidden" id="base_rent">
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Create Booking</button>
                <a href="{{ route('admin.booking.index') }}" class="btn btn-danger">Cancel</a>
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
        const rentVat = vatIncluded ? rentInput - (rentInput / 1.05) : rentInput * 0.05;
        const rent = vatIncluded ? rentInput - rentVat : rentInput;
        const cleaning = money('cleaning_fee');
        const agency = money('agency_fee');
        const dtcm = money('dtcm_fee');
        const deposit = money('security_deposit');
        const cleaningVat = cleaning * 0.05;
        const agencyVat = agency * 0.05;
        const vat = rentVat + cleaningVat + agencyVat;
        const total = rent + vat + dtcm + cleaning + agency + deposit;
        document.getElementById('base_rent').value = rent.toFixed(2);
        const show = (id, value) => document.getElementById(id).textContent = value.toFixed(2);
        show('summary_rent_net', rent); show('summary_rent_vat', rentVat); show('summary_rent_total', rent + rentVat);
        show('summary_cleaning_net', cleaning); show('summary_cleaning_vat', cleaningVat); show('summary_cleaning_total', cleaning + cleaningVat);
        show('summary_agency_net', agency); show('summary_agency_vat', agencyVat); show('summary_agency_total', agency + agencyVat);
        show('summary_dtcm', dtcm); show('summary_dtcm_total', dtcm);
        show('summary_deposit', deposit); show('summary_deposit_total', deposit);
        show('vat_amount', vat);
        document.getElementById('booking_total').textContent = total.toFixed(2);
    };

    document.querySelectorAll('.booking-money, #vat_included').forEach((input) => input.addEventListener('input', calculateBookingTotal));
    document.getElementById('vat_included').addEventListener('change', calculateBookingTotal);
    document.getElementById('property_id').addEventListener('change', (event) => {
        const rent = event.target.selectedOptions[0]?.dataset.rent;
        if (rent && Number(rent) > 0) {
            document.getElementById('rent_amount').value = Number(rent).toFixed(2);
            calculateBookingTotal();
        }
    });
    calculateBookingTotal();
</script>
@endsection
