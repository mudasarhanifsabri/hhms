<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guest check-in notification</title>
</head>
<body style="margin:0;background:#f3f5f9;font-family:Arial,Helvetica,sans-serif;color:#24324a;">
@php
    $booking = $invoice->booking;
    $property = $booking?->property;
    $building = $property?->building;
    $documents = array_values(array_filter([
        filled($booking?->guest_document) ? 'Guest document' : null,
        filled($booking?->tenant?->id_document) ? 'Tenant ID - front' : null,
        filled($booking?->tenant?->id_document_back) ? 'Tenant ID - back' : null,
    ]));
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f9;padding:32px 12px;">
    <tr><td align="center">
        <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:100%;max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 28px rgba(27,39,70,.08);">
            <tr>
                <td style="padding:30px 36px;background:linear-gradient(135deg,#4438ca,#6d4aff);color:#ffffff;">
                    <div style="font-size:13px;letter-spacing:1.4px;text-transform:uppercase;opacity:.85;">Pattern Vacation Homes</div>
                    <h1 style="margin:10px 0 6px;font-size:26px;line-height:1.25;">Guest Check-In Notification</h1>
                    <div style="font-size:15px;opacity:.9;">Please register the guest and arrange building access.</div>
                </td>
            </tr>
            <tr>
                <td style="padding:30px 36px;">
                    <p style="margin:0 0 22px;font-size:15px;line-height:1.65;">Dear Management Team,</p>
                    <p style="margin:0 0 24px;font-size:15px;line-height:1.65;">Please register the guest below and allow check-in for the stated stay period. The available identification documents are attached for your building access and security records.</p>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#f7f7ff;border:1px solid #e5e3ff;border-radius:12px;">
                        <tr><td style="padding:20px 22px;">
                            <div style="font-size:12px;color:#746f91;text-transform:uppercase;letter-spacing:.8px;">Property</div>
                            <div style="margin-top:6px;font-size:18px;font-weight:700;color:#352d8d;">{{ $building?->building_name ?? 'Building not specified' }}</div>
                            <div style="margin-top:4px;font-size:14px;color:#555f73;">{{ $property?->name ?? 'Unit not specified' }}@if($building?->address) · {{ $building->address }}@endif</div>
                        </td></tr>
                    </table>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">
                        @foreach([
                            'Booking reference' => $booking?->booking_reference,
                            'Guest / tenant' => $booking?->guest_name ?: $booking?->tenant?->name,
                            'Email' => $booking?->guest_email ?: $booking?->tenant?->email,
                            'Phone' => $booking?->guest_phone ?: $booking?->tenant?->phone,
                            'Passport / ID number' => $booking?->guest_passport_id_no ?: $booking?->tenant?->eid_passport_no,
                            'Check-in' => $invoice->period_from?->format('d M Y') ?? '—',
                            'Check-out' => $invoice->period_to?->format('d M Y') ?? '—',
                        ] as $label => $value)
                            <tr>
                                <td style="width:42%;padding:10px 0;border-bottom:1px solid #edf0f5;color:#727b8d;">{{ $label }}</td>
                                <td style="padding:10px 0;border-bottom:1px solid #edf0f5;font-weight:600;color:#27334b;">{{ filled($value) ? $value : '—' }}</td>
                            </tr>
                        @endforeach
                    </table>

                    <div style="margin-top:24px;padding:17px 19px;border-radius:10px;background:#edf9f2;border-left:4px solid #33ae69;">
                        <div style="font-size:14px;font-weight:700;color:#237c4a;">Documents</div>
                        <div style="margin-top:5px;font-size:13px;line-height:1.6;color:#496457;">
                            {{ count($documents) ? implode(', ', $documents).' attached.' : 'No tenant documents were available to attach.' }}
                        </div>
                    </div>

                    <p style="margin:26px 0 0;font-size:13px;line-height:1.6;color:#7a8292;">This is an automated guest check-in notification from Pattern Vacation Homes.</p>
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html>
