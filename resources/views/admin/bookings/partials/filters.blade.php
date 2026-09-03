<form method="GET" action="{{ url()->current() }}" class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-xl-4 col-md-6"><label for="bookingSearch" class="form-label">Search bookings</label><input id="bookingSearch" name="search" value="{{ request('search') }}" class="form-control" maxlength="200" placeholder="Booking, invoice, guest, unit, building or agent"></div>
            <div class="col-xl-2 col-md-3"><label for="bookingStatus" class="form-label">Booking status</label><select id="bookingStatus" name="status" class="form-select"><option value="">All statuses</option>@foreach(['confirmed'=>'Confirmed','checked_in'=>'Checked In','checked_out'=>'Checked Out'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-xl-2 col-md-3"><label for="invoiceStatus" class="form-label">Booking payment status</label><select id="invoiceStatus" name="invoice_status" class="form-select"><option value="">All payments</option>@foreach(['unpaid'=>'Unpaid','partial'=>'Partial','paid'=>'Paid'] as $value=>$label)<option value="{{ $value }}" @selected(request('invoice_status')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-xl-2 col-md-4"><label for="bookingFrom" class="form-label">Check-in from</label><input id="bookingFrom" type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-xl-2 col-md-4"><label for="bookingTo" class="form-label">Check-in to</label><input id="bookingTo" type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-2"><label for="bookingPerPage" class="form-label">Per page</label><select id="bookingPerPage" name="per_page" class="form-select">@foreach([10,12,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',12)===$size)>{{ $size }}</option>@endforeach</select></div>
            <div class="col-md-10 d-flex gap-2 align-items-center flex-wrap"><button class="btn btn-primary">Apply Filters</button><a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a><span class="small text-muted ms-md-auto">Showing {{ $bookings->firstItem() ?? 0 }}–{{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }} matching bookings</span></div>
        </div>
    </div>
</form>
