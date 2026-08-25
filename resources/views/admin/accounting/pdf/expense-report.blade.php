<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#111827;font-size:9px}
        .header{border-bottom:3px solid #0b2850;padding-bottom:10px;margin-bottom:12px}
        .brand{font-size:20px;font-weight:700;color:#0b2850}.title{font-size:14px;font-weight:700}.muted{color:#6b7280}
        .filters,.summary{width:100%;border-collapse:collapse;margin-bottom:12px}.filters td,.summary td{border:1px solid #d8dee9;padding:7px}
        .summary td{background:#f3f6fb;font-size:10px}
        table.report{width:100%;border-collapse:collapse}.report th,.report td{border:1px solid #d8dee9;padding:5px;vertical-align:top}
        .report th{background:#0b2850;color:#fff}.right{text-align:right}.nowrap{white-space:nowrap}.total-row td{font-weight:700;background:#eef2f7}
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">PATTERN Vacation Homes Rental</div>
        <div class="title">Expense Report</div>
        <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>
    </div>

    <table class="filters"><tr>
        <td><strong>Period</strong><br>{{ $filters['date_from']?->format('d M Y') ?? 'Beginning' }} to {{ $filters['date_to']?->format('d M Y') ?? 'Today' }}</td>
        <td><strong>Category</strong><br>{{ $filters['category'] }}</td>
        <td><strong>Unit</strong><br>{{ $filters['unit']?->name ?? 'All units' }}@if($filters['unit']) — {{ $filters['unit']->building?->building_name ?? $filters['unit']->building?->name ?? 'No Building' }}@endif</td>
        <td><strong>Status</strong><br>{{ $filters['status'] }}</td>
    </tr></table>

    <table class="summary"><tr>
        <td><strong>Expenses</strong><br>{{ number_format($expenses->count()) }}</td>
        <td><strong>Net</strong><br>AED {{ number_format($expenses->sum('net_amount'), 2) }}</td>
        <td><strong>VAT</strong><br>AED {{ number_format($expenses->sum('vat_amount'), 2) }}</td>
        <td><strong>Total</strong><br>AED {{ number_format($expenses->sum('gross_amount'), 2) }}</td>
        <td><strong>Owner Charged</strong><br>AED {{ number_format($expenses->where('responsibility', 'owner')->sum('gross_amount'), 2) }}</td>
    </tr></table>

    <table class="report">
        <thead><tr><th>Date / No.</th><th>Category</th><th>Unit / Building</th><th>Vendor</th><th>Charged To</th><th>Paid From</th><th>Status</th><th class="right">Net</th><th class="right">VAT</th><th class="right">Total</th></tr></thead>
        <tbody>
        @forelse($expenses as $expense)
            <tr>
                <td class="nowrap">{{ $expense->expense_date?->format('d M Y') }}<br><strong>{{ $expense->expense_no }}</strong></td>
                <td>{{ \App\Models\Expense::CATEGORIES[$expense->category] ?? ucfirst($expense->category) }}</td>
                <td>{{ $expense->property?->name ?? 'General' }}<br><span class="muted">{{ $expense->property?->building?->building_name ?? $expense->property?->building?->name ?? '' }}</span></td>
                <td>{{ $expense->vendor?->name ?? $expense->supplier ?? '-' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $expense->responsibility)) }}</td>
                <td>{{ $expense->paidFromAccount?->name ?? '-' }}</td>
                <td>{{ ucfirst($expense->approval_status) }}</td>
                <td class="right">{{ number_format((float) $expense->net_amount, 2) }}</td>
                <td class="right">{{ number_format((float) $expense->vat_amount, 2) }}</td>
                <td class="right">{{ number_format((float) $expense->gross_amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center">No expenses match the selected filters.</td></tr>
        @endforelse
        @if($expenses->isNotEmpty())
            <tr class="total-row"><td colspan="7">Report Total</td><td class="right">{{ number_format($expenses->sum('net_amount'), 2) }}</td><td class="right">{{ number_format($expenses->sum('vat_amount'), 2) }}</td><td class="right">AED {{ number_format($expenses->sum('gross_amount'), 2) }}</td></tr>
        @endif
        </tbody>
    </table>
</body>
</html>
