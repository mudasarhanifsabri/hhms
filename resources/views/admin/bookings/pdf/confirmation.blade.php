@php
    $property = $booking->property;
    $building = $property?->building;
    $logo = public_path('assets/images/pattern-bilingual-logo.png');
    $stamp = public_path('assets/images/vacation-homes-rental-stamp.png');
    $confirmationInvoice = $invoice ?? null;
    $invoices = $confirmationInvoice ? collect([$confirmationInvoice->loadMissing('payments.bankAccount')]) : $booking->invoices()->with('payments.bankAccount')->orderBy('period_from')->get();
    $confirmationFrom = $confirmationInvoice?->period_from ?? ($invoices->min('period_from') ?: $booking->check_in);
    $confirmationTo = $confirmationInvoice?->period_to ?? ($invoices->max('period_to') ?: $booking->check_out);
    $confirmationReference = $confirmationInvoice?->invoice_number ?? $booking->booking_reference;
    $documentDate = ($confirmationInvoice?->issue_date ?? now())->format('d M Y');
    $charges = [
        'Rent' => (float) ($confirmationInvoice?->rent_amount ?? ($invoices->isNotEmpty() ? $invoices->sum('rent_amount') : $booking->rent_amount)),
        'VAT' => (float) ($confirmationInvoice?->vat_amount ?? ($invoices->isNotEmpty() ? $invoices->sum('vat_amount') : $booking->vat_amount)),
        'Tourism Fee' => 0, 'Cleaning Fee' => 0, 'Agency Fee' => 0, 'Security Deposit' => 0, 'Other Services' => 0,
    ];
    $feeInvoices = $confirmationInvoice ? collect([$confirmationInvoice]) : $invoices;
    foreach ($feeInvoices as $feeInvoice) {
        foreach ($feeInvoice->fees ?? [] as $label => $amount) {
            $key = in_array($label, ['DTCM Fee','Tourism Fee']) ? 'Tourism Fee' : (array_key_exists($label, $charges) ? $label : 'Other Services');
            $charges[$key] += (float) $amount;
        }
    }
    if ($invoices->isEmpty()) {
        $charges['Tourism Fee']=(float)$booking->dtcm_fee; $charges['Cleaning Fee']=(float)$booking->cleaning_fee;
        $charges['Agency Fee']=(float)$booking->agency_fee; $charges['Security Deposit']=(float)$booking->security_deposit;
    }
    $total = $invoices->isNotEmpty() ? (float)$invoices->sum('total_amount') : (float)$booking->total_amount;
    $paid = $invoices->isNotEmpty() ? (float)$invoices->sum(fn($item) => $item->paid_amount) : $total;
    $isPaid = $total > 0 && round($paid, 2) >= round($total, 2);
    $payments = $invoices->flatMap(fn($item) => $item->payments)->sortBy('payment_date');
    $checkInTime = $booking->check_in_time ? \Carbon\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00';
    $checkOutTime = $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00';
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:10mm 11mm 13mm;footer:html_footer}body{font-family:DejaVu Sans,Arial,sans-serif;color:#18243a;font-size:9.5px}.top{border-bottom:3px solid #102f62;padding-bottom:8px}.logo{width:205px;height:auto}.doc{font-size:19px;font-weight:bold;color:#102f62;text-align:right;line-height:1.15}.doc-sub{font-size:8px;color:#b68b43;letter-spacing:1px}.hero{background:#102f62;color:#fff;padding:13px 15px;margin:12px 0}.hero table,.hero td{border:0;color:#fff;margin:0}.hero .guest{font-size:17px;font-weight:bold}.paid{background:#dff7e8;color:#157347;padding:4px 9px;font-weight:bold}.grid{width:100%;border-collapse:collapse;margin-bottom:11px}.grid th,.grid td{border:1px solid #d8dfeb;padding:6px;vertical-align:top}.grid th{background:#edf2f8;color:#263d61;text-align:left}.label{font-size:8px;text-transform:uppercase;color:#718096;letter-spacing:.5px}.value{font-size:11px;font-weight:bold;margin-top:2px}.section{font-size:13px;color:#102f62;border-bottom:2px solid #c39a53;padding-bottom:4px;margin:12px 0 7px}.charges td:last-child,.charges th:last-child{text-align:right}.total td{font-size:12px;font-weight:bold;background:#f5f7fb}.summary td{text-align:center;width:33.33%;padding:9px}.summary strong{font-size:14px;color:#102f62}.notice{background:#f4f7fb;border-left:3px solid #c39a53;padding:8px;margin-top:9px}.terms{font-size:8.5px;color:#556176}.signature{margin-top:13px}.sign-box{height:36px;border-bottom:1px solid #9ca6b7}.footer{text-align:center;color:#7b8494;border-top:1px solid #d9dfeb;padding-top:4px;font-size:8px}.muted{color:#68758a}.right{text-align:right}
</style></head><body>
<htmlpagefooter name="footer"><div class="footer">PATTERN Vacation Homes Rental | {{ $confirmationReference }} | Generated {{ now()->format('d M Y H:i') }} | Page {PAGENO} of {nbpg}</div></htmlpagefooter>
<table class="top" width="100%"><tr><td width="58%">@if(file_exists($logo))<img class="logo" src="{{ $logo }}">@endif</td><td width="42%" class="doc">BOOKING CONFIRMATION<br><span class="doc-sub">{{ strtoupper($confirmationInvoice?->type_label ?? 'COMPLETE RESERVATION') }}</span></td></tr></table>
<div class="hero"><table width="100%"><tr><td><div class="guest">{{ $booking->guest_name }}</div><div>{{ $building?->name ?? 'Property' }} - Unit {{ $property?->name ?? '-' }}</div></td><td class="right"><span class="paid">{{ $isPaid ? 'PAID & CONFIRMED' : 'PAYMENT PENDING' }}</span><br><br>{{ $confirmationReference }}</td></tr></table></div>
<table class="grid"><tr><td><div class="label">Booking reference</div><div class="value">{{ $booking->booking_reference }}</div></td><td><div class="label">Document date</div><div class="value">{{ $documentDate }}</div></td><td><div class="label">Stay period</div><div class="value">{{ $confirmationFrom?->format('d M Y') }} - {{ $confirmationTo?->format('d M Y') }}</div></td><td><div class="label">Duration</div><div class="value">{{ $confirmationFrom && $confirmationTo ? $confirmationFrom->diffInDays($confirmationTo) : $booking->nights }} nights</div></td></tr></table>
<div class="section">Guest & Property</div><table class="grid"><tr><th>Guest</th><td>{{ $booking->guest_name }}</td><th>Phone</th><td>{{ $booking->guest_phone }}</td></tr><tr><th>Email</th><td>{{ $booking->guest_email }}</td><th>Passport / ID</th><td>{{ $booking->guest_passport_id_no ?: 'Not recorded' }}</td></tr><tr><th>Building</th><td>{{ $building?->name ?? '-' }}</td><th>Unit / Type</th><td>{{ $property?->name ?? '-' }} / {{ $property?->category ?? '-' }}</td></tr><tr><th>Community</th><td>{{ $property?->community ?? $building?->city ?? '-' }}</td><th>Parking</th><td>{{ $property?->parking_number ?? 'N/A' }}</td></tr></table>
<div class="section">Arrival & Departure</div><table class="grid"><tr><td><div class="label">Check-in</div><div class="value">{{ $confirmationFrom?->format('l, d M Y') }} at {{ $checkInTime }}</div></td><td><div class="label">Check-out</div><div class="value">{{ $confirmationTo?->format('l, d M Y') }} at {{ $checkOutTime }}</div></td><td><div class="label">Prepared by</div><div class="value">{{ $booking->agent?->name ?? 'PATTERN Reservations' }}</div></td></tr></table>
<div class="section">Confirmed Charges</div><table class="grid charges"><thead><tr><th>Description</th><th>AED</th></tr></thead><tbody>@foreach($charges as $label=>$amount)@if(abs($amount)>.004)<tr><td>{{ $label }}@if($label==='Security Deposit') <span class="muted">(company-held refundable amount)</span>@endif</td><td>{{ number_format($amount,2) }}</td></tr>@endif @endforeach<tr class="total"><td>Total confirmed</td><td>AED {{ number_format($total,2) }}</td></tr></tbody></table>
<table class="grid summary"><tr><td><span class="label">Total confirmed</span><br><strong>AED {{ number_format($total,2) }}</strong></td><td><span class="label">Payments received</span><br><strong>AED {{ number_format($paid,2) }}</strong></td><td><span class="label">Balance</span><br><strong>AED {{ number_format(max(0,$total-$paid),2) }}</strong></td></tr></table>
@if($payments->isNotEmpty())<div class="section">Payment References</div><table class="grid"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="right">Amount</th></tr></thead><tbody>@foreach($payments as $payment)<tr><td>{{ $payment->payment_date?->format('d M Y') }}</td><td>{{ $payment->payment_method }}</td><td>{{ $payment->reference ?: '-' }}@if($payment->payment_batch_id) <span class="muted">(combined transfer)</span>@endif</td><td class="right">AED {{ number_format((float)$payment->amount,2) }}</td></tr>@endforeach</tbody></table>@endif
<div class="notice"><strong>Important stay information</strong><br>Utilities allowance: AED {{ number_format((float)($property?->utilities_cap ?? 0),2) }} per month. Wi-Fi: {{ $property?->wifi_name ?? $property?->wifi_provider ?? 'Ask reservations' }}. Customer service: +971 (4) 329 96 93 | customerservice@pattern.ae</div>
<p class="terms">This confirmation is valid for the exact period and charges stated above. Security deposits remain separate from rent and are subject to the documented inspection and refund process. Guest identification and compliance documents may be required before check-in.</p>
<table class="signature" width="100%"><tr><td width="46%"><strong>Guest acknowledgement</strong><div class="sign-box"></div>{{ $booking->guest_name }}</td><td width="8%"></td><td width="46%"><strong>For PATTERN Vacation Homes Rental</strong><div class="sign-box"></div>@if(file_exists($stamp))<span class="muted">Authorized confirmation</span>@else<span class="muted">System-generated confirmation</span>@endif</td></tr></table>
</body></html>
