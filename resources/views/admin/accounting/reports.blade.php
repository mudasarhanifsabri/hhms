@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<form class="row g-2 align-items-end mb-3">
    <div class="col-md-3"><label class="form-label">From Date</label><input type="date" name="date_from" value="{{ $from->toDateString() }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">To Date</label><input type="date" name="date_to" value="{{ $to->toDateString() }}" class="form-control"></div>
    <div class="col-md-2"><label class="form-label">Quick Month</label><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Apply</button></div>
    <div class="col-md-2"><a href="{{ route('admin.accounting.reports') }}" class="btn btn-light w-100">Reset</a></div>
</form>

<div class="row">
    @foreach([
        ['label' => 'Total Expenses', 'value' => 'AED ' . number_format((float) $expenseTotals['gross'], 2), 'icon' => 'ri-receipt-line', 'color' => 'primary'],
        ['label' => 'Net Amount', 'value' => 'AED ' . number_format((float) $expenseTotals['net'], 2), 'icon' => 'ri-wallet-3-line', 'color' => 'success'],
        ['label' => 'VAT Input', 'value' => 'AED ' . number_format((float) $expenseTotals['vat'], 2), 'icon' => 'ri-percent-line', 'color' => 'warning'],
        ['label' => 'Draft / Review', 'value' => $expenseTotals['draft'] . ' / ' . $expenseTotals['review'], 'icon' => 'ri-draft-line', 'color' => 'danger'],
    ] as $metric)
        <div class="col-md-3">
            <div class="card finance-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div><p class="text-muted mb-1">{{ $metric['label'] }}</p><div class="finance-metric">{{ $metric['value'] }}</div></div>
                    <span class="avatar-md rounded bg-{{ $metric['color'] }} bg-opacity-10 text-{{ $metric['color'] }} d-inline-flex align-items-center justify-content-center"><i class="{{ $metric['icon'] }} fs-26"></i></span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Expense Report By Date</h4></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light-subtle"><tr><th>Date</th><th class="text-center">Entries</th><th class="text-end">Net</th><th class="text-end">VAT</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse($expensesByDate as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->expense_date)->format('d M Y') }}</td>
                            <td class="text-center">{{ $row->count_total }}</td>
                            <td class="text-end">AED {{ number_format((float) $row->net_total, 2) }}</td>
                            <td class="text-end">AED {{ number_format((float) $row->vat_total, 2) }}</td>
                            <td class="text-end fw-semibold">AED {{ number_format((float) $row->gross_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No expenses for this date range.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Expenses By Unit</h4></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light-subtle"><tr><th>Unit</th><th class="text-center">Entries</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse($expensesByUnit as $unit => $row)
                        <tr><td>{{ $unit }}</td><td class="text-center">{{ $row['count'] }}</td><td class="text-end">AED {{ number_format((float) $row['gross'], 2) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No unit expenses for this date range.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Utility Cost By Type</h4></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light-subtle"><tr><th>Utility</th><th>Total</th></tr></thead>
                    <tbody>
                    @forelse($utilityByType as $type => $total)
                        <tr><td>{{ ucfirst($type) }}</td><td>AED {{ number_format((float) $total, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">No utility cost for this month.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Expenses By Category</h4></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light-subtle"><tr><th>Category</th><th>Total</th></tr></thead>
                    <tbody>
                    @forelse($expensesByCategory as $category => $total)
                        <tr><td>{{ ucfirst(str_replace('_', ' ', $category)) }}</td><td>AED {{ number_format((float) $total, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">No expenses for this month.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Profit &amp; Loss</h4>
                <span class="text-muted">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="bg-light-subtle"><tr><th>Account</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        <tr class="table-success"><th colspan="2">Income</th></tr>
                        @forelse($incomeAccounts as $row)<tr><td>{{ $row['code'] }} — {{ $row['name'] }}</td><td class="text-end">AED {{ number_format($row['amount'], 2) }}</td></tr>@empty<tr><td colspan="2" class="text-muted text-center">No posted income</td></tr>@endforelse
                        <tr class="fw-bold"><td>Total Income</td><td class="text-end">AED {{ number_format($profitLossTotals['income'], 2) }}</td></tr>
                        <tr class="table-danger"><th colspan="2">Expenses</th></tr>
                        @forelse($expenseAccounts as $row)<tr><td>{{ $row['code'] }} — {{ $row['name'] }}</td><td class="text-end">AED {{ number_format($row['amount'], 2) }}</td></tr>@empty<tr><td colspan="2" class="text-muted text-center">No posted expenses</td></tr>@endforelse
                        <tr class="fw-bold"><td>Total Expenses</td><td class="text-end">AED {{ number_format($profitLossTotals['expense'], 2) }}</td></tr>
                        <tr class="table-primary fs-16"><th>Net Profit / (Loss)</th><th class="text-end {{ $profitLossTotals['net_profit'] < 0 ? 'text-danger' : 'text-success' }}">AED {{ number_format($profitLossTotals['net_profit'], 2) }}</th></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Cash Flow Summary</h4></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <tr><td>Opening Bank &amp; Cash</td><td class="text-end">AED {{ number_format($cashFlowSummary['opening'], 2) }}</td></tr>
                    <tr><td>Cash Inflows</td><td class="text-end text-success">AED {{ number_format($cashFlowSummary['inflow'], 2) }}</td></tr>
                    <tr><td>Cash Outflows</td><td class="text-end text-danger">AED {{ number_format($cashFlowSummary['outflow'], 2) }}</td></tr>
                    <tr class="table-primary fw-bold"><td>Closing Bank &amp; Cash</td><td class="text-end">AED {{ number_format($cashFlowSummary['closing'], 2) }}</td></tr>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Receivable / Payable Ageing</h4></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="bg-light-subtle"><tr><th>Age</th><th class="text-end">Receivable</th><th class="text-end">Payable</th></tr></thead>
                    <tbody>
                    @foreach(['current' => '0–30 Days', '31_60' => '31–60 Days', '61_90' => '61–90 Days', 'over_90' => 'Over 90 Days'] as $key => $label)
                        <tr><td>{{ $label }}</td><td class="text-end">AED {{ number_format($receivableAgeing[$key], 2) }}</td><td class="text-end">AED {{ number_format($payableAgeing[$key], 2) }}</td></tr>
                    @endforeach
                    <tr class="fw-bold"><td>Total</td><td class="text-end">AED {{ number_format($receivableAgeing['total'], 2) }}</td><td class="text-end">AED {{ number_format($payableAgeing['total'], 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">Balance Sheet (as at {{ $to->format('d M Y') }})</h4></div>
    <div class="row g-0">
        @foreach(['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity'] as $type => $label)
            @php
                $rows = $balanceSheetRows->get($type, collect());
                $total = $rows->sum(fn($row) => $type === 'asset' ? (float)$row->debit_total - (float)$row->credit_total : (float)$row->credit_total - (float)$row->debit_total);
            @endphp
            <div class="col-lg-4 border-end">
                <div class="p-3"><h5>{{ $label }}</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    @forelse($rows as $row)
                        @php($amount = $type === 'asset' ? (float)$row->debit_total - (float)$row->credit_total : (float)$row->credit_total - (float)$row->debit_total)
                        <tr><td>{{ $row->code }} {{ $row->name }}</td><td class="text-end">AED {{ number_format($amount, 2) }}</td></tr>
                    @empty<tr><td class="text-muted">No posted balances</td></tr>@endforelse
                    <tr class="table-light fw-bold"><td>Total {{ $label }}</td><td class="text-end">AED {{ number_format($total, 2) }}</td></tr>
                </table></div>
            </div>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">Expense Details</h4></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Date</th><th>No.</th><th>Vendor</th><th>Unit</th><th>Category</th><th>Status</th><th class="text-end">VAT</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            @forelse($expenseRows as $expense)
                <tr>
                    <td>{{ $expense->expense_date?->format('d M Y') }}</td>
                    <td>{{ $expense->expense_no }}</td>
                    <td>{{ $expense->vendor?->name ?? $expense->supplier ?? '-' }}</td>
                    <td>{{ $expense->property?->name ?? '-' }}</td>
                    <td>{{ $expenseCategories[$expense->category] ?? ucfirst(str_replace('_', ' ', $expense->category)) }}</td>
                    <td>
                        <span class="badge {{ in_array($expense->approval_status, ['approved', 'paid'], true) ? 'bg-success' : 'bg-warning' }}">{{ ucfirst($expense->approval_status) }}</span>
                        @if($expense->needs_review)<span class="badge bg-danger-subtle text-danger border ms-1">Review</span>@endif
                    </td>
                    <td class="text-end">AED {{ number_format((float) $expense->vat_amount, 2) }}</td>
                    <td class="text-end fw-semibold">AED {{ number_format((float) $expense->gross_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No expense details found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">Outstanding Utility Bills</h4></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Unit</th><th>Utility</th><th>Responsibility</th><th>Due Date</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($outstandingBills as $bill)
                <tr>
                    <td>{{ $bill->property?->name ?? '-' }}</td>
                    <td>{{ $bill->account?->type_label ?? '-' }}</td>
                    <td>{{ $bill->account?->responsibility_label ?? ucfirst($bill->responsibility) }}</td>
                    <td>{{ $bill->due_date?->format('d M Y') ?? '-' }}</td>
                    <td>AED {{ number_format((float) $bill->total_amount, 2) }}</td>
                    <td><span class="badge bg-warning">{{ $bill->status_label }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No outstanding bills.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
