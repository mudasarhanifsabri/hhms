<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#eef2f7;font-family:Arial,sans-serif;color:#172033">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f7;padding:28px 12px"><tr><td align="center">
<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 12px 38px rgba(3,41,79,.12)">
    <tr><td style="background:linear-gradient(135deg,#001d3d,#073b6d);padding:26px 32px;color:#fff">
        <img src="{{ asset('assets/images/pattern-bilingual-logo.png') }}" alt="PATTERN Vacation Homes Rental" style="display:block;width:210px;max-width:65%;height:auto;background:#fff;border-radius:8px;padding:8px;margin-bottom:24px">
        <div style="font-size:12px;letter-spacing:1.5px;color:#d2a44f;text-transform:uppercase;font-weight:bold">Owner Account Statement</div>
        <h1 style="margin:8px 0 4px;font-size:27px;line-height:1.25">{{ $purpose }}</h1>
        <p style="margin:0;color:#c9d6e8;font-size:14px">{{ $statementData['period']['from']->format('d M Y') }} - {{ $statementData['period']['to']->format('d M Y') }}</p>
    </td></tr>
    <tr><td style="padding:30px 32px">
        <p style="margin:0 0 14px;font-size:16px">Dear <strong>{{ $owner->name }}</strong>,</p>
        <p style="margin:0 0 20px;color:#566176;line-height:1.7">Please find your owner account statement attached as a PDF. It includes fully paid booking income, management fees, owner-responsible expenses, payouts, and the resulting account balance.</p>
        @if($customMessage)
            <div style="background:#f7f9fc;border-left:4px solid #d2a44f;border-radius:8px;padding:15px 17px;margin-bottom:22px;color:#3e485a;line-height:1.65">{!! nl2br(e($customMessage)) !!}</div>
        @endif
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:8px 0;margin:0 -8px 24px">
            <tr>
                <td style="background:#f2fbf6;border:1px solid #d7f1e2;border-radius:12px;padding:15px"><div style="font-size:11px;color:#718096;text-transform:uppercase">Total Credit</div><strong style="display:block;margin-top:6px;color:#159455;font-size:18px">AED {{ number_format($statementData['accountTotals']['credit'],2) }}</strong></td>
                <td style="background:#fff5f5;border:1px solid #f7dddd;border-radius:12px;padding:15px"><div style="font-size:11px;color:#718096;text-transform:uppercase">Total Debit</div><strong style="display:block;margin-top:6px;color:#d74b57;font-size:18px">AED {{ number_format($statementData['accountTotals']['debit'],2) }}</strong></td>
                <td style="background:#f2f6fb;border:1px solid #dce6f1;border-radius:12px;padding:15px"><div style="font-size:11px;color:#718096;text-transform:uppercase">Net Balance</div><strong style="display:block;margin-top:6px;color:#03294f;font-size:18px">AED {{ number_format($statementData['accountTotals']['balance'],2) }}</strong></td>
            </tr>
        </table>
        <div style="background:#edf4fb;border-radius:10px;padding:13px 15px;color:#36506d;font-size:13px;line-height:1.6">The attached statement contains only fully paid booking periods. Original bookings, extensions, and renewals are presented separately.</div>
        <p style="margin:24px 0 0;color:#566176;line-height:1.7">Kind regards,<br><strong style="color:#03294f">PATTERN Vacation Homes Rental</strong></p>
    </td></tr>
    <tr><td style="padding:18px 32px;background:#f7f9fc;border-top:1px solid #e7ebf1;text-align:center;color:#8490a2;font-size:11px">This is a system-generated owner statement email. The attached PDF is the official statement copy.</td></tr>
</table>
</td></tr></table>
</body></html>
