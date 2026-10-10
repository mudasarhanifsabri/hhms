<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#172033">
@php
    $owner = $entry->landlord;
    $property = $entry->property;
    $invoice = $entry->bookingInvoice;
    $companyPhone = \App\Support\AppSettings::get('company_phone', '0527687168');
    $companyEmail = \App\Support\AppSettings::get('company_email', 'customerservice@pattern.ae');
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f7;padding:28px 12px"><tr><td align="center">
<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="width:100%;max-width:640px;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 12px 38px rgba(3,41,79,.12)">
    <tr><td style="background:#03294f;padding:27px 32px;color:#fff">
        <img src="{{ asset('assets/images/pattern-bilingual-logo.png') }}" alt="PATTERN Vacation Homes Rental" style="display:block;width:210px;max-width:65%;height:auto;background:#fff;border-radius:8px;padding:8px;margin-bottom:22px">
        <div style="font-size:11px;letter-spacing:1.7px;text-transform:uppercase;color:#e3bd69;font-weight:700">Payment confirmation</div>
        <h1 style="margin:7px 0 4px;font-size:28px;line-height:1.25">Owner Payout Receipt</h1>
        <p style="margin:0;color:#c9d6e8;font-size:14px">Transfer reference: <strong style="color:#fff">{{ $entry->reference }}</strong></p>
    </td></tr>
    <tr><td style="padding:30px 32px">
        <p style="margin:0 0 12px;font-size:16px">Dear <strong>{{ $owner->name }}</strong>,</p>
        <p style="margin:0 0 23px;color:#566176;line-height:1.7">This email confirms that PATTERN Vacation Homes Rental recorded the following payout to your owner account.</p>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#edf9f3;border:1px solid #cfecdc;border-radius:14px;margin-bottom:22px">
            <tr><td style="padding:20px 22px"><div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#5e7d6d">Amount paid</div><div style="font-size:30px;font-weight:700;color:#128653;margin-top:5px">AED {{ number_format((float) $entry->amount, 2) }}</div><div style="color:#5e7d6d;font-size:13px;margin-top:5px">Paid on {{ $entry->entry_date->format('d M Y') }}</div></td></tr>
        </table>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #e3e8ef;border-radius:12px;overflow:hidden">
            <tr><td style="padding:13px 16px;background:#f7f9fc;color:#718096;border-bottom:1px solid #e3e8ef;width:40%">Transfer reference</td><td style="padding:13px 16px;border-bottom:1px solid #e3e8ef;font-weight:700;word-break:break-word">{{ $entry->reference }}</td></tr>
            <tr><td style="padding:13px 16px;background:#f7f9fc;color:#718096;border-bottom:1px solid #e3e8ef">Owner</td><td style="padding:13px 16px;border-bottom:1px solid #e3e8ef">{{ $owner->name }}</td></tr>
            <tr><td style="padding:13px 16px;background:#f7f9fc;color:#718096;border-bottom:1px solid #e3e8ef">Unit / Building</td><td style="padding:13px 16px;border-bottom:1px solid #e3e8ef">{{ $property?->name ?? 'General owner payout' }}@if($property?->building?->building_name) · {{ $property->building->building_name }}@endif</td></tr>
            @if($invoice)
            <tr><td style="padding:13px 16px;background:#f7f9fc;color:#718096;border-bottom:1px solid #e3e8ef">Booking period</td><td style="padding:13px 16px;border-bottom:1px solid #e3e8ef">{{ $invoice->period_from?->format('d M Y') }} – {{ $invoice->period_to?->format('d M Y') }}<br><span style="font-size:12px;color:#718096">{{ $invoice->invoice_number }}</span></td></tr>
            @endif
            <tr><td style="padding:13px 16px;background:#f7f9fc;color:#718096">Description</td><td style="padding:13px 16px">{{ $entry->description ?: 'Owner payout transfer' }}</td></tr>
        </table>

        <div style="margin-top:22px;padding:15px 17px;background:#f3f6fa;border-left:4px solid #d2a44f;border-radius:8px;color:#526176;font-size:13px;line-height:1.65">Please keep this email as your payout receipt. The transaction is also reflected in your owner account statement.</div>
        <p style="margin:24px 0 0;color:#566176;line-height:1.7">Kind regards,<br><strong style="color:#03294f">PATTERN Vacation Homes Rental</strong></p>
    </td></tr>
    <tr><td style="padding:18px 32px;background:#f7f9fc;border-top:1px solid #e7ebf1;text-align:center;color:#8490a2;font-size:11px;line-height:1.6">{{ $companyEmail }} · {{ $companyPhone }}<br>This is a system-generated payout receipt.</td></tr>
</table>
</td></tr></table>
</body>
</html>
