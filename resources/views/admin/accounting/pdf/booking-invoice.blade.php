<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#111827;font-size:12px}
        .top{border-bottom:3px solid #0f2f6b;padding-bottom:12px;margin-bottom:18px}
        .brand{font-size:22px;font-weight:700;color:#0f2f6b}
        .muted{color:#6b7280}
        table{width:100%;border-collapse:collapse;margin-bottom:16px}
        th,td{border:1px solid #d8dee9;padding:8px}
        th{background:#eef2f7;text-align:left}
        .right{text-align:right}
        .badge{display:inline-block;padding:5px 10px;background:#eef2ff;color:#3730a3;border-radius:4px}
        .total td{font-weight:700;background:#f9fafb}
    </style>
</head>
<body>
    @php($booking = $invoice->booking)
    <div class="top">
        <div class="brand">{{ \App\Support\AppSettings::get('invoice_establishment_name', 'PATTERN Vacation Homes Rental') }}</div>
        <div>{{ \App\Support\AppSettings::get('invoice_legal_name') }}</div>
        <div>{{ \App\Support\AppSettings::get('invoice_address') }}</div>
        <div>TRN: {{ \App\Support\AppSettings::get('invoice_trn') }}</div>
        <div class="muted">{{ $invoice->type_label }} Invoice</div>
    </div>

    <table>
        <tr>
            <td>
                <strong>Invoice No.</strong><br>{{ $invoice->invoice_number }}<br><br>
                <strong>Issue Date</strong><br>{{ $invoice->issue_date?->format('d M Y') }}
            </td>
            <td>
                <strong>Booking</strong><br>{{ $booking?->booking_reference }}<br><br>
                <strong>Status</strong><br><span class="badge">{{ ucfirst($invoice->status) }}</span>
            </td>
            <td>
                <strong>Guest / Tenant</strong><br>{{ $booking?->guest_name }}<br>{{ $booking?->guest_email }}<br>{{ $booking?->guest_phone }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td><strong>Unit</strong><br>{{ $booking?->property?->name }}<br>{{ $booking?->property?->building?->name }}</td>
            <td><strong>Stay / Invoice Period</strong><br>{{ $invoice->period_from?->format('d M Y') }} to {{ $invoice->period_to?->format('d M Y') }}</td>
            <td><strong>Agent</strong><br>{{ $booking?->agent?->name ?? '-' }}</td>
        </tr>
    </table>

    <table>
        <thead><tr><th>Description</th><th class="right">Amount</th></tr></thead>
        <tbody>
            <tr><td>Rent Amount</td><td class="right">AED {{ number_format((float) $invoice->rent_amount, 2) }}</td></tr>
            <tr><td>VAT {{ number_format((float) $invoice->vat_rate, 2) }}%</td><td class="right">AED {{ number_format((float) $invoice->vat_amount, 2) }}</td></tr>
            @foreach(($invoice->fees ?? []) as $label => $amount)
                @if((float) $amount > 0)
                    <tr><td>{{ $label }}</td><td class="right">AED {{ number_format((float) $amount, 2) }}</td></tr>
                @endif
            @endforeach
            <tr class="total"><td>Total Invoice Amount</td><td class="right">AED {{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
            <tr><td>Payments Received</td><td class="right">AED {{ number_format($invoice->paid_amount, 2) }}</td></tr>
            <tr class="total"><td>Balance Due</td><td class="right">AED {{ number_format($invoice->balance_due, 2) }}</td></tr>
        </tbody>
    </table>

    @if($invoice->notes)
        <p><strong>Notes:</strong> {{ $invoice->notes }}</p>
    @endif
</body>
</html>
