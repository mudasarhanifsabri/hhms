@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<form class="row g-2 align-items-end mb-3">
    <div class="col-md-3"><label class="form-label">VAT Month</label><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Apply</button></div>
</form>

<div class="row">
    @foreach([
        ['Output VAT', $outputVat, 'success'],
        ['Input VAT', $inputVat, 'danger'],
        ['Net Payable', $outputVat - $inputVat, 'primary'],
    ] as [$label, $amount, $color])
        <div class="col-md-4"><div class="card finance-card"><div class="card-body"><p class="text-muted mb-1">{{ $label }}</p><div class="finance-metric text-{{ $color }}">AED {{ number_format((float) $amount, 2) }}</div></div></div></div>
    @endforeach
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">VAT Ledger Detail</h4></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Date</th><th>No.</th><th>Type</th><th>Unit</th><th>Net</th><th>VAT %</th><th>VAT Amount</th><th>Gross</th></tr></thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                    <td>{{ $entry->entry_no }}</td>
                    <td>{{ ucfirst($entry->type) }}</td>
                    <td>{{ $entry->property?->name ?? '-' }}</td>
                    <td>AED {{ number_format((float) $entry->net_amount, 2) }}</td>
                    <td>{{ number_format((float) $entry->vat_rate, 2) }}%</td>
                    <td>AED {{ number_format((float) $entry->vat_amount, 2) }}</td>
                    <td>AED {{ number_format((float) $entry->gross_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No VAT entries for this month.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
