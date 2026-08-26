@php
    $logo = public_path('assets/images/logo-dark.png');
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 38px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color: #3c3f3b; font-size: 11px; }
        .header { width: 100%; margin-bottom: 24px; }
        .header td { vertical-align: top; border: 0; }
        .logo { width: 210px; margin-top: 20px; }
        .company { text-align: right; line-height: 1.45; }
        .company strong { font-size: 13px; }
        .to { margin-top: 16px; line-height: 1.45; }
        .title { text-align: right; margin-top: 24px; }
        .title h1 { display: inline-block; font-size: 24px; margin: 0; color: #111; border-bottom: 2px solid #111; }
        .title p { margin: 6px 0 0; }
        .summary { width: 360px; margin-left: auto; margin-top: 18px; border-collapse: collapse; }
        .summary th { background: #e9e9e9; text-align: left; padding: 8px; }
        .summary td { padding: 8px; border-bottom: 0; }
        .summary td:last-child { text-align: right; }
        .summary .line td { border-top: 1px solid #111; }
        .statement { width: 100%; border-collapse: collapse; margin-top: 44px; }
        .statement th { background: #3c403b; color: #fff; padding: 8px 6px; text-align: left; }
        .statement td { padding: 9px 6px; vertical-align: top; }
        .statement tbody tr:nth-child(even) { background: #f5f3f3; }
        .right { text-align: right; }
        .balance-due td { border-top: 1px solid #ddd; background: #fff; font-weight: 700; padding-top: 12px; }
        .footer-line { position: fixed; bottom: 22px; left: 42px; right: 42px; border-top: 1px solid #bbb; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 50%;">
                @if(file_exists($logo))
                    <img src="{{ $logo }}" class="logo" alt="Pattern">
                @endif
                <div class="to">
                    <strong>To</strong><br>
                    {{ $landlord->name }}<br>
                    {{ $landlord->email }}<br>
                    {{ $landlord->phone }}
                </div>
            </td>
            <td class="company">
                <strong>PATTERN VACATION HOMES RENTAL</strong><br>
                Dubai<br>
                U.A.E<br>
                TRN 101001557300003<br>
                +971503344887<br>
                patterncustomerservice@gmail.com

                <div class="title">
                    <h1>Statement of Accounts</h1>
                    <p>{{ $period['from']->format('d M Y') }} To {{ $period['to']->format('d M Y') }}</p>
                </div>

                <table class="summary">
                    <tr><th colspan="2">Account Summary</th></tr>
                    <tr><td>Opening Balance</td><td>AED 0.00</td></tr>
                    <tr><td>Total Credit</td><td>AED {{ number_format($accountTotals['credit'], 2) }}</td></tr>
                    <tr><td>Total Debit</td><td>AED {{ number_format($accountTotals['debit'], 2) }}</td></tr>
                    <tr class="line"><td>Net Owner Balance</td><td>AED {{ number_format($accountTotals['balance'], 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="statement">
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 15%;">Category</th>
                <th>Description</th>
                <th style="width: 12%;">Unit</th>
                <th style="width: 11%;">Reference</th>
                <th class="right" style="width: 11%;">Credit</th>
                <th class="right" style="width: 11%;">Debit</th>
                <th class="right" style="width: 12%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accountEntries as $entry)
                <tr>
                    <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                    <td>{{ $entry->type_label }}</td>
                    <td>{{ $entry->description ?: '-' }}</td>
                    <td>{{ $entry->property?->name ?? 'General' }}</td>
                    <td>{{ $entry->reference ?? '-' }}</td>
                    <td class="right">{{ $entry->direction === 'credit' ? number_format((float) $entry->amount, 2) : '-' }}</td>
                    <td class="right">{{ $entry->direction === 'debit' ? number_format((float) $entry->amount, 2) : '-' }}</td>
                    <td class="right">{{ number_format((float) $entry->balance_after, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">No owner account entries found.</td></tr>
            @endforelse
            <tr class="balance-due">
                <td colspan="7" class="right">Net Owner Balance</td>
                <td class="right">AED {{ number_format($accountTotals['balance'], 2) }}</td>
            </tr>
        </tbody>
    </table>
    <div class="footer-line"></div>
</body>
</html>
