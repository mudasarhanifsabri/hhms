<div class="modal fade booking-invoice-editor" id="correctInvoice{{ $invoice->id }}" tabindex="-1" aria-label="Edit invoice" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
<form method="POST" action="{{ route('admin.booking-invoice.correct',$invoice) }}" data-invoice-editor data-vat-scope="{{ $invoice->vat_scope }}">
    @csrf @method('PUT')
    <div class="modal-header"><h5 class="modal-title">Edit Invoice {{ $invoice->invoice_number }} <span class="badge bg-danger-subtle text-danger ms-2">{{ ucfirst($invoice->status) }}</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
        <p class="border-bottom pb-3"><strong>{{ $booking->guest_name }}</strong> <span class="text-muted">· {{ $booking->property?->building?->name }} — {{ $booking->property?->name }}</span></p>
        <label class="form-label" for="rentInput{{ $invoice->id }}">Rent amount entered (AED)</label>
        @php($taxableFeesVat = $invoice->vat_scope === 'rent_cleaning_agency' ? round(((float)(($invoice->fees ?? [])['Cleaning Fee'] ?? 0) + (float)(($invoice->fees ?? [])['Agency Fee'] ?? 0)) * (float)$invoice->vat_rate / 100, 2) : 0)
        <input id="rentInput{{ $invoice->id }}" name="rent_amount" type="number" min="0" step="0.01" value="{{ number_format((float)$invoice->rent_amount + ($invoice->vat_included ? max(0,(float)$invoice->vat_amount-$taxableFeesVat) : 0),2,'.','') }}" class="form-control mb-3" required>
        <div class="btn-group w-100 mb-2" role="group" aria-label="VAT treatment">
            <input type="radio" class="btn-check" name="vat_included" value="1" id="vatIn{{ $invoice->id }}" @checked($invoice->vat_included)><label class="btn btn-outline-primary" for="vatIn{{ $invoice->id }}">VAT Included</label>
            <input type="radio" class="btn-check" name="vat_included" value="0" id="vatAdd{{ $invoice->id }}" @checked(!$invoice->vat_included)><label class="btn btn-outline-primary" for="vatAdd{{ $invoice->id }}">Add VAT</label>
        </div>
        <input type="hidden" name="vat_rate" value="{{ $invoice->vat_rate }}">
        <small class="text-muted">VAT rate: {{ number_format((float)$invoice->vat_rate,2) }}%</small>
        <div class="table-responsive border rounded my-3">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Charge</th><th style="width:155px" class="text-end">Net Amount</th><th class="text-center">VAT</th><th class="text-end">VAT Amount</th><th class="text-end">Line Total</th></tr></thead>
                <tbody>
                    <tr><td>Rent</td><td class="text-end" data-preview="rent"></td><td class="text-center">{{ number_format((float)$invoice->vat_rate,2) }}%</td><td class="text-end" data-preview="rentVat"></td><td class="text-end fw-semibold" data-preview="rentTotal"></td></tr>
                    @forelse($invoice->fees ?? [] as $label=>$amount)
                        @php($feeTaxable = $invoice->vat_scope === 'rent_cleaning_agency' && in_array($label, ['Cleaning Fee','Agency Fee']))
                        <tr data-fee-row>
                            <td><label class="mb-0" for="charge{{ $invoice->id }}-{{ $loop->index }}">{{ $label==='Security Deposit'?'Refundable security deposit':$label }}</label></td>
                            <td><input id="charge{{ $invoice->id }}-{{ $loop->index }}" name="fees[{{ $label }}]" type="number" min="0" step="0.01" value="{{ $amount }}" class="form-control form-control-sm text-end" data-invoice-fee data-fee-label="{{ $label }}" required></td>
                            <td class="text-center {{ $feeTaxable ? '' : 'text-muted' }}">{{ $feeTaxable ? number_format((float)$invoice->vat_rate,2).'%' : 'No VAT' }}</td>
                            <td class="text-end" data-fee-vat>AED 0.00</td>
                            <td class="text-end fw-semibold" data-fee-total>AED 0.00</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No additional charges.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-semibold">
                    <tr><td colspan="3">Total VAT</td><td class="text-end" data-preview="vat"></td><td></td></tr>
                    <tr><td colspan="4">Invoice Total</td><td class="text-end" data-preview="total"></td></tr>
                </tfoot>
            </table>
        </div>
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
            const rentVat=included?round(entered-rent):round(rent*rate/100);
            let fees=0,taxableFeesVat=0;
            form.querySelectorAll('[data-invoice-fee]').forEach(input=>{
                const amount=round(Number(input.value)||0);
                const taxable=form.dataset.vatScope==='rent_cleaning_agency' && ['Cleaning Fee','Agency Fee'].includes(input.dataset.feeLabel);
                const feeVat=taxable?round(amount*rate/100):0;
                const row=input.closest('[data-fee-row]');
                fees=round(fees+amount);taxableFeesVat=round(taxableFeesVat+feeVat);
                row.querySelector('[data-fee-vat]').textContent=fmt(feeVat);
                row.querySelector('[data-fee-total]').textContent=fmt(amount+feeVat);
            });
            const vat=round(rentVat+taxableFeesVat);
            Object.entries({rent,rentVat,rentTotal:round(rent+rentVat),vat,total:round(rent+vat+fees)}).forEach(([key,value])=>form.querySelector('[data-preview="'+key+'"]').textContent=fmt(value));
        };
        form.addEventListener('input',calculate);form.addEventListener('change',calculate);calculate();
    });
});
</script>
@endpush
@endonce
