<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#eef2f7;font-family:Arial,sans-serif;color:#172033">
@php
    $company = \App\Support\AppSettings::get('invoice_legal_name', 'PATTERN Vacation Homes Rental');
    $recipientName = $expense->landlord?->name ?? $expense->booking?->guest_name ?? 'Valued Customer';
    $unit = trim(($expense->property?->name ?? '').($expense->property?->building ? ' — '.($expense->property->building->building_name ?? $expense->property->building->name) : ''));
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f7;padding:28px 12px"><tr><td align="center">
<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 12px 38px rgba(3,41,79,.12)">
    <tr><td style="background:linear-gradient(135deg,#001d3d,#073b6d);padding:26px 32px;color:#fff">
        <img src="{{ asset('assets/images/pattern-bilingual-logo.png') }}" alt="PATTERN Vacation Homes Rental" style="display:block;width:210px;max-width:65%;height:auto;background:#fff;border-radius:8px;padding:8px;margin-bottom:22px">
        <div style="font-size:12px;letter-spacing:1.5px;color:#d2a44f;text-transform:uppercase;font-weight:bold">Official Tax Document</div>
        <h1 style="margin:8px 0 4px;font-size:27px">Tax Invoice TI-{{ $expense->expense_no }}</h1>
        <p style="margin:0;color:#c9d6e8;font-size:14px">Issued {{ $expense->expense_date?->format('d M Y') }}</p>
    </td></tr>
    <tr><td style="padding:30px 32px">
        <p style="margin:0 0 14px;font-size:16px">Dear <strong>{{ $recipientName }}</strong>,</p>
        <p style="margin:0 0 20px;color:#566176;line-height:1.7">Please find the detailed tax invoice attached as a PDF for your records.</p>
        @if($customMessage)<div style="background:#f7f9fc;border-left:4px solid #d2a44f;border-radius:8px;padding:15px 17px;margin-bottom:22px;color:#3e485a;line-height:1.65">{!! nl2br(e($customMessage)) !!}</div>@endif
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:22px">
            <tr><td style="padding:12px 15px;background:#f7f9fc;color:#718096;width:35%">Description</td><td style="padding:12px 15px;font-weight:bold">{{ $expense->description ?: ucfirst($expense->category).' service/recovery' }}</td></tr>
            @if($unit)<tr><td style="padding:12px 15px;background:#f7f9fc;color:#718096">Unit / Building</td><td style="padding:12px 15px">{{ $unit }}</td></tr>@endif
            <tr><td style="padding:12px 15px;background:#f7f9fc;color:#718096">Subtotal</td><td style="padding:12px 15px">AED {{ number_format((float)$expense->sale_net_amount,2) }}</td></tr>
            <tr><td style="padding:12px 15px;background:#f7f9fc;color:#718096">VAT {{ number_format((float)$expense->sale_vat_rate,2) }}%</td><td style="padding:12px 15px">AED {{ number_format((float)$expense->sale_vat_amount,2) }}</td></tr>
            <tr><td style="padding:14px 15px;background:#edf4fb;color:#03294f;font-weight:bold">Total</td><td style="padding:14px 15px;background:#edf4fb;color:#03294f;font-size:19px;font-weight:bold">AED {{ number_format((float)$expense->sale_gross_amount,2) }}</td></tr>
        </table>
        <div style="background:#edf4fb;border-radius:10px;padding:13px 15px;color:#36506d;font-size:13px;line-height:1.6">The attached A4 PDF is the official detailed tax invoice. Please retain it for your accounting and VAT records.</div>
        <p style="margin:24px 0 0;color:#566176;line-height:1.7">Kind regards,<br><strong style="color:#03294f">{{ $company }}</strong></p>
    </td></tr>
    <tr><td style="padding:18px 32px;background:#f7f9fc;border-top:1px solid #e7ebf1;text-align:center;color:#8490a2;font-size:11px">System-generated tax invoice email · TRN {{ \App\Support\AppSettings::get('invoice_trn', '101001557300003') }}</td></tr>
</table>
</td></tr></table>
</body></html>
