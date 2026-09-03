@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')
@php
    $reportTabs = ['financial' => 'Financial Summary', 'receivables' => 'Receivables', 'expenses' => 'Expenses', 'utilities' => 'Utilities'];
    $activeReport = array_key_exists(request('report', 'financial'), $reportTabs) ? request('report', 'financial') : 'financial';
    $periodQuery = ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()];
@endphp
<div class="reports-hub">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h4 class="mb-1">Accounting Reports</h4><p class="text-muted mb-0">Review performance, outstanding balances and operating costs.</p></div>
    <span class="badge bg-primary-subtle text-primary border px-3 py-2">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</span>
</div>
<div class="card">
    <div class="card-header"><h5 class="mb-0">Reporting Period</h5></div>
    <form method="GET" action="{{ route('admin.accounting.reports') }}" class="card-body">
        <input type="hidden" name="report" id="activeReport" value="{{ $activeReport }}">
        <div class="row g-3 align-items-end">
            <div class="col-lg-3 col-sm-6"><label for="reportFrom" class="form-label">From date</label><input id="reportFrom" type="date" name="date_from" value="{{ $from->toDateString() }}" class="form-control" required></div>
            <div class="col-lg-3 col-sm-6"><label for="reportTo" class="form-label">To date</label><input id="reportTo" type="date" name="date_to" value="{{ $to->toDateString() }}" class="form-control" required></div>
            <div class="col-lg-3 col-sm-6"><label for="reportMonth" class="form-label">Fill dates from month</label><input id="reportMonth" type="month" value="{{ $from->format('Y-m') === $to->format('Y-m') ? $from->format('Y-m') : '' }}" class="form-control"><small class="text-muted">Select a month, then apply.</small></div>
            <div class="col-lg-3 col-sm-6 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Apply Period</button><a href="{{ route('admin.accounting.reports', ['report'=>$activeReport]) }}" class="btn btn-outline-secondary">Reset</a></div>
        </div>
    </form>
</div>
<div class="row g-3 mb-4">
    @foreach([
        ['Posted income', $profitLossTotals['income'], 'text-success', 'Selected period'],
        ['Net profit / (loss)', $profitLossTotals['net_profit'], $profitLossTotals['net_profit'] < 0 ? 'text-danger' : 'text-success', 'Selected period'],
        ['Closing bank & cash', $cashFlowSummary['closing'], 'text-primary', 'At period end'],
        ['Outstanding receivables', $receivableAgeing['total'], 'text-warning', 'Current outstanding · all dates'],
    ] as [$label, $value, $color, $scope])
    <div class="col-xl-3 col-sm-6"><div class="card h-100 mb-0"><div class="card-body"><small class="text-muted">{{ $label }}</small><h4 class="my-2 {{ $color }}">AED {{ number_format((float)$value,2) }}</h4><small class="text-muted">{{ $scope }}</small></div></div></div>
    @endforeach
</div>
<div class="card" id="reportsPanel">
    <div class="card-header p-0"><nav class="nav nav-tabs reports-tabs px-3" role="tablist" aria-label="Report categories">
        @foreach($reportTabs as $key=>$label)
        <a id="report-tab-{{ $key }}" class="nav-link {{ $activeReport===$key?'active':'' }}" href="{{ route('admin.accounting.reports', $periodQuery + ['report'=>$key]) }}#reportsPanel" data-bs-toggle="tab" data-bs-target="#report-panel-{{ $key }}" data-report="{{ $key }}" role="tab" aria-controls="report-panel-{{ $key }}" aria-selected="{{ $activeReport===$key?'true':'false' }}">{{ $label }}</a>
        @endforeach
    </nav></div>
    <div class="card-body tab-content">
<section id="report-panel-financial" class="tab-pane {{ $activeReport==='financial'?'show active':'' }}" role="tabpanel" aria-labelledby="report-tab-financial" tabindex="0">
<p class="small text-muted mb-3">Profit & loss uses posted entries in the selected period. Balance sheet includes posted balances through the period end. Ageing shows current outstanding amounts across all dates.</p><div class="row">
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


</section>
<section id="report-panel-receivables" class="tab-pane {{ $activeReport==='receivables'?'show active':'' }}" role="tabpanel" aria-labelledby="report-tab-receivables" tabindex="0">
<p class="small text-muted mb-3">Current outstanding balances across all dates—not restricted by the reporting period. Open a booking or owner statement to review the balance.</p><div class="card" id="accounts-receivable">
    <div class="card-header"><h4 class="card-title mb-0">Accounts Receivable - Who Owes</h4></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="bg-light-subtle"><tr><th>Invoice</th><th>Receivable From</th><th>Booking</th><th>Unit</th><th>Issue Date</th><th>Age</th><th class="text-end">Amount Due</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($ownerReceivableRows as $ownerRow)<tr class="table-warning">
            <td>Owner Ledger</td><td><strong>{{ $ownerRow->landlord?->name ?? 'Owner' }}</strong><br><small class="text-muted">Owner / Landlord</small></td><td>-</td><td>Multiple / owner account</td><td>{{ $ownerRow->oldest_debit_date ? \Illuminate\Support\Carbon::parse($ownerRow->oldest_debit_date)->format('d M Y') : '-' }}</td><td>{{ $ownerRow->oldest_debit_date ? \Illuminate\Support\Carbon::parse($ownerRow->oldest_debit_date)->diffInDays(today()) : 0 }} days</td><td class="text-end fw-semibold">AED {{ number_format(abs((float)$ownerRow->balance),2) }}</td><td>@if($ownerRow->landlord)<a href="{{ route('admin.landlord.account-statement',$ownerRow->landlord_id) }}" class="btn btn-sm btn-soft-warning">Owner Statement</a>@endif</td>
        </tr>@endforeach
        @foreach($receivableRows as $invoice)<tr>
            <td>{{ $invoice->invoice_number }}</td><td><strong>{{ $invoice->booking?->guest_name ?? 'Booking customer' }}</strong><br><small class="text-muted">Guest / Tenant</small></td><td>{{ $invoice->booking?->booking_reference ?? '-' }}</td><td>{{ $invoice->booking?->property?->name ?? '-' }}</td><td>{{ $invoice->issue_date?->format('d M Y') }}</td><td>{{ $invoice->issue_date?->diffInDays(today()) ?? 0 }} days</td><td class="text-end fw-semibold">AED {{ number_format((float)$invoice->balance_due,2) }}</td><td>@if($invoice->booking)<a href="{{ route('admin.booking.show',$invoice->booking) }}" class="btn btn-sm btn-soft-primary">Open Booking</a>@endif</td>
        </tr>@endforeach
        @if($ownerReceivableRows->isEmpty() && $receivableRows->isEmpty())<tr><td colspan="8" class="text-center text-muted py-4">No outstanding receivables.</td></tr>@endif
        </tbody>
    </table></div>
</div>


</section>
<section id="report-panel-expenses" class="tab-pane {{ $activeReport==='expenses'?'show active':'' }}" role="tabpanel" aria-labelledby="report-tab-expenses" tabindex="0">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><p class="small text-muted mb-0">Selected-period expenses, including drafts and review items; rejected expenses excluded.</p><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounting.expenses', $periodQuery) }}">Expense Register &amp; Downloads</a></div><div class="row">
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

<div class="row">    <div class="col-12">
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
</div><div class="card">
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


</section>
<section id="report-panel-utilities" class="tab-pane {{ $activeReport==='utilities'?'show active':'' }}" role="tabpanel" aria-labelledby="report-tab-utilities" tabindex="0">
<p class="small text-muted mb-3">Cost summary uses bill months within the selected period. Outstanding bills include all dates.</p><div class="row">    <div class="col-12">
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
</div><div class="card">
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

</section>
</div></div></div>
@endsection

@push('styles')
<style>
.reports-hub .card{border:1px solid var(--bs-border-color,#e7e8ef);border-radius:10px;box-shadow:0 2px 8px #13203a04}
.reports-tabs{gap:8px;border-bottom:0;flex-wrap:wrap}
.reports-tabs .nav-link{padding:17px 14px;border:0;border-bottom:3px solid transparent;color:var(--bs-secondary-color,#667085)}
.reports-tabs .nav-link.active{color:#6242df;border-bottom-color:#6242df;background:transparent;font-weight:600}
.reports-hub th{font-size:12px}.reports-hub td{font-size:13px}
.reports-hub .table-responsive{max-height:480px;overflow:auto}
.reports-hub thead{position:sticky;top:0;z-index:1;background:var(--bs-body-bg,#fff)}
.reports-hub .card-title{font-size:16px}
.reports-hub .table td,.reports-hub .table th{padding:12px 16px}
@media print{.reports-hub .tab-pane{display:block!important}.reports-hub .table-responsive{max-height:none;overflow:visible}.reports-hub form,.reports-tabs{display:none}}
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const from=document.getElementById('reportFrom'),to=document.getElementById('reportTo');
    document.getElementById('reportMonth').addEventListener('change',function(){
        if(!/^\d{4}-\d{2}$/.test(this.value)) return;
        const [year,month]=this.value.split('-').map(Number);
        from.value=this.value+'-01';
        to.value=this.value+'-'+String(new Date(year,month,0).getDate()).padStart(2,'0');
    });
    document.querySelectorAll('[data-report]').forEach(tab=>tab.addEventListener('shown.bs.tab',()=>{
        document.getElementById('activeReport').value=tab.dataset.report;
        const url=new URL(window.location.href);url.searchParams.set('report',tab.dataset.report);url.hash='reportsPanel';history.replaceState(null,'',url);
    }));
    if(location.hash==='#accounts-receivable' && window.bootstrap){
        bootstrap.Tab.getOrCreateInstance(document.getElementById('report-tab-receivables')).show();
        document.getElementById('accounts-receivable').scrollIntoView();
    }
});
</script>
@endpush
