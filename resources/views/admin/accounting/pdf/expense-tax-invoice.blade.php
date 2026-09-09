@php
    $company=\App\Support\AppSettings::get('invoice_legal_name','PATTERN Vacation Homes Rental');
    $trn=\App\Support\AppSettings::get('invoice_trn','101001557300003');
    $logo=public_path('assets/images/pattern-bilingual-logo.png');
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:15mm}body{font-family:DejaVu Sans,Arial;color:#172033;font-size:11px}.head,.totals{width:100%;border-collapse:collapse}.logo{width:190px}.right{text-align:right}.title{font-size:24px;font-weight:bold;margin:25px 0 12px;border-left:5px solid #5b45e8;padding-left:10px}.box{background:#f4f6fa;padding:12px;margin:10px 0}.items{width:100%;border-collapse:collapse;margin-top:20px}.items th{background:#082f55;color:#fff;padding:9px}.items td{border:1px solid #d9dfeb;padding:9px}.totals{margin-top:15px;width:45%;margin-left:auto}.totals td{padding:7px;border-bottom:1px solid #ddd}.grand{font-size:14px;font-weight:bold}.muted{color:#6f7b8f}</style></head><body>
<table class="head"><tr><td>@if(file_exists($logo))<img class="logo" src="{{ $logo }}">@endif</td><td class="right"><strong>{{ $company }}</strong><br>Dubai, U.A.E.<br>TRN {{ $trn }}</td></tr></table>
<div class="title">TAX INVOICE</div>
<div class="box"><strong>Invoice:</strong> TI-{{ $expense->expense_no }} &nbsp; <strong>Date:</strong> {{ $expense->expense_date?->format('d M Y') }}<br><strong>Bill To:</strong> {{ $expense->landlord?->name ?? $expense->booking?->guest_name ?? ucfirst(str_replace('_',' ',$expense->responsibility)) }}<br><span class="muted">{{ $expense->property?->name }} {{ $expense->property?->building?->building_name }}</span></div>
<table class="items"><thead><tr><th>Description</th><th class="right">Amount</th></tr></thead><tbody><tr><td>{{ $expense->description ?: ucfirst($expense->category).' service/recovery' }}</td><td class="right">AED {{ number_format((float)$expense->sale_net_amount,2) }}</td></tr></tbody></table>
<table class="totals"><tr><td>Subtotal</td><td class="right">AED {{ number_format((float)$expense->sale_net_amount,2) }}</td></tr><tr><td>VAT {{ number_format((float)$expense->sale_vat_rate,2) }}%</td><td class="right">AED {{ number_format((float)$expense->sale_vat_amount,2) }}</td></tr><tr class="grand"><td>Total</td><td class="right">AED {{ number_format((float)$expense->sale_gross_amount,2) }}</td></tr></table>
</body></html>
