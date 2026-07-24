<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#111827;font-size:12px}
        .header{border-bottom:3px solid #111827;padding-bottom:12px;margin-bottom:16px}
        .brand{font-size:22px;font-weight:700}
        .muted{color:#6b7280}
        .summary{background:#f3f6fb;border:1px solid #d8dee9;padding:10px;margin:10px 0 16px}
        table{width:100%;border-collapse:collapse;margin-bottom:14px}
        th,td{border:1px solid #d8dee9;padding:7px;vertical-align:top}
        th{background:#eef2f7}
        .right{text-align:right}
        .unit-title{font-size:15px;font-weight:700;margin:18px 0 7px}
        .total-row td{font-weight:700;background:#f9fafb}
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">PATTERN Vacation Homes Rental</div>
        <div class="muted">Owner Statement</div>
    </div>

    <table>
        <tr>
            <td><strong>Owner</strong><br>{{ $owner->name }}<br>{{ $owner->email }}</td>
            <td class="right"><strong>Period</strong><br>{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}<br><strong>Generated</strong><br>{{ now()->format('d M Y H:i') }}</td>
        </tr>
    </table>

    <div class="summary">
        @php($grandTotal = $entries->flatten(1)->sum(fn($entry) => $entry->direction === 'credit' ? (float) $entry->amount : -(float) $entry->amount))
        <strong>Statement Net Balance:</strong> AED {{ number_format($grandTotal, 2) }}
    </div>

    @forelse($entries as $propertyEntries)
        @php($property = $propertyEntries->first()?->property)
        @php($unitTotal = $propertyEntries->sum(fn($entry) => $entry->direction === 'credit' ? (float) $entry->amount : -(float) $entry->amount))
        <div class="unit-title">{{ $property?->name ?? 'General Owner Ledger' }}</div>
        <table>
            <thead>
                <tr><th>Date</th><th>Particulars</th><th>Reference</th><th class="right">Debit</th><th class="right">Credit</th></tr>
            </thead>
            <tbody>
                @foreach($propertyEntries as $entry)
                    <tr>
                        <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                        <td><strong>{{ $entry->type_label }}</strong><br>{{ $entry->description }}</td>
                        <td>{{ $entry->reference }}</td>
                        <td class="right">{{ $entry->direction === 'debit' ? 'AED '.number_format((float) $entry->amount, 2) : '-' }}</td>
                        <td class="right">{{ $entry->direction === 'credit' ? 'AED '.number_format((float) $entry->amount, 2) : '-' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row"><td colspan="3">Unit Total</td><td colspan="2" class="right">AED {{ number_format($unitTotal, 2) }}</td></tr>
            </tbody>
        </table>
    @empty
        <p>No owner statement entries for this period.</p>
    @endforelse
</body>
</html>
