@extends('layouts.app')

@section('content')
@include('admin.bookings.partials.compact-style')
<style>
    .booking-create-heading { margin-bottom: 1.25rem; }
    .booking-create-heading h3 { margin-bottom: .2rem; font-weight: 700; }
    .booking-flow { max-width: 1040px; margin: 0 auto 1.5rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
    .booking-flow-step { display: flex; align-items: center; gap: .75rem; padding: .9rem 1rem; border: 1px solid #e5e9f2; border-radius: .75rem; background: #fff; color: #68758c; text-align: left; cursor: pointer; }
    .booking-flow-step.active { border-color: #6152d8; background: #f3f1ff; color: #302a72; box-shadow: 0 3px 10px rgba(68, 55, 147, .08); }
    .booking-flow-step.done { color: #17805f; }
    .booking-flow-number { width: 2rem; height: 2rem; flex: 0 0 2rem; display: grid; place-items: center; border-radius: 50%; background: #edf0f6; font-weight: 700; }
    .booking-flow-step.active .booking-flow-number { background: #6152d8; color: white; }
    .booking-flow-step.done .booking-flow-number { background: #dff5e9; }
    .booking-flow-step strong, .booking-flow-step small { display: block; }
    .booking-flow-step small { font-size: .75rem; font-weight: 400; }
    .booking-step-panel { max-width: 1040px; margin: 0 auto; }
    .booking-step-panel[data-booking-step="3"] { max-width: none; }
    .booking-step-panel[hidden] { display: none !important; }
    .booking-step-panel .card { margin-bottom: 1rem; }
    .booking-charges-card .card-body { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; gap: 1rem 1.5rem; }
    .booking-charges-card .card-body > .mb-3 { margin-bottom: 0 !important; }
    .booking-charges-card .booking-charge-preview { grid-column: 1 / -1; margin-top: .25rem !important; }
    .booking-review-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin-bottom: 1rem; }
    .booking-review-item { background: #fff; border: 1px solid #e5e9f2; border-radius: .65rem; padding: .8rem 1rem; }
    .booking-review-item small, .booking-review-item strong { display: block; }
    .booking-review-item small { color: #718096; }
    .booking-review-item strong { margin-top: .2rem; color: #202c42; }
    .booking-flow-error { color: #c63d47; margin: 0 auto .75rem; max-width: 1040px; }
    .booking-charge-preview { overflow-x: auto; }
    .booking-charge-preview table { min-width: 440px; }
    .booking-schedule-table { min-width: 860px; }
    .booking-schedule-table th, .booking-schedule-table td { padding: .75rem .65rem; }
    .booking-schedule-table .schedule-period { white-space: nowrap; }
    .booking-schedule-table .period-rent { min-width: 120px; max-width: 150px; }
    .booking-step-panel[data-booking-step="3"] .booking-schedule-table .period-rent { min-width: 155px; max-width: 190px; font-size: 1.12rem; font-weight: 750; color: #192641; border-color: #cbd2e2; background: #fafbff; }
    .booking-step-panel[data-booking-step="3"] .booking-schedule-table .period-total { font-size: 1.1rem; color: #27366a; }
    .booking-step-panel[data-booking-step="3"] .booking-schedule-table tbody tr:hover { background: #fafbff; }
    .booking-step-panel[data-booking-step="3"] .booking-schedule-table tfoot { font-size: 1.08rem; }
    .booking-schedule-table .schedule-nights { display: block; font-size: .75rem; color: #718096; }
    .booking-create-actions { display: flex; justify-content: flex-end; gap: .75rem; max-width: 1040px; margin: 1.25rem auto; }
    .booking-create-actions .btn { min-width: 140px; }
    .booking-create-actions [hidden] { display: none !important; }
    @media (max-width: 767px) {
        .booking-flow { gap: .35rem; }
        .booking-flow-step { padding: .55rem; gap: .35rem; }
        .booking-flow-step small { display: none; }
        .booking-flow-step strong { font-size: .74rem; }
        .booking-flow-number { width: 1.5rem; height: 1.5rem; flex-basis: 1.5rem; font-size: .75rem; }
        .booking-charges-card .card-body, .booking-review-strip { grid-template-columns: 1fr; }
        .booking-charges-card .booking-charge-preview { grid-column: 1; }
        .booking-create-actions { flex-direction: column-reverse; }
        .booking-create-actions .btn { width: 100%; }
        .booking-schedule-table { min-width: 0; }
        .booking-schedule-table thead { display: none; }
        .booking-schedule-table tbody, .booking-schedule-table tbody tr, .booking-schedule-table tbody td { display: block; width: 100%; }
        .booking-schedule-table tbody tr { margin-bottom: .75rem; border: 1px solid #e5e9f2; border-radius: .6rem; padding: .8rem; }
        .booking-schedule-table tbody td { border: 0; padding: .3rem 0; }
        .booking-schedule-table tbody td[data-label]::before { content: attr(data-label); color: #718096; font-size: .76rem; display: block; }
        .booking-schedule-table tbody .text-end { text-align: left !important; }
        .booking-schedule-table .period-rent { max-width: none; }
        .booking-schedule-table tfoot td { padding: .75rem; }
    }
</style>
<div class="booking-workspace">
<div class="booking-create-heading"><h3>Create Booking</h3><div class="text-muted">Enter the stay details, then review each invoice period before saving.</div></div>
<form action="{{ route('admin.booking.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <nav class="booking-flow" aria-label="Booking steps">
        <button type="button" class="booking-flow-step active" data-go-step="1" aria-current="step"><span class="booking-flow-number">1</span><span><strong>Guest &amp; stay</strong><small>Who, where and when</small></span></button>
        <button type="button" class="booking-flow-step" data-go-step="2"><span class="booking-flow-number">2</span><span><strong>Charges</strong><small>Rent, VAT and fees</small></span></button>
        <button type="button" class="booking-flow-step" data-go-step="3"><span class="booking-flow-number">3</span><span><strong>Review</strong><small>Invoice dates and totals</small></span></button>
    </nav>
    <div class="booking-flow-error" id="booking-flow-error" role="alert" hidden></div>
    <section class="booking-step-panel" data-booking-step="1">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Guest Information</h4></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6"><label class="form-label" for="guest_name">Guest Name</label><input type="text" id="guest_name" name="guest_name" value="{{ old('guest_name') }}" class="form-control" required>@error('guest_name')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_email">Email</label><input type="email" id="guest_email" name="guest_email" value="{{ old('guest_email') }}" class="form-control" required>@error('guest_email')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_phone">Phone</label><input type="text" id="guest_phone" name="guest_phone" value="{{ old('guest_phone') }}" class="form-control" required>@error('guest_phone')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-6"><label class="form-label" for="guest_passport_id_no">Passport/ID No.</label><input type="text" id="guest_passport_id_no" name="guest_passport_id_no" value="{{ old('guest_passport_id_no') }}" class="form-control">@error('guest_passport_id_no')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-12"><label class="form-label" for="guest_document">Attachment</label><input type="file" id="guest_document" name="guest_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">@error('guest_document')<span class="text-danger">{{ $message }}</span>@enderror</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Booking Details</h4></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3"><label class="form-label" for="check_in">Check In</label><input type="date" id="check_in" name="check_in" value="{{ old('check_in') }}" class="form-control" required>@error('check_in')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_in_time">Check In Time</label><input type="time" id="check_in_time" name="check_in_time" value="{{ old('check_in_time', '15:00') }}" class="form-control">@error('check_in_time')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_out">Check Out Date</label><input type="date" id="check_out" name="check_out" value="{{ old('check_out') }}" class="form-control" required>@error('check_out')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-3"><label class="form-label" for="check_out_time">Check Out Time</label><input type="time" id="check_out_time" name="check_out_time" value="{{ old('check_out_time', '11:00') }}" class="form-control">@error('check_out_time')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="col-lg-12">
                            <label class="form-label" for="property_id">Unit Select</label>
                            <select id="property_id" name="property_id" class="form-control" required>
                                <option value="">Select Unit</option>
                                @foreach($properties as $property)
                                    <option value="{{ $property->id }}" @selected(old('property_id') === $property->id) data-rent="{{ $property->rent ?? 0 }}" data-dtcm="{{ \App\Support\BookingInvoiceSchedule::dtcmRate($property) ?? '' }}" data-unit-type="{{ $property->category }}" data-management-fee-percent="{{ $property->management_fee_percent ?? 0 }}">{{ $property->name }} - {{ optional($property->building)->building_name ?? 'No Building' }}</option>
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
    </section>
    <section class="booking-step-panel" data-booking-step="2" hidden>
            <div class="card booking-charges-card">
                <div class="card-header"><h4 class="card-title">Invoice Charges</h4><small class="text-muted">Enter rent only. Other fees and deposit are separate. Saving does not record payment.</small></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label" for="rent_amount">Rent per 30 nights (AED)</label><input type="number" step="0.01" min="0" id="rent_amount" name="rent_amount" value="{{ old('rent_amount', 0) }}" class="form-control booking-money"><small class="text-muted">Used to suggest each invoice rent. You can edit every period below.</small></div>
                    <div class="btn-group w-100 mb-3" role="group" aria-label="VAT treatment">
                        <input type="radio" class="btn-check booking-money" id="vat_included" name="vat_included" value="1" @checked(old('vat_included', false))><label class="btn btn-outline-primary" for="vat_included">VAT Included</label>
                        <input type="radio" class="btn-check booking-money" id="vat_added" name="vat_included" value="0" @checked(!(old('vat_included', false)))><label class="btn btn-outline-primary" for="vat_added">Add VAT</label>
                    </div>
                    <div class="mb-3"><label class="form-label" for="dtcm_fee">DTCM Fee per contract <span class="badge bg-light text-muted">No VAT</span></label><input type="number" step="0.01" min="0" id="dtcm_fee" name="dtcm_fee" value="{{ old('dtcm_fee', 0) }}" class="form-control booking-money"><small class="text-muted">Suggested from unit type settings. Charged on first period and each 90-day renewal.</small>@error('dtcm_fee')<span class="text-danger d-block">{{ $message }}</span>@enderror</div>
                    <div class="mb-3"><label class="form-label" for="cleaning_fee">Cleaning Fee <span class="badge bg-primary-subtle text-primary">+ 5% VAT</span></label><input type="number" step="0.01" min="0" id="cleaning_fee" name="cleaning_fee" value="{{ old('cleaning_fee', 0) }}" class="form-control booking-money"></div>
                    <div class="mb-3"><label class="form-label" for="agency_fee">Agency Fee <span class="badge bg-primary-subtle text-primary">+ 5% VAT</span></label><input type="number" step="0.01" min="0" id="agency_fee" name="agency_fee" value="{{ old('agency_fee', 0) }}" class="form-control booking-money"></div>
                    <div class="mb-3"><label class="form-label" for="security_deposit">Refundable security deposit (company held)</label><input type="number" step="0.01" min="0" id="security_deposit" name="security_deposit" value="{{ old('security_deposit', 0) }}" class="form-control booking-money"></div>
                    <div class="table-responsive booking-charge-preview border rounded mt-3">
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
                                <tr><td colspan="4">First invoice estimate</td><td class="text-end"><span id="booking_total">0.00</span> AED</td></tr>
                            </tfoot>
                        </table>
                        <input type="hidden" id="base_rent">
                    </div>
                </div>
            </div>

    </section>
    <section class="booking-step-panel" data-booking-step="3" hidden>
        <div class="booking-review-strip"><div class="booking-review-item"><small>Guest</small><strong id="review-guest">Not entered</strong></div><div class="booking-review-item"><small>Unit</small><strong id="review-unit">Not selected</strong></div><div class="booking-review-item"><small>Stay</small><strong id="review-stay">Select dates</strong></div></div>
        <div class="card"><div class="card-header d-flex justify-content-between align-items-start gap-2 flex-wrap"><div><h4 class="card-title mb-1">Invoice schedule</h4><small class="text-muted">One invoice per 30 nights. Each invoice is due at the start of its period. Initial fees are charged once; DTCM repeats at 90-day renewal boundaries.</small></div><button type="button" class="btn btn-sm btn-outline-primary" id="fill-period-rents">Fill empty rents</button></div><div class="card-body"><div id="invoice-schedule-message" class="text-muted small mb-2">Select check-in and checkout dates to preview invoices.</div><div class="table-responsive"><table class="table align-middle mb-0 booking-schedule-table" id="invoice-schedule-table" hidden><thead class="table-light"><tr><th>Invoice</th><th>Stay period</th><th>Due date</th><th>Rent (AED)</th><th class="text-end">VAT</th><th class="text-end">Fees &amp; deposit</th><th class="text-end">Invoice total</th></tr></thead><tbody id="invoice-schedule-body"></tbody><tfoot><tr class="table-light fw-bold"><td colspan="6">Total scheduled</td><td class="text-end" id="invoice-schedule-total">0.00</td></tr></tfoot></table></div>@error('period_rents')<div class="text-danger small">{{ $message }}</div>@enderror</div></div>
    </section>
    <div class="booking-create-actions"><a href="{{ route('admin.booking.index') }}" class="btn btn-light" id="booking-cancel">Cancel</a><button type="button" class="btn btn-light" id="booking-back" hidden>Back</button><button type="button" class="btn btn-primary" id="booking-next">Continue to Charges</button><button type="submit" class="btn btn-primary" id="booking-submit" hidden>Create Booking</button></div>
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
        const dtcm = event.target.selectedOptions[0]?.dataset.dtcm;
        if (rent && Number(rent) > 0) {
            document.getElementById('rent_amount').value = Number(rent).toFixed(2);
        }
        if (dtcm !== undefined && dtcm !== '') document.getElementById('dtcm_fee').value = Number(dtcm).toFixed(2);
        calculateBookingTotal(); renderInvoiceSchedule();
    });
    const selectedDtcm = document.getElementById('property_id').selectedOptions[0]?.dataset.dtcm;
    if (selectedDtcm !== undefined && selectedDtcm !== '' && !@json(old('dtcm_fee'))) document.getElementById('dtcm_fee').value = Number(selectedDtcm).toFixed(2);
    const scheduleDate = (date) => new Date(date + 'T00:00:00Z');
    const asDate = (date) => date.toISOString().slice(0, 10);
    const addDays = (date, days) => new Date(date.getTime() + days * 86400000);
    const formatDate = (date) => new Intl.DateTimeFormat('en-GB', {day:'2-digit', month:'short', year:'numeric', timeZone:'UTC'}).format(date);
    function renderInvoiceSchedule() {
        const startValue = document.getElementById('check_in').value, endValue = document.getElementById('check_out').value;
        const table = document.getElementById('invoice-schedule-table'), body = document.getElementById('invoice-schedule-body');
        const message = document.getElementById('invoice-schedule-message');
        const editedRents = new Map([...body.querySelectorAll('tr[data-edited="1"]')].map(row => [row.dataset.from, row.querySelector('.period-rent').value]));
        body.replaceChildren();
        if (!startValue || !endValue) { table.hidden = true; message.textContent = 'Select check-in and checkout dates to preview invoices.'; return; }
        const start = scheduleDate(startValue), end = scheduleDate(endValue);
        const nights = Math.round((end - start) / 86400000);
        if (nights < 1 || nights > 1095) { table.hidden = true; message.textContent = 'Stay must be between 1 night and 3 years.'; return; }
        table.hidden = false; message.textContent = nights + ' nights · ' + Math.ceil(nights / 30) + ' separate invoice(s)';
        const savedRents = @json(old('period_rents', []));
        for (let offset = 0, index = 0; offset < nights; offset += 30, index++) {
            const length = Math.min(30, nights - offset), from = addDays(start, offset), to = addDays(start, offset + length);
            const renewal = offset > 0 && offset % 90 === 0;
            const row = document.createElement('tr');
            row.dataset.length = length; row.dataset.first = index === 0 ? '1' : '0'; row.dataset.renewal = renewal ? '1' : '0'; row.dataset.from = asDate(from);
            const title = index === 0 ? 'Original' : (renewal ? 'Renewal + DTCM' : 'Period ' + (index + 1));
            row.innerHTML = '<td class="fw-semibold"></td><td class="schedule-period"></td><td data-label="Due date"></td><td data-label="Rent (AED)"><input type="number" step="0.01" min="0" max="99999999" class="form-control form-control-sm period-rent" name="period_rents['+index+']" required></td><td data-label="VAT" class="text-end period-vat"></td><td data-label="Fees &amp; deposit" class="text-end period-fees"></td><td data-label="Invoice total" class="text-end fw-semibold period-total"></td>';
            row.children[0].textContent = title;
            row.children[1].textContent = formatDate(from) + ' – ' + formatDate(to);
            const nightsLabel = document.createElement('span'); nightsLabel.className = 'schedule-nights'; nightsLabel.textContent = length + ' nights'; row.children[1].append(nightsLabel);
            row.children[2].textContent = formatDate(from);
            row.querySelector('.period-rent').value = editedRents.get(row.dataset.from) ?? savedRents[index] ?? (money('rent_amount') * (nights <= 30 ? 1 : length / 30)).toFixed(2);
            if (editedRents.has(row.dataset.from)) row.dataset.edited = '1';
            row.querySelector('.period-rent').addEventListener('input', () => { row.dataset.edited = '1'; calculateScheduleTotals(); });
            body.append(row);
        }
        calculateScheduleTotals();
    }
    function calculateScheduleTotals() {
        const included = document.getElementById('vat_included').checked;
        const dtcm = money('dtcm_fee'), cleaning = money('cleaning_fee'), agency = money('agency_fee'), deposit = money('security_deposit');
        let grand = 0;
        document.querySelectorAll('#invoice-schedule-body tr').forEach(row => {
            const input = Number(row.querySelector('.period-rent').value) || 0;
            const rentVat = included ? input - input / 1.05 : input * .05;
            const first = row.dataset.first === '1', renewal = row.dataset.renewal === '1';
            const fees = (first || renewal ? dtcm : 0) + (first ? cleaning + agency + deposit : 0);
            const vat = rentVat + (first ? (cleaning + agency) * .05 : 0);
            const total = (included ? input : input + rentVat) + fees + (first ? (cleaning + agency) * .05 : 0);
            row.querySelector('.period-vat').textContent = vat.toFixed(2);
            row.querySelector('.period-fees').textContent = fees.toFixed(2);
            row.querySelector('.period-total').textContent = total.toFixed(2);
            grand += total;
        });
        document.getElementById('invoice-schedule-total').textContent = grand.toFixed(2) + ' AED';
    }
    ['check_in', 'check_out', 'rent_amount'].forEach(id => document.getElementById(id).addEventListener('change', renderInvoiceSchedule));
    document.querySelectorAll('.booking-money').forEach(input => input.addEventListener('input', calculateScheduleTotals));
    calculateBookingTotal();
    renderInvoiceSchedule();

    const bookingForm = document.querySelector('.booking-workspace form');
    const flowError = document.getElementById('booking-flow-error');
    let currentStep = 1;
    function showBookingStep(step) {
        currentStep = step;
        document.querySelectorAll('[data-booking-step]').forEach(panel => { panel.hidden = Number(panel.dataset.bookingStep) !== step; });
        document.querySelectorAll('[data-go-step]').forEach(button => {
            const number = Number(button.dataset.goStep);
            button.classList.toggle('active', number === step);
            button.classList.toggle('done', number < step);
            if (number === step) button.setAttribute('aria-current', 'step'); else button.removeAttribute('aria-current');
        });
        document.getElementById('booking-back').hidden = step === 1;
        document.getElementById('booking-cancel').hidden = step !== 1;
        document.getElementById('booking-next').hidden = step === 3;
        document.getElementById('booking-next').textContent = step === 1 ? 'Continue to Charges' : 'Review Invoices';
        document.getElementById('booking-submit').hidden = step !== 3;
        flowError.hidden = true;
        if (step === 3) {
            renderInvoiceSchedule();
            document.getElementById('review-guest').textContent = document.getElementById('guest_name').value.trim() || 'Not entered';
            document.getElementById('review-unit').textContent = document.getElementById('property_id').selectedOptions[0]?.textContent || 'Not selected';
            const checkIn = document.getElementById('check_in').value, checkOut = document.getElementById('check_out').value;
            document.getElementById('review-stay').textContent = checkIn && checkOut ? formatDate(scheduleDate(checkIn)) + ' – ' + formatDate(scheduleDate(checkOut)) : 'Select dates';
            calculateScheduleTotals();
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function validateBookingStep(step) {
        if (step === 1) {
            for (const field of document.querySelectorAll('[data-booking-step="1"] input[required], [data-booking-step="1"] select[required]')) {
                if (!field.reportValidity()) { field.focus(); return false; }
            }
            const start = scheduleDate(document.getElementById('check_in').value), end = scheduleDate(document.getElementById('check_out').value);
            const nights = Math.round((end - start) / 86400000);
            if (nights < 1 || nights > 1095) {
                flowError.textContent = 'Checkout must be after check-in, for a stay of no more than 3 years.';
                flowError.hidden = false;
                document.getElementById('check_out').focus();
                return false;
            }
        }
        return true;
    }
    document.getElementById('booking-next').addEventListener('click', () => { if (validateBookingStep(currentStep)) showBookingStep(currentStep + 1); });
    document.getElementById('booking-back').addEventListener('click', () => showBookingStep(currentStep - 1));
    document.getElementById('fill-period-rents').addEventListener('click', () => {
        const rows = [...document.querySelectorAll('#invoice-schedule-body tr')];
        const base = money('rent_amount') || Number(rows[0]?.querySelector('.period-rent').value) || 0;
        if (base <= 0) {
            flowError.textContent = 'Enter rent per 30 nights under Charges, or enter rent in the first invoice.';
            flowError.hidden = false;
            return;
        }
        rows.forEach(row => {
            const field = row.querySelector('.period-rent');
            if (Number(field.value) <= 0) { field.value = (base * Number(row.dataset.length) / 30).toFixed(2); row.dataset.edited = '1'; }
        });
        calculateScheduleTotals();
        flowError.hidden = true;
    });
    document.querySelectorAll('[data-go-step]').forEach(button => button.addEventListener('click', () => {
        const target = Number(button.dataset.goStep);
        if (target < currentStep || (target === currentStep + 1 && validateBookingStep(currentStep))) showBookingStep(target);
    }));
    bookingForm.addEventListener('invalid', event => {
        const panel = event.target.closest('[data-booking-step]');
        if (panel) showBookingStep(Number(panel.dataset.bookingStep));
    }, true);
    bookingForm.addEventListener('submit', event => {
        if (currentStep !== 3) { event.preventDefault(); if (validateBookingStep(currentStep)) showBookingStep(currentStep + 1); return; }
        renderInvoiceSchedule();
        if (!document.querySelector('#invoice-schedule-body tr')) {
            event.preventDefault();
            flowError.textContent = 'Invoice periods could not be prepared. Please return to Guest & stay and confirm the check-in and checkout dates.';
            flowError.hidden = false;
        }
    });
    window.addEventListener('pageshow', () => {
        calculateBookingTotal();
        renderInvoiceSchedule();
    });
    showBookingStep(@json($errors->has('period_rents') ? 3 : ($errors->has('rent_amount') || $errors->has('dtcm_fee') || $errors->has('cleaning_fee') || $errors->has('agency_fee') || $errors->has('security_deposit') || $errors->has('vat_included') ? 2 : 1)));
</script>
@endsection
