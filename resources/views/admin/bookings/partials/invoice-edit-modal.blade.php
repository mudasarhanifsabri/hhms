<div class="modal fade booking-invoice-editor" id="correctInvoice{{ $invoice->id }}" tabindex="-1" aria-label="Edit invoice" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
<form method="POST" action="{{ route('admin.booking-invoice.correct',$invoice) }}" data-invoice-editor>
    @csrf @method('PUT')
    <div class="modal-header"><h5 class="modal-title">Edit Invoice {{ $invoice->invoice_number }} <span class="badge bg-danger-subtle text-danger ms-2">{{ ucfirst($invoice->status) }}</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
        <p class="border-bottom pb-3"><strong>{{ $booking->guest_name }}</strong> <span class="text-muted">· {{ $booking->property?->building?->name }} — {{ $booking->property?->name }}</span></p>
        <label class="form-label" for="rentInput{{ $invoice->id }}">Rent amount entered (AED)</label>
        <input id="rentInput{{ $invoice->id }}" name="rent_amount" type="number" min="0" step="0.01" value="{{ number_format((float)$invoice->rent_amount + ($invoice->vat_included ? (float)$invoice->vat_amount : 0),2,'.','') }}" class="form-control mb-3" required>
        <div class="btn-group w-100 mb-2" role="group" aria-label="VAT treatment">
            <input type="radio" class="btn-check" name="vat_included" value="1" id="vatIn{{ $invoice->id }}" @checked($invoice->vat_included)><label class="btn btn-outline-primary" for="vatIn{{ $invoice->id }}">VAT Included</label>
            <input type="radio" class="btn-check" name="vat_included" value="0" id="vatAdd{{ $invoice->id }}" @checked(!$invoice->vat_included)><label class="btn btn-outline-primary" for="vatAdd{{ $invoice->id }}">Add VAT</label>
        </div>
        <input type="hidden" name="vat_rate" value="{{ $invoice->vat_rate }}">
        <small class="text-muted">VAT rate: {{ number_format((float)$invoice->vat_rate,2) }}%</small>
        <div class="invoice-breakdown mt-2">
            <div><span>Rent excluding VAT</span><strong data-preview="rent"></strong></div>
            <div><span>VAT {{ number_format((float)$invoice->vat_rate,2) }}%</span><strong data-preview="vat"></strong></div>
            <div class="fw-semibold"><span>Rent including VAT</span><strong data-preview="grossRent"></strong></div>
        </div>
        <div class="border rounded p-3 my-3"><h6>Other invoice charges</h6>
            @forelse($invoice->fees ?? [] as $label=>$amount)
            <div class="d-flex align-items-center justify-content-between gap-3 mt-2"><label class="small" for="charge{{ $invoice->id }}-{{ $loop->index }}">{{ $label==='Security Deposit'?'Refundable security deposit':$label }}</label><input id="charge{{ $invoice->id }}-{{ $loop->index }}" name="fees[{{ $label }}]" type="number" min="0" step="0.01" value="{{ $amount }}" class="form-control form-control-sm text-end" style="max-width:150px" data-invoice-fee required></div>
            @empty<p class="small text-muted mb-0">No additional charges.</p>@endforelse
        </div>
        <div class="d-flex justify-content-between fs-5 fw-semibold mb-3"><span>Invoice total</span><strong data-preview="total"></strong></div>
        <div class="alert alert-info py-2 small">Enter rent only here. Deposit and other fees are separate.</div>
        <label class="form-label" for="invoiceReason{{ $invoice->id }}">Reason for correction</label><textarea id="invoiceReason{{ $invoice->id }}" name="reason" class="form-control" rows="2" minlength="5" maxlength="1000" placeholder="Explain the change" required></textarea>
        <p class="small text-muted mt-3 mb-0">Saving an invoice does not record payment. Use Record Payment for money received.</p>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Invoice</button></div>
</form></div></div></div>
@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('[data-invoice-editor]').forEach(form=>{
        const round=n=>Math.round((n+Number.EPSILON)*100)/100;
        const fmt=n=>'AED '+n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
        const calculate=()=>{
            const entered=round(Number(form.elements.rent_amount.value)||0), rate=Number(form.elements.vat_rate.value)||0;
            const included=form.querySelector('[name="vat_included"]:checked').value==='1';
            const rent=included?round(entered/(1+rate/100)):entered;
            const vat=included?round(entered-rent):round(rent*rate/100);
            let fees=0;form.querySelectorAll('[data-invoice-fee]').forEach(input=>fees+=Number(input.value)||0);
            Object.entries({rent,vat,grossRent:round(rent+vat),total:round(rent+vat+fees)}).forEach(([key,value])=>form.querySelector('[data-preview="'+key+'"]').textContent=fmt(value));
        };
        form.addEventListener('input',calculate);form.addEventListener('change',calculate);calculate();
    });
});
</script>
@endpush
@endonce
