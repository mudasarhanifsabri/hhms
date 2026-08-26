@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<form class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label">Month</label>
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control">
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Apply</button></div>
</form>

<div class="row">
    @foreach([
        ['Today Income', $todayIncome, 'ri-arrow-up-circle-line', 'success', null],
        ['Today Expenses', $todayExpenses, 'ri-arrow-down-circle-line', 'danger', null],
        ['Cash Balance', $cashBalance, 'ri-money-dollar-circle-line', 'success', null],
        ['Bank Balance', $bankBalance, 'ri-bank-line', 'primary', route('admin.accounting.bank-statements')],
        ['Accounts Receivable', $accountsReceivable, 'ri-file-list-3-line', 'info', route('admin.accounting.reports').'#accounts-receivable'],
        ['Accounts Payable', $accountsPayable, 'ri-bill-line', 'warning', route('admin.accounting.expenses')],
        ['Owner Payables', $ownerPayables, 'ri-user-star-line', 'secondary', route('admin.accounting.owner-statements')],
        ['VAT Payable', $vatOutput - $vatInput, 'ri-percent-line', 'warning', route('admin.accounting.vat')],
        ['Monthly Profit', $monthlyProfit, 'ri-line-chart-line', 'primary', route('admin.accounting.reports')],
        ['Occupancy Revenue', $occupancyRevenue, 'ri-hotel-bed-line', 'success', null],
        ['Utility Expenses', $utilityExpenses, 'ri-flashlight-line', 'danger', route('admin.accounting.utilities')],
        ['Outstanding Bills', $outstandingUtilities, 'ri-alarm-warning-line', 'info', route('admin.accounting.utilities')],
    ] as [$label, $amount, $icon, $color, $href])
        <div class="col-xl-3 col-md-4">
            <div class="card finance-card">@if($href)<a href="{{ $href }}" class="stretched-link" aria-label="Open {{ $label }}"></a>@endif
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">{{ $label }}</p>
                        <div class="finance-metric">AED {{ number_format((float) $amount, 2) }}</div>
                    </div>
                    <span class="avatar-md rounded bg-{{ $color }} bg-opacity-10 text-{{ $color }} d-inline-flex align-items-center justify-content-center">
                        <i class="{{ $icon }} fs-24"></i>
                    </span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Recent Ledger Entries</h4></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-subtle"><tr><th>Date</th><th>No.</th><th>Unit</th><th>Type</th><th>Debit</th><th>Credit</th></tr></thead>
                    <tbody>
                    @forelse($recentEntries as $entry)
                        <tr>
                            <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                            <td class="fw-semibold">{{ $entry->entry_no }}</td>
                            <td>{{ $entry->property?->name ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark">{{ ucfirst($entry->type) }}</span></td>
                            <td>AED {{ number_format((float) $entry->debit, 2) }}</td>
                            <td>AED {{ number_format((float) $entry->credit, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No ledger entries yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Utility Bills To Pay</h4></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-subtle"><tr><th>Unit</th><th>Utility</th><th>Due</th><th>Total</th></tr></thead>
                    <tbody>
                    @forelse($upcomingUtilityBills as $bill)
                        <tr>
                            <td>{{ $bill->property?->name ?? '-' }}</td>
                            <td>{{ $bill->account?->type_label ?? '-' }}</td>
                            <td>{{ $bill->due_date?->format('d M Y') ?? '-' }}</td>
                            <td>AED {{ number_format((float) $bill->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No outstanding utility bills.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
