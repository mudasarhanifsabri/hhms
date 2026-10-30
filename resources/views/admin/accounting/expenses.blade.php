@extends('layouts.app')

@section('content')
@if(isset($scopedProperty))
    @include('admin.properties.partials.unit-tabs', ['property' => $scopedProperty])
@else
    @include('admin.accounting.partials.module-nav')
@endif

@php
    $activeFilterCount = collect(['category', 'property_id', 'approval_status', 'date_from', 'date_to'])
        ->filter(fn ($key) => request()->filled($key))->count();
    $buildingLabel = isset($scopedProperty)
        ? ($scopedProperty->building?->building_name ?? $scopedProperty->building?->name ?? 'Building not assigned')
        : null;
@endphp

<style>
.expense-workspace{--ex-ink:#172432;--ex-purple:#6654e8;--ex-muted:#718096}.expense-hero{position:relative;overflow:hidden;padding:24px 26px;border-radius:22px;background:linear-gradient(118deg,#192838 0%,#294462 58%,#6554e8 100%);color:#fff;box-shadow:0 15px 35px rgba(28,44,65,.16)}.expense-hero:after{content:"";position:absolute;right:-90px;top:-145px;width:310px;height:310px;border-radius:50%;background:rgba(255,255,255,.08)}.expense-hero>*{position:relative;z-index:1}.expense-kicker{color:#bdcbe0;font-size:11px;font-weight:750;letter-spacing:.12em;text-transform:uppercase}.expense-title{font-size:25px;font-weight:800;letter-spacing:-.02em}.expense-subtitle{color:#d3dceb}.expense-hero .btn{border:0;border-radius:11px;padding:10px 14px;font-weight:650}.expense-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.expense-stat{display:flex;align-items:center;gap:12px;padding:16px;background:#fff;border:1px solid #e7eaf1;border-radius:17px;box-shadow:0 8px 25px rgba(31,45,61,.05)}.expense-stat .stat-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;background:#eeeafd;color:var(--ex-purple);font-size:21px;flex:0 0 auto}.expense-stat small{display:block;color:#8490a2}.expense-stat strong{display:block;color:var(--ex-ink);font-size:17px}.expense-panel{border:0;border-radius:20px;box-shadow:0 10px 32px rgba(32,44,66,.07);overflow:hidden}.expense-toolbar{background:#fff;padding:18px 20px;border-bottom:1px solid #edf0f5}.expense-filter{padding:17px 20px;background:#f8f9fc;border-bottom:1px solid #edf0f5}.expense-filter label{display:block;margin-bottom:6px;color:#718096;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}.expense-table thead th{padding:13px 14px;color:#718096;font-size:11px;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap}.expense-table tbody td{padding:14px;border-color:#edf0f5}.expense-no{color:#293850;font-weight:750}.expense-money{font-weight:700;white-space:nowrap}.expense-actions,.expense-files{display:flex;align-items:center;gap:5px;flex-wrap:wrap}.expense-actions .btn,.expense-files .btn{width:32px;height:32px;padding:0;display:inline-grid;place-items:center;border-radius:9px}.expense-empty{padding:55px 20px!important}.expense-empty i{display:block;font-size:42px;color:#b8c0cf;margin-bottom:8px}@media(max-width:991px){.expense-summary{grid-template-columns:repeat(2,1fr)}.expense-hero .hero-actions{margin-top:16px;justify-content:flex-start!important}}@media(max-width:575px){.expense-summary{grid-template-columns:1fr}.expense-hero{padding:20px}.expense-title{font-size:21px}.expense-toolbar .btn{flex:1}.expense-table{min-width:980px}}
</style>

<div class="expense-workspace">
<section class="expense-hero mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="expense-kicker">{{ isset($scopedProperty) ? 'Unit Financial Workspace' : 'Accounting Workspace' }}</div>
            <div class="expense-title mt-1">{{ isset($scopedProperty) ? $buildingLabel.' · Unit '.$scopedProperty->name : 'Expense Management' }}</div>
            <div class="expense-subtitle mt-1">{{ isset($scopedProperty) ? 'Track costs, owner charges, approvals and supporting documents for this unit.' : 'Review, approve and report company and unit expenses from one place.' }}</div>
        </div>
        <div class="hero-actions d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ url('/admin/accounting/expenses/report/pdf').(request()->getQueryString() ? '?'.request()->getQueryString() : '') }}" class="btn btn-light"><i class="ri-file-pdf-2-line me-1 text-danger"></i>PDF</a>
            <a href="{{ url('/admin/accounting/expenses/report/csv').(request()->getQueryString() ? '?'.request()->getQueryString() : '') }}" class="btn btn-light"><i class="ri-file-excel-2-line me-1 text-success"></i>CSV</a>
            @can('accounting.manage')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal"><i class="ri-add-line me-1"></i>{{ isset($scopedProperty) ? 'Add New Expense' : 'Record Expense' }}</button>@endcan
        </div>
    </div>
</section>

<div class="expense-summary mb-3">
    <div class="expense-stat"><span class="stat-icon"><i class="ri-wallet-3-line"></i></span><div><small>Total cost · {{ number_format($summary['count']) }} records</small><strong>AED {{ number_format($summary['cost'],2) }}</strong></div></div>
    <div class="expense-stat"><span class="stat-icon"><i class="ri-price-tag-3-line"></i></span><div><small>Recovered / sale</small><strong>AED {{ number_format($summary['sale'],2) }}</strong></div></div>
    <div class="expense-stat"><span class="stat-icon"><i class="ri-line-chart-line"></i></span><div><small>Gross profit</small><strong class="{{ $summary['profit'] >= 0 ? 'text-success' : 'text-danger' }}">AED {{ number_format($summary['profit'],2) }}</strong></div></div>
    <div class="expense-stat"><span class="stat-icon"><i class="ri-time-line"></i></span><div><small>Needs action</small><strong>{{ number_format($summary['pending']) }} pending <span class="text-muted fw-normal">· {{ number_format($summary['recurring']) }} recurring</span></strong></div></div>
</div>

<div class="card expense-panel">
    <div class="expense-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div><h4 class="card-title mb-0">{{ isset($scopedProperty) ? 'Unit Expenses · '.$scopedProperty->name : 'Expense Management' }}</h4>@isset($scopedProperty)<small class="text-muted">{{ $scopedProperty->building?->building_name ?? $scopedProperty->building?->name }} · Every expense recorded here is automatically linked to this unit.</small>@endisset</div>
        <div class="d-flex gap-2">
            @unless(isset($scopedProperty))<a href="{{ url('/admin/accounting/expenses/import') }}" class="btn btn-soft-primary"><i class="ri-upload-cloud-2-line me-1"></i>Import Expenses</a>@endunless
            @if($activeFilterCount)<a href="{{ isset($scopedProperty) ? route('admin.property.expenses',$scopedProperty) : url('/admin/accounting/expenses') }}" class="btn btn-light"><i class="ri-close-line me-1"></i>Clear {{ $activeFilterCount }} filter{{ $activeFilterCount === 1 ? '' : 's' }}</a>@endif
        </div>
    </div>
    <div class="expense-filter">
        <form class="row g-2">
            <div class="col-md-2"><label>Category</label><select name="category" class="form-select"><option value="">All Categories</option>@foreach($expenseCategories as $key => $label)<option value="{{ $key }}" @selected(request('category')===$key)>{{ $label }}</option>@endforeach</select></div>
            @if(isset($scopedProperty))<input type="hidden" name="property_id" value="{{ $scopedProperty->id }}">@else<div class="col-md-2"><label>Unit</label><select name="property_id" class="form-select"><option value="">All Units</option>@foreach($properties as $property)<option value="{{ $property->id }}" @selected(request('property_id')===$property->id)>{{ $property->name }} — {{ $property->building?->building_name ?? $property->building?->name ?? 'No Building' }}</option>@endforeach</select></div>@endif
            <div class="col-md-2"><label>Status</label>
                <select name="approval_status" class="form-select">
                    <option value="">All Status</option>
                    @foreach(['draft' => 'Draft', 'pending' => 'Pending', 'reviewed' => 'Reviewed', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected', 'reversed' => 'Reversed'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('approval_status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label>From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" title="From date"></div>
            <div class="col-md-2"><label>To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" title="To date"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Apply Filters</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 expense-table">
            <thead class="bg-light-subtle"><tr><th>Date</th><th>No.</th><th>Category</th><th>Unit</th><th>Vendor</th><th>Status</th><th>Cost</th><th>Sale</th><th>Profit</th><th>Files</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_date?->format('d M Y') }}</td>
                    <td class="expense-no">{{ $expense->expense_no }}</td>
                    <td>{{ $expenseCategories[$expense->category] ?? ucfirst($expense->category) }}</td>
                    <td>{{ $expense->property?->name ?? '-' }}@if($expense->property)<br><small class="text-muted">{{ $expense->property->building?->building_name ?? $expense->property->building?->name ?? 'No Building' }}</small>@endif</td>
                    <td>{{ $expense->vendor?->name ?? $expense->supplier ?? '-' }}</td>
                    <td>
                        <span class="badge {{ in_array($expense->approval_status, ['approved', 'paid'], true) ? 'bg-success' : ($expense->approval_status === 'rejected' ? 'bg-danger' : ($expense->approval_status === 'reversed' ? 'bg-secondary' : 'bg-warning')) }}">
                            {{ ucfirst(str_replace('_', ' ', $expense->approval_status)) }}
                        </span>
                        @if($expense->needs_review)
                            <span class="badge bg-danger-subtle text-danger border ms-1">Needs Review</span>
                        @endif
                        @if($expense->recurring_enabled)<span class="badge bg-primary-subtle text-primary border ms-1" title="Next: {{ $expense->next_recurrence_date?->format('d M Y') }}"><i class="ri-repeat-line"></i> Monthly</span>@endif
                    </td>
                    <td class="expense-money">AED {{ number_format((float) $expense->gross_amount, 2) }}</td>
                    <td class="expense-money">{{ (float)$expense->sale_gross_amount > 0 ? 'AED '.number_format((float)$expense->sale_gross_amount,2) : '-' }}</td>
                    <td class="expense-money {{ (float)$expense->profit_amount >= 0 ? 'text-success' : 'text-danger' }}">AED {{ number_format((float)$expense->profit_amount,2) }}</td>
                    <td><div class="expense-files">
                        @if($expense->audits->isNotEmpty())<button type="button" class="btn btn-sm btn-soft-info" data-bs-toggle="modal" data-bs-target="#expenseAudit{{ $expense->id }}" title="Audit History"><i class="ri-history-line"></i></button>@endif
                        @if($expense->receipt_path)<a href="{{ route('admin.accounting.expenses.document', [$expense, 'receipt']) }}" target="_blank" class="btn btn-sm btn-soft-primary" title="Receipt"><i class="ri-receipt-line"></i></a>@endif
                        @if($expense->invoice_path)<a href="{{ route('admin.accounting.expenses.document', [$expense, 'invoice']) }}" target="_blank" class="btn btn-sm btn-soft-info" title="Invoice"><i class="ri-file-list-3-line"></i></a>@endif
                        @if($expense->quotation_path)<a href="{{ route('admin.accounting.expenses.document', [$expense, 'quotation']) }}" target="_blank" class="btn btn-sm btn-soft-warning" title="Quotation"><i class="ri-file-paper-2-line"></i></a>@endif
                        @if((float)$expense->sale_gross_amount > 0)<a href="{{ route('admin.accounting.expenses.tax-invoice',$expense) }}" class="btn btn-sm btn-soft-success" title="Download Tax Invoice"><i class="ri-bill-line"></i></a><button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#emailTaxInvoice{{ $expense->id }}" title="Email Tax Invoice"><i class="ri-mail-send-line"></i></button>@endif
                        @if($expense->import_source_file)<a href="{{ route('admin.accounting.expenses.document', [$expense, 'import']) }}" target="_blank" class="btn btn-sm btn-soft-secondary" title="Import Source"><i class="ri-file-upload-line"></i></a>@endif
                    </div></td>
                    <td><div class="expense-actions">
                        @if($expense->owner_approval_status)<span class="badge mb-1 {{ $expense->owner_approval_status==='approved'?'bg-success':($expense->owner_approval_status==='rejected'?'bg-danger':'bg-info') }}">Owner: {{ str($expense->owner_approval_status)->replace('_',' ')->headline() }}</span><br>@endif
                        @if($expense->approval_status !== 'reversed' && (! in_array($expense->approval_status, ['approved', 'paid'], true) || auth()->user()?->role === 'admin'))
                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editExpense{{ $expense->id }}"><i class="ri-edit-line"></i></button>
                        @endif
                        @if(! in_array($expense->approval_status, ['approved', 'paid', 'rejected', 'reversed'], true))
                            <form action="{{ route('admin.accounting.expenses.approve', $expense->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success" title="Approve and post"><i class="ri-check-line"></i></button>
                            </form>
                        @endif
                        @if($expense->landlord_id && !in_array($expense->owner_approval_status,['approved','rejected','owner_paying'],true))<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ownerApproval{{ $expense->id }}" title="Request owner approval"><i class="ri-user-follow-line"></i></button>@endif
                        @if(in_array($expense->approval_status, ['approved', 'paid', 'reversed'], true) && auth()->user()?->role === 'admin')
                            <button type="button" class="btn btn-sm btn-soft-danger" data-bs-toggle="modal" data-bs-target="#deleteExpense{{ $expense->id }}" title="Delete expense permanently"><i class="ri-delete-bin-line"></i></button>
                        @elseif($expense->approval_status !== 'reversed')
                        <form action="{{ url('/admin/accounting/expenses/' . $expense->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense? Linked ledger entry and owner statement debit will also be removed.');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-soft-danger" title="Delete Expense"><i class="ri-delete-bin-line"></i></button>
                        </form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted expense-empty"><i class="ri-receipt-line"></i><strong>No expenses found</strong><div class="small mt-1">Add the first expense or adjust your filters.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $expenses->links('pagination::bootstrap-5') }}</div>
</div>
</div>

<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="post" action="{{ route('admin.accounting.expenses.store') }}" enctype="multipart/form-data">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="expense_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select" required>@foreach($expenseCategories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select"><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Supplier Text</label><input name="supplier" class="form-control"></div>
            @if(isset($scopedProperty))<input type="hidden" name="property_id" value="{{ $scopedProperty->id }}"><div class="col-md-6"><label class="form-label">Unit</label><div class="form-control bg-light fw-semibold"><i class="ri-building-line me-1 text-primary"></i>{{ $scopedProperty->name }} — {{ $scopedProperty->building?->building_name ?? $scopedProperty->building?->name }}</div></div>@else<div class="col-md-6"><label class="form-label">Unit</label><select name="property_id" class="form-select"><option value="">General Company Expense</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->name }} — {{ $property->building?->building_name ?? $property->building?->name ?? 'No Building' }}</option>@endforeach</select></div>@endif
            <div class="col-md-6"><label class="form-label">Booking</label><select name="booking_id" class="form-select"><option value="">No Booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}">{{ $booking->booking_reference }} - {{ $booking->guest_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Paid By</label><select name="responsibility" class="form-select expense-responsibility" required><option value="company">Company</option><option value="owner">Owner</option><option value="tenant_guest">Tenant / Guest</option></select></div>
            <div class="col-md-4"><label class="form-label">Paid From Account</label><select name="paid_from_account_id" class="form-select"><option value="">Select bank/cash</option>@foreach($bankAccounts as $bankAccount)<option value="{{ $bankAccount->id }}">{{ $bankAccount->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Approval Status</label><select name="approval_status" class="form-select"><option value="draft">Draft</option><option value="pending" selected>Pending Approval</option><option value="reviewed">Reviewed</option><option value="approved">Approved</option><option value="paid">Paid</option><option value="rejected">Rejected</option></select></div>
            <input type="hidden" class="expense-default-vat" value="{{ \App\Support\AppSettings::get('default_vat_rate',5) }}">
            <div class="col-12"><div class="border rounded-3 p-3 bg-light-subtle row g-3 mx-0"><div class="col-12 d-flex justify-content-between"><h6 class="mb-0"><i class="ri-shopping-bag-3-line me-1"></i>Supplier Cost</h6><span class="badge bg-dark">COST AREA</span></div><div class="col-md-5"><label class="form-label">Cost Amount</label><input type="number" step="0.01" name="net_amount" class="form-control expense-cost" required><small class="text-muted">Amount on supplier bill.</small></div><div class="col-md-7"><label class="form-label d-block">Cost VAT</label><div class="btn-group w-100" role="group">@foreach(['none'=>'No VAT','included'=>'VAT Included','excluded'=>'VAT Excluded'] as $value=>$label)<input type="radio" class="btn-check expense-vat-mode" name="cost_vat_mode" id="costVat{{ $value }}" value="{{ $value }}" @checked($value==='excluded')><label class="btn btn-outline-primary" for="costVat{{ $value }}">{{ $label }}</label>@endforeach</div></div></div></div>
            <div class="col-12"><div class="border border-primary rounded-3 p-3 bg-primary-subtle row g-3 mx-0"><div class="col-12 d-flex justify-content-between"><h6 class="mb-0 text-primary"><i class="ri-price-tag-3-line me-1"></i>Owner / Customer Sale</h6><span class="badge bg-primary">SALES AREA</span></div><div class="col-md-5"><label class="form-label">Sale Amount</label><input type="number" step="0.01" min="0" name="sale_amount" class="form-control expense-sale"><small class="text-muted">Amount charged onward.</small></div><div class="col-md-7"><label class="form-label d-block">Sale VAT</label><div class="btn-group w-100" role="group">@foreach(['none'=>'No VAT','included'=>'VAT Included','excluded'=>'VAT Excluded'] as $value=>$label)<input type="radio" class="btn-check expense-vat-mode" name="sale_vat_mode" id="saleVat{{ $value }}" value="{{ $value }}" @checked($value==='excluded')><label class="btn btn-outline-primary" for="saleVat{{ $value }}">{{ $label }}</label>@endforeach</div></div></div></div>
            <div class="col-12"><div class="alert alert-light border mb-0 py-2">Profit excluding VAT: <strong class="expense-profit">AED 0.00</strong></div></div>
            <div class="col-md-6"><label class="form-label">Payment Method</label><input name="payment_method" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Transaction Reference</label><input name="transaction_reference" class="form-control"></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input owner-billable" type="checkbox" name="owner_billable" value="1" id="ownerBillable"><label class="form-check-label" for="ownerBillable">Add this expense to owner statement (automatic when paid by Owner)</label></div></div>
            <div class="col-12"><div class="border rounded-3 p-3 bg-light"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="recurring_enabled" value="1" id="recurringEnabled"><label class="form-check-label fw-semibold" for="recurringEnabled"><i class="ri-repeat-line me-1"></i>Repeat automatically every month</label></div><div class="row g-2 mt-1"><div class="col-md-4"><label class="form-label">Billing day</label><input type="number" name="recurrence_day" min="1" max="31" value="{{ now()->day }}" class="form-control"></div><div class="col-md-4"><label class="form-label">Stop after <span class="text-muted">(optional)</span></label><input type="date" name="recurring_until" class="form-control"></div><div class="col-md-4 d-flex align-items-end"><small class="text-muted">A pending owner-billable expense will be created monthly for approval.</small></div></div></div></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
            <div class="col-md-6"><label class="form-label">Receipt</label><input type="file" name="receipt" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Supplier Invoice</label><input type="file" name="invoice" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Quotation</label><input type="file" name="quotation" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save Expense</button></div>
    </form></div>
</div>

@foreach($expenses->filter(fn($expense) => (float) $expense->sale_gross_amount > 0) as $expense)
@php($defaultTaxInvoiceEmail = $expense->landlord?->email ?: $expense->booking?->guest_email)
<div class="modal fade" id="emailTaxInvoice{{ $expense->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="post" action="{{ route('admin.accounting.expenses.tax-invoice.email', $expense) }}">
            @csrf
            <div class="modal-header">
                <div><h5 class="modal-title"><i class="ri-mail-send-line me-1 text-primary"></i>Email Tax Invoice</h5><small class="text-muted">TI-{{ $expense->expense_no }} · PDF will be attached automatically</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-primary d-flex justify-content-between align-items-center"><span>{{ $expense->description ?: ucfirst($expense->category).' service/recovery' }}</span><strong>AED {{ number_format((float)$expense->sale_gross_amount, 2) }}</strong></div>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Recipient Email</label><input type="email" name="recipient" value="{{ $defaultTaxInvoiceEmail }}" class="form-control" placeholder="customer@example.com" required></div>
                    <div class="col-12"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" maxlength="180" value="Tax Invoice TI-{{ $expense->expense_no }} — {{ \App\Support\AppSettings::get('invoice_legal_name', 'PATTERN Vacation Homes Rental') }}"></div>
                    <div class="col-12"><label class="form-label">Message <span class="text-muted">(optional)</span></label><textarea name="message" rows="4" maxlength="2000" class="form-control" placeholder="Add a short note for the recipient..."></textarea></div>
                </div>
                <div class="border rounded-3 p-3 mt-3 bg-light"><div class="d-flex justify-content-between"><span>Subtotal</span><strong>AED {{ number_format((float)$expense->sale_net_amount,2) }}</strong></div><div class="d-flex justify-content-between mt-2"><span>VAT {{ number_format((float)$expense->sale_vat_rate,2) }}%</span><strong>AED {{ number_format((float)$expense->sale_vat_amount,2) }}</strong></div><hr><div class="d-flex justify-content-between text-primary"><strong>Total</strong><strong>AED {{ number_format((float)$expense->sale_gross_amount,2) }}</strong></div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="ri-send-plane-fill me-1"></i>Send Email & PDF</button></div>
        </form>
    </div>
</div>
@endforeach

@foreach($expenses as $expense)
@if($expense->audits->isNotEmpty())
<div class="modal fade" id="expenseAudit{{ $expense->id }}" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">Expense Audit History</h5><small class="text-muted">{{ $expense->expense_no }} · protected changes and reversals</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="timeline">@foreach($expense->audits->sortByDesc('created_at') as $audit)<div class="border rounded-3 p-3 mb-2"><div class="d-flex justify-content-between"><strong>{{ ucwords(str_replace('_',' ',$audit->action)) }}</strong><small class="text-muted">{{ $audit->created_at?->format('d M Y H:i') }}</small></div><p class="mb-1 mt-2">{{ $audit->reason }}</p><small class="text-muted">By {{ $audit->user?->name ?? 'System' }} · IP {{ $audit->ip_address ?: '-' }}</small></div>@endforeach</div></div></div></div></div>
@endif
@if($expense->approval_status !== 'reversed' && (! in_array($expense->approval_status, ['approved', 'paid'], true) || auth()->user()?->role === 'admin'))
<div class="modal fade" id="editExpense{{ $expense->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="post" action="{{ route('admin.accounting.expenses.update', $expense->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Edit Expense {{ $expense->expense_no }}</h5>
                    @if($expense->imported_transaction_id)
                        <small class="text-muted">Imported ID: {{ $expense->imported_transaction_id }}</small>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="expense_date" value="{{ $expense->expense_date?->toDateString() }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select" required>@foreach($expenseCategories as $key => $label)<option value="{{ $key }}" @selected($expense->category === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select"><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected($expense->vendor_id === $vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Supplier Text</label><input name="supplier" value="{{ $expense->supplier }}" class="form-control"></div>
                @if(isset($scopedProperty))<input type="hidden" name="property_id" value="{{ $scopedProperty->id }}"><div class="col-md-4"><label class="form-label">Unit</label><div class="form-control bg-light fw-semibold">{{ $scopedProperty->name }} — {{ $scopedProperty->building?->building_name ?? $scopedProperty->building?->name }}</div></div>@else<div class="col-md-4"><label class="form-label">Unit</label><select name="property_id" class="form-select"><option value="">General Company Expense</option>@foreach($properties as $property)<option value="{{ $property->id }}" @selected($expense->property_id === $property->id)>{{ $property->name }} — {{ $property->building?->building_name ?? $property->building?->name ?? 'No Building' }}</option>@endforeach</select></div>@endif
                <div class="col-md-4"><label class="form-label">Booking</label><select name="booking_id" class="form-select"><option value="">No Booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}" @selected($expense->booking_id === $booking->id)>{{ $booking->booking_reference }} - {{ $booking->guest_name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Paid By</label><select name="responsibility" class="form-select expense-responsibility" required><option value="company" @selected($expense->responsibility === 'company')>Company</option><option value="owner" @selected($expense->responsibility === 'owner')>Owner</option><option value="tenant_guest" @selected($expense->responsibility === 'tenant_guest')>Tenant / Guest</option></select></div>
                <div class="col-md-4"><label class="form-label">Paid From Account</label><select name="paid_from_account_id" class="form-select"><option value="">Select bank/cash</option>@foreach($bankAccounts as $bankAccount)<option value="{{ $bankAccount->id }}" @selected($expense->paid_from_account_id === $bankAccount->id)>{{ $bankAccount->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Status</label>@if(in_array($expense->approval_status,['approved','paid'],true))<input class="form-control" value="{{ ucfirst($expense->approval_status) }} — locked" readonly><input type="hidden" name="approval_status" value="{{ $expense->approval_status }}">@else<select name="approval_status" class="form-select"><option value="draft" @selected($expense->approval_status === 'draft')>Draft</option><option value="pending" @selected($expense->approval_status === 'pending')>Pending</option><option value="reviewed" @selected($expense->approval_status === 'reviewed')>Reviewed</option><option value="approved" @selected($expense->approval_status === 'approved')>Approved</option><option value="paid" @selected($expense->approval_status === 'paid')>Paid</option><option value="rejected" @selected($expense->approval_status === 'rejected')>Rejected</option></select>@endif</div>
                <input type="hidden" class="expense-default-vat" value="{{ \App\Support\AppSettings::get('default_vat_rate',5) }}">
                <div class="col-12"><div class="border rounded-3 p-3 bg-light-subtle row g-3 mx-0"><div class="col-12 d-flex justify-content-between"><h6 class="mb-0"><i class="ri-shopping-bag-3-line me-1"></i>Supplier Cost</h6><span class="badge bg-dark">COST AREA</span></div><div class="col-md-5"><label class="form-label">Cost Amount</label><input type="number" step="0.01" name="net_amount" value="{{ $expense->cost_vat_mode === 'included' ? $expense->gross_amount : $expense->net_amount }}" class="form-control expense-cost" required><small class="text-muted">{{ $expense->cost_vat_mode === 'included' ? 'Supplier total including VAT.' : 'Supplier amount before VAT.' }}</small></div><div class="col-md-7"><label class="form-label d-block">Cost VAT</label><div class="btn-group w-100">@foreach(['none'=>'No VAT','included'=>'VAT Included','excluded'=>'VAT Excluded'] as $value=>$label)<input type="radio" class="btn-check expense-vat-mode" name="cost_vat_mode" id="editCostVat{{ $expense->id }}{{ $value }}" value="{{ $value }}" @checked($expense->cost_vat_mode===$value)><label class="btn btn-outline-primary" for="editCostVat{{ $expense->id }}{{ $value }}">{{ $label }}</label>@endforeach</div></div></div></div>
                <div class="col-12"><div class="border border-primary rounded-3 p-3 bg-primary-subtle row g-3 mx-0"><div class="col-12 d-flex justify-content-between"><h6 class="mb-0 text-primary"><i class="ri-price-tag-3-line me-1"></i>Owner / Customer Sale</h6><span class="badge bg-primary">SALES AREA</span></div><div class="col-md-5"><label class="form-label">Sale Amount</label><input type="number" step="0.01" min="0" name="sale_amount" value="{{ $expense->sale_vat_included ? $expense->sale_gross_amount : $expense->sale_net_amount }}" class="form-control expense-sale"><small class="text-muted">Amount charged onward.</small></div><div class="col-md-7"><label class="form-label d-block">Sale VAT</label><div class="btn-group w-100">@foreach(['none'=>'No VAT','included'=>'VAT Included','excluded'=>'VAT Excluded'] as $value=>$label)<input type="radio" class="btn-check expense-vat-mode" name="sale_vat_mode" id="editSaleVat{{ $expense->id }}{{ $value }}" value="{{ $value }}" @checked($expense->sale_vat_mode===$value)><label class="btn btn-outline-primary" for="editSaleVat{{ $expense->id }}{{ $value }}">{{ $label }}</label>@endforeach</div></div></div></div>
                <div class="col-12"><div class="alert alert-light border mb-0 py-2">Profit excluding VAT: <strong class="expense-profit">AED {{ number_format((float)$expense->profit_amount,2) }}</strong></div></div>
                <div class="col-md-4"><label class="form-label">Transaction Reference</label><input name="transaction_reference" value="{{ $expense->transaction_reference }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Payment Method</label><input name="payment_method" value="{{ $expense->payment_method }}" class="form-control"></div>
                <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input owner-billable" type="checkbox" name="owner_billable" value="1" id="ownerBillable{{ $expense->id }}" @checked($expense->owner_billable)><label class="form-check-label" for="ownerBillable{{ $expense->id }}">Add to owner statement after approval</label></div></div>
                <div class="col-12"><div class="border rounded-3 p-3 bg-light"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="recurring_enabled" value="1" id="recurringEnabled{{ $expense->id }}" @checked($expense->recurring_enabled)><label class="form-check-label fw-semibold" for="recurringEnabled{{ $expense->id }}"><i class="ri-repeat-line me-1"></i>Repeat automatically every month</label></div><div class="row g-2 mt-1"><div class="col-md-4"><label class="form-label">Billing day</label><input type="number" name="recurrence_day" min="1" max="31" value="{{ $expense->recurrence_day ?: $expense->expense_date?->day }}" class="form-control"></div><div class="col-md-4"><label class="form-label">Stop after</label><input type="date" name="recurring_until" value="{{ $expense->recurring_until?->toDateString() }}" class="form-control"></div><div class="col-md-4 d-flex align-items-end"><small class="text-muted">Next creation: {{ $expense->next_recurrence_date?->format('d M Y') ?: 'after saving' }}</small></div></div></div></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control">{{ $expense->description }}</textarea></div>
                <div class="col-md-6"><label class="form-label">Receipt</label><input type="file" name="receipt" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Supplier Invoice</label><input type="file" name="invoice" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Quotation</label><input type="file" name="quotation" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
                @if(in_array($expense->approval_status, ['approved', 'paid'], true))
                    <div class="col-12"><div class="alert alert-warning mb-0"><strong>Protected financial record</strong><br>Editing will synchronize accounting, VAT and the owner statement. Confirm your own Super Admin password and explain the change.</div></div>
                    <div class="col-md-6"><label class="form-label">Super Admin Password</label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
                    <div class="col-md-6"><label class="form-label">Reason for Change</label><input name="change_reason" class="form-control" minlength="5" maxlength="1000" required placeholder="Explain why this approved expense is changing"></div>
                @endif
                @if($expense->imported_payload)
                    <div class="col-12">
                        <div class="alert alert-light border mb-0">
                            <strong>Original imported data is kept for audit.</strong>
                            <pre class="small mb-0 mt-2" style="white-space:pre-wrap;max-height:160px;overflow:auto">{{ json_encode($expense->imported_payload, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Save Changes</button></div>
        </form>
    </div>
</div>
@endif
@if(in_array($expense->approval_status, ['approved', 'paid', 'reversed'], true) && auth()->user()?->role === 'admin')
<div class="modal fade" id="deleteExpense{{ $expense->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="{{ route('admin.accounting.expenses.destroy',$expense) }}">@csrf @method('DELETE')<div class="modal-header"><div><h5 class="modal-title text-danger">Permanently Delete Expense</h5><small class="text-muted">{{ $expense->expense_no }} · This action cannot be undone</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-danger"><strong>The expense will be removed completely.</strong> Its ledger, VAT, owner-statement entries, audit history, receipt and invoice will also be deleted. No reversal entry will be created.</div><label class="form-label">Super Admin Password</label><input type="password" name="current_password" class="form-control mb-3" autocomplete="current-password" required><label class="form-label">Reason for Deletion</label><textarea name="change_reason" class="form-control" rows="3" minlength="5" maxlength="1000" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Delete Permanently</button></div></form></div></div>
@endif
@if($expense->landlord_id && !in_array($expense->owner_approval_status,['approved','rejected','owner_paying'],true))
<div class="modal fade" id="ownerApproval{{ $expense->id }}" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('admin.accounting.expenses.owner-approval',$expense) }}" enctype="multipart/form-data">@csrf<div class="modal-header"><div><h5 class="modal-title">Request Owner Approval</h5><small class="text-muted">{{ $expense->expense_no }} · {{ $expense->property?->name }} · AED {{ number_format((float)$expense->gross_amount,2) }}</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-info"><i class="ri-information-line me-1"></i>The owner can Approve & Deduct, Reject, or choose to arrange/pay personally.</div><div class="row g-3"><div class="col-12"><label class="form-label">Required work, remarks and details</label><textarea name="remarks" class="form-control" rows="4" minlength="5" maxlength="2000" required>{{ $expense->owner_approval_remarks ?: $expense->description }}</textarea></div><div class="col-md-6"><label class="form-label">Decision deadline</label><input type="date" name="deadline" min="{{ today()->toDateString() }}" class="form-control" value="{{ $expense->owner_approval_deadline?->toDateString() }}"></div><div class="col-md-6"><label class="form-label">Quotation @if(!$expense->quotation_path && !$expense->invoice_path)<span class="text-danger">required</span>@endif</label><input type="file" name="quotation" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" @required(!$expense->quotation_path && !$expense->invoice_path)></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="ri-mail-send-line me-1"></i>Send Approval Request</button></div></form></div></div></div>
@endif
@endforeach
@endsection

@push('scripts')
<script>
document.querySelectorAll('.expense-responsibility').forEach((select) => {
    const syncOwnerBilling = () => {
        if (select.value === 'owner') {
            const checkbox = select.closest('form')?.querySelector('.owner-billable');
            if (checkbox) checkbox.checked = true;
        }
    };
    select.addEventListener('change', syncOwnerBilling);
    syncOwnerBilling();
});
document.querySelectorAll('#expenseModal form, [id^="editExpense"] form').forEach((form) => {
    const mode = name => form.querySelector(`[name="${name}"]:checked`)?.value || form.querySelector(`[name="${name}"]`)?.value || 'excluded';
    const net = (amount, rate, vatMode) => vatMode === 'included' ? amount / (1 + rate / 100) : amount;
    const refresh = () => {
        const costAmount = Number(form.querySelector('.expense-cost')?.value || 0);
        const saleAmount = Number(form.querySelector('.expense-sale')?.value || 0);
        const defaultRate = Number(form.querySelector('.expense-default-vat')?.value || 5);
        const costRate = mode('cost_vat_mode') === 'none' ? 0 : defaultRate;
        const saleRate = mode('sale_vat_mode') === 'none' ? 0 : defaultRate;
        const profit = saleAmount > 0 ? net(saleAmount, saleRate, mode('sale_vat_mode')) - net(costAmount, costRate, mode('cost_vat_mode')) : 0;
        form.querySelector('.expense-profit').textContent = `AED ${profit.toFixed(2)}`;
    };
    form.querySelectorAll('.expense-cost,.expense-sale,.expense-vat-mode').forEach(input => input.addEventListener('input', refresh));
    form.querySelectorAll('.expense-vat-mode').forEach(input => input.addEventListener('change', refresh));
    refresh();
});
</script>
@endpush
