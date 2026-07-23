@php
    $property = $booking->property;
    $building = $property?->building;
    $logo = public_path('assets/images/logo-dark.png');
    $documentDate = ($booking->created_at ?? now())->format('d-m-Y');
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; margin: 0; }
        .page { padding: 28px 34px 55px; }
        .header { position: relative; height: 95px; border-bottom: 2px solid #111; }
        .logo { width: 165px; }
        .title { position: absolute; top: 18px; right: 0; text-align: right; }
        .title h1 { margin: 0; font-size: 24px; }
        .box { margin-top: 20px; }
        .cols { width: 100%; }
        .cols td { border: 0; padding: 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 9px; }
        th { background: #f0f2f4; text-align: left; }
        .right { text-align: right; }
        .total th { font-size: 15px; }
        .proof { margin-top: 18px; padding: 12px; background: #eef0f2; }
        .footer { position: fixed; left: 0; right: 0; bottom: 0; height: 42px; background: #111; color: #fff; font-size: 12px; padding: 8px 12px; }
        .footer div { display: inline-block; width: 32%; text-align: center; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        @if(file_exists($logo))
            <img src="{{ $logo }}" class="logo" alt="Pattern">
        @endif
        <div class="title">
            <h1>Invoice</h1>
            <p>{{ $booking->invoice_number }}<br>{{ $documentDate }}</p>
        </div>
    </div>

    <div class="box">
        <table class="cols">
            <tr>
                <td style="width:55%;">
                    <strong>Guest</strong><br>
                    {{ $booking->guest_name }}<br>
                    {{ $booking->guest_email }}<br>
                    {{ $booking->guest_phone }}<br>
                    Passport/ID: {{ $booking->guest_passport_id_no }}
                </td>
                <td>
                    <strong>Booking</strong><br>
                    Ref no. {{ $booking->booking_reference }}<br>
                    Unit: {{ $property?->name ?? 'N/A' }}<br>
                    Community: {{ $property?->community ?? $building?->city ?? $building?->address ?? 'N/A' }}<br>
                    Prepared By: {{ $booking->agent?->name ?? 'Admin' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <table>
            <thead>
                <tr><th>Description</th><th class="right">Amount</th></tr>
            </thead>
            <tbody>
                <tr><td>Reservation Fee</td><td class="right">{{ number_format((float) $booking->rent_amount, 2) }} AED</td></tr>
                <tr><td>VAT 5% {{ $booking->vat_included ? '(separated from rent)' : '' }}</td><td class="right">{{ number_format((float) $booking->vat_amount, 2) }} AED</td></tr>
                <tr><td>Tourism Fee</td><td class="right">{{ number_format((float) $booking->dtcm_fee, 2) }} AED</td></tr>
                <tr><td>Housekeeping</td><td class="right">{{ number_format((float) $booking->cleaning_fee, 2) }} AED</td></tr>
                <tr><td>Agency Commission</td><td class="right">{{ number_format((float) $booking->agency_fee, 2) }} AED</td></tr>
                <tr><td>Security Deposit</td><td class="right">{{ number_format((float) $booking->security_deposit, 2) }} AED</td></tr>
                <tr class="total"><th>Total</th><th class="right">{{ number_format((float) $booking->total_amount, 2) }} AED</th></tr>
            </tbody>
        </table>
    </div>

    <div class="proof">
        <strong>Invoice Status:</strong> {{ ucfirst($booking->invoice_status) }}<br>
        <strong>Payment Proof:</strong> {{ $booking->payment_proof ? 'Attached - ' . $booking->payment_proof : 'Not attached yet' }}
    </div>
</div>
<div class="footer">
    <div>pattern.ae</div>
    <div>customerservice@pattern.ae</div>
    <div>+971 (4) 329 96 93</div>
</div>
</body>
</html>
