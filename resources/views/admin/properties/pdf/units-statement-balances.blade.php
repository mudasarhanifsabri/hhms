<!doctype html>
<html><head><meta charset="utf-8"><style>
@page{margin:12mm}body{font-family:DejaVu Sans,Arial,sans-serif;color:#172033;font-size:9px}.header{border-bottom:3px solid #173b6c;padding-bottom:10px;margin-bottom:14px}.header img{width:175px}.header h1{font-size:20px;color:#173b6c;margin:8px 0 2px}.meta{color:#657187}table{width:100%;border-collapse:collapse}th{background:#173b6c;color:#fff;padding:7px 5px;text-align:left}td{border-bottom:1px solid #dce3ec;padding:7px 5px;vertical-align:top}.right{text-align:right}.positive{color:#14804a}.negative{color:#c23333}.total td{font-weight:bold;background:#eef3f8;border-top:2px solid #173b6c}.footer{margin-top:14px;color:#657187;font-size:8px;text-align:center}
</style></head><body>
<div class="header">
@if(file_exists(public_path('assets/images/logo-dark.png')))
<img src="{{ public_path('assets/images/logo-dark.png') }}">
@else
<strong>PATTERN VACATION HOMES RENTAL</strong>
@endif
<h1>Units &amp; Owner Statement Balances</h1>
<div class="meta">
Statement month: <strong>{{ $periodLabel }}</strong> ({{ $periodStart->format('d M Y') }} – {{ $periodEnd->format('d M Y') }}) ·
Generated {{ now()->timezone('Asia/Dubai')->format('d M Y, h:i A') }} GST
@if($status) · Status: {{ str($status)->headline() }} @endif
@if($search) · Search: {{ $search }} @endif
</div></div>
<table><thead><tr><th>Unit</th><th>Building</th><th>Owner</th><th>Status</th><th class="right">Opening</th><th class="right">Credits</th><th class="right">Debits</th><th class="right">Month Net</th><th class="right">Closing</th><th>Position</th></tr></thead><tbody>
@forelse($properties as $property)
@php
    $balance = (float) $property->statement_balance;
@endphp
<tr><td><strong>{{ $property->name }}</strong></td><td>{{ $property->building?->building_name ?? 'No building' }}</td><td>{{ $property->landlord?->name ?? 'Not assigned' }}</td><td>{{ $property->status_label }}</td><td class="right">{{ number_format((float)$property->opening_balance,2) }}</td><td class="right positive">{{ number_format((float)$property->period_credits,2) }}</td><td class="right negative">{{ number_format((float)$property->period_debits,2) }}</td><td class="right">{{ number_format((float)$property->period_net,2) }}</td><td class="right {{ $balance>0?'positive':($balance<0?'negative':'') }}">{{ number_format($balance,2) }}</td><td>{{ $balance>0.009?'Due to Owner':($balance < -0.009?'Due from Owner':'Settled') }}</td></tr>@empty<tr><td colspan="10" style="text-align:center">No units match the selected filters.</td></tr>@endforelse
@if($properties->isNotEmpty())<tr class="total"><td colspan="8">Combined closing balance</td><td class="right">AED {{ number_format($totalBalance,2) }}</td><td>{{ $totalBalance>0.009?'Due to Owners':($totalBalance < -0.009?'Due from Owners':'Settled') }}</td></tr>@endif
</tbody></table><div class="footer">Statement balance uses the same owner-visible ledger entries and RMS statement cutoff rules as Owner Statements.</div>
</body></html>
