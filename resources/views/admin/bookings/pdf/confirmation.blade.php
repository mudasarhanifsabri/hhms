@php
    $property = $booking->property;
    $building = $property?->building;
    $logo = public_path('assets/images/logo-dark.png');
    $stamp = public_path('assets/images/vacation-homes-rental-stamp.png');
    $checkInTime = $booking->check_in_time ? \Carbon\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00';
    $checkOutTime = $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00';
    $confirmationInvoice = $invoice ?? null;
    $confirmationFrom = $confirmationInvoice?->period_from ?? $booking->check_in;
    $confirmationTo = $confirmationInvoice?->period_to ?? $booking->check_out;
    $confirmationReference = $confirmationInvoice?->invoice_number ?? $booking->booking_reference;
    $documentDate = ($confirmationInvoice?->issue_date ?? $booking->created_at ?? now())->format('d-m-Y');
    $confirmationRent = (float) ($confirmationInvoice?->rent_amount ?? $booking->rent_amount);
    $confirmationVat = (float) ($confirmationInvoice?->vat_amount ?? $booking->vat_amount);
    $confirmationFees = $confirmationInvoice?->fees ?? [
        'DTCM Fee' => (float) $booking->dtcm_fee,
        'Cleaning Fee' => (float) $booking->cleaning_fee,
        'Agency Fee' => (float) $booking->agency_fee,
        'Security Deposit' => (float) $booking->security_deposit,
    ];
    $confirmationOtherFees = collect($confirmationFees)->except(['DTCM Fee', 'Cleaning Fee', 'Agency Fee', 'Security Deposit'])->sum();
    $confirmationTotal = (float) ($confirmationInvoice?->total_amount ?? $booking->total_amount);
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10.5px; margin: 0; }
        @page { margin: 0; }
        .page { padding: 22px 26px 38px; position: relative; }
        .header { position: relative; height: 92px; }
        .logo { width: 155px; }
        .title { position: absolute; top: 22px; left: 250px; font-size: 19px; font-weight: 700; }
        .ref { position: absolute; top: 18px; right: 15px; font-size: 12px; line-height: 1.35; }
        .main { width: 100%; border-collapse: collapse; }
        .main > tbody > tr > td { border: 0; padding: 0; vertical-align: top; }
        .left { width: 70%; padding-right: 22px !important; }
        .right { width: 30%; background: #eef0f2; padding: 14px 10px !important; }
        h3 { font-size: 13.5px; margin: 11px 0 7px; }
        table { width: 100%; border-collapse: collapse; }
        .details td { border-bottom: 1px solid #ddd; padding: 5px 18px; vertical-align: top; }
        .details td:first-child { width: 48%; }
        .details td:last-child { font-weight: 700; text-align: right; }
        .fees td:first-child { font-weight: 700; }
        .fees td:last-child { font-weight: 400; }
        .total td { font-weight: 700; border-bottom: 0; }
        .side-title { font-size: 13px; font-weight: 700; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin: 4px 0 8px; }
        .muted { color: #aaa; font-style: italic; }
        .small { font-size: 11px; }
        .service { margin-top: 175px; }
        .stamp { margin: 12px auto 0; width: 88px; height: 88px; border: 3px solid #1b75bb; color: #1b75bb; border-radius: 50px; text-align: center; font-weight: 700; font-size: 10px; padding-top: 17px; transform: rotate(-18deg); }
        .stamp-img { display: block; margin: 12px auto 0; width: 105px; height: 105px; object-fit: contain; }
        .signature { position: absolute; left: 26px; right: 26px; top: 875px; }
        .signature h3 { margin-bottom: 10px; }
        .signature-line { margin: 18px auto 7px; width: 380px; border-top: 1px solid #ddd; text-align: center; color: #bbb; padding-top: 4px; }
        .sign-name { text-align: center; font-size: 15px; }
        .sign-date { text-align: center; font-size: 14px; margin-top: 8px; }
        .footer { position: fixed; left: 0; right: 0; bottom: 0; height: 46px; background: #111; color: #fff; font-size: 12px; padding: 8px 12px; }
        .footer div { display: inline-block; width: 32%; text-align: center; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        @if(file_exists($logo))
            <img src="{{ $logo }}" class="logo" alt="Pattern">
        @endif
        <div class="title">Booking Confirmation</div>
        <div class="ref">Ref no. {{ $confirmationReference }}<br>Booking: {{ $booking->booking_reference }}<br>Date: {{ $documentDate }}</div>
    </div>

    <table class="main">
        <tr>
            <td class="left">
        <h3>Guest's Details</h3>
        <table class="details">
            <tr><td>Guest Name</td><td>{{ $booking->guest_name }}</td></tr>
            <tr><td>Contact no</td><td>{{ $booking->guest_phone }}</td></tr>
            <tr><td>Email Address</td><td>{{ $booking->guest_email }}</td></tr>
        </table>

        <h3>Property Information</h3>
        <table class="details">
            <tr><td>Property</td><td>{{ $property?->name ?? 'N/A' }}</td></tr>
            <tr><td>Type</td><td>{{ $property?->category ?? 'N/A' }}</td></tr>
            <tr><td>Floor No</td><td>{{ $property?->unit_floor_label ?? $property?->floor ?? 'N/A' }}</td></tr>
            <tr><td>No. Room</td><td>{{ $property?->room_no ?? ($property?->bedrooms ? $property->bedrooms . ' Bedroom' : 'N/A') }}</td></tr>
            <tr><td>Community</td><td>{{ $property?->community ?? $building?->city ?? $building?->address ?? 'N/A' }}</td></tr>
        </table>

        <h3>Reservation Details</h3>
        <table class="details">
            <tr><td>Confirmation type</td><td>{{ $confirmationInvoice?->type_label ?? 'Original Booking' }}</td></tr>
            <tr><td>Check-in date</td><td>{{ $confirmationFrom?->format('d-m-Y') }}</td></tr>
            <tr><td>Check-in time</td><td>{{ $checkInTime }}</td></tr>
            <tr><td>Check-out date</td><td>{{ $confirmationTo?->format('d-m-Y') }}</td></tr>
            <tr><td>Check-out time</td><td>{{ $checkOutTime }}</td></tr>
        </table>

        <h3>Fees & Charges</h3>
        <table class="details fees">
            <tr><td>Reservation Fee</td><td>{{ number_format($confirmationRent, 2) }}</td></tr>
            <tr><td>Housekeeping</td><td>{{ number_format((float) ($confirmationFees['Cleaning Fee'] ?? 0), 2) }}</td></tr>
            <tr><td>Tourism Fee</td><td>{{ number_format((float) ($confirmationFees['DTCM Fee'] ?? 0), 2) }}</td></tr>
            <tr><td>VAT</td><td>{{ number_format($confirmationVat, 2) }}</td></tr>
            <tr><td>Security Deposit</td><td>{{ number_format((float) ($confirmationFees['Security Deposit'] ?? 0), 2) }}</td></tr>
            <tr><td>Agency Commission</td><td>{{ number_format((float) ($confirmationFees['Agency Fee'] ?? 0), 2) }}</td></tr>
            <tr><td>Additional Service</td><td>{{ number_format((float) $confirmationOtherFees, 2) }}</td></tr>
            <tr class="total"><td></td><td>Total {{ number_format($confirmationTotal, 2) }}</td></tr>
        </table>
            </td>

            <td class="right">
        <div class="side-title">Additional Info</div>
        <p><strong>Utilities Cap</strong>&nbsp;&nbsp;&nbsp; AED {{ number_format((float) ($property?->utilities_cap ?? 0), 2) }} / Month</p>
        <p class="muted small">- Electricity & water<br>- A/C<br>- Gas</p>
        <hr>
        <p>WIFI&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $property?->wifi_name ?? $property?->wifi_provider ?? 'N/A' }}</p>
        <p>WIFIPassword: {{ $property?->wifi_password ?? $property?->wifi_account_no ?? 'N/A' }}</p>
        <div class="side-title">Required Documents</div>
        <p>&bull; Valid ID Document<br><span class="muted">&nbsp;&nbsp;&nbsp;&nbsp;Passport / Emirates ID</span></p>
        <p>Parking Number: {{ $property?->parking_number ?? 'N/A' }}</p>

        <div class="service">
            <div class="side-title">Customer Service<br>97143299693</div>
            <p>customerservice@pattern.ae</p>
            <p style="margin-top:45px;">Prepared By, {{ $booking->agent?->name ?? 'Admin' }}</p>
            @if(file_exists($stamp))
                <img src="{{ $stamp }}" class="stamp-img" alt="Stamp">
            @else
                <div class="stamp">PATTERN<br>VACATION<br>HOMES</div>
            @endif
        </div>
            </td>
        </tr>
    </table>

    <div class="signature">
        <h3>SIGNATURE</h3>
        <p style="text-align:center;">By signing this I certify that I have read and accepted the <strong><u>Terms & Conditions</u></strong> for my reservation</p>
        <div class="signature-line">Signature</div>
        <div class="sign-name">{{ $booking->guest_name }}</div>
        <div class="sign-date">{{ $documentDate }}</div>
    </div>
</div>
<div class="footer">
    <div>pattern.ae</div>
    <div>customerservice@pattern.ae</div>
    <div>+971 (4) 329 96 93</div>
</div>
</body>
</html>
