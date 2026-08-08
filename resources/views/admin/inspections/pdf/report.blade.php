<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { color: #0b2f6b; font-size: 22px; margin: 0 0 8px; }
        h2 { font-size: 15px; margin: 18px 0 8px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d8dee8; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #eef4ff; }
        .summary td { font-weight: bold; }
        .issue { color: #b42318; font-weight: bold; }
        .good { color: #067647; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $inspection->type_label }} Inspection Report</h1>
    <p><strong>{{ $inspection->inspection_number }}</strong> | {{ $inspection->booking?->booking_reference ?? $inspection->task?->task_display_number ?? 'Unit inspection' }} | {{ $inspection->booking?->guest_name ?? $inspection->submittedBy?->name ?? '-' }}</p>
    <p>{{ $inspection->booking?->property?->building?->name ?? $inspection->property?->building?->name }} - {{ $inspection->booking?->property?->name ?? $inspection->property?->name }} | Submitted: {{ $inspection->submitted_at?->format('d M Y H:i') ?? '-' }}</p>
    <table class="summary">
        <tr><td>Total Items: {{ $inspection->total_items }}</td><td class="good">Good: {{ $inspection->good_items }}</td><td class="issue">Issues: {{ $inspection->issue_items }}</td><td>N/A: {{ $inspection->na_items }}</td></tr>
    </table>
    <h2>Items</h2>
    <table>
        <thead><tr><th>Area</th><th>Item</th><th>Condition</th><th>Comment</th></tr></thead>
        <tbody>
            @foreach($inspection->items as $item)
                <tr>
                    <td>{{ $item->area }}</td>
                    <td>{{ $item->item }}</td>
                    <td>{{ strtoupper($item->condition) }}</td>
                    <td>{{ $item->comment ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <h2>Security Deposit Comparison</h2>
    @if($comparison['other'])
        <table>
            <thead><tr><th>Area</th><th>Item</th><th>Check In</th><th>Check Out</th><th>Comment</th></tr></thead>
            <tbody>
                @forelse($comparison['changed'] as $change)
                    <tr><td>{{ $change['area'] }}</td><td>{{ $change['item'] }}</td><td>{{ strtoupper($change['check_in']) }}</td><td>{{ strtoupper($change['check_out']) }}</td><td>{{ $change['comment'] ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="5">No condition changes found.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <p>Both check-in and check-out inspections are required for comparison.</p>
    @endif
    <h2>Notes</h2>
    <p>{{ $inspection->notes ?: '-' }}</p>
</body>
</html>
