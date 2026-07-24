@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<form class="row g-2 align-items-end mb-3">
    <div class="col-md-3"><label class="form-label">Report Month</label><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Apply</button></div>
</form>

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
