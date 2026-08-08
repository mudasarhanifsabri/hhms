@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

@php
    $hasPreview = $previewRows->isNotEmpty();
    $importableRows = $previewRows->reject(fn ($row) => ($row['status'] ?? null) === 'duplicate')->values();
@endphp

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h4 class="card-title mb-1">Import Expenses</h4>
            <p class="text-muted mb-0">CSV and XLSX rows are imported as drafts. PDF/XLS files are added as review drafts with the source file attached.</p>
        </div>
        <a href="{{ route('admin.accounting.expenses') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Back to Expenses</a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.accounting.expenses.import.preview') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <div class="col-lg-7">
                <label class="form-label">Upload CSV, Excel, or PDF</label>
                <input type="file" name="expense_file" class="form-control" accept=".csv,.txt,.xlsx,.xls,.pdf" required>
                <small class="text-muted">CSV/XLSX: parsed transaction rows. PDF/XLS: saved as draft source file for manual review.</small>
            </div>
            <div class="col-lg-3">
                <button class="btn btn-primary w-100"><i class="ri-eye-line me-1"></i>Preview Import</button>
            </div>
        </form>
    </div>
</div>

@if($hasPreview)
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h4 class="card-title mb-1">Preview: {{ $fileName }}</h4>
            <p class="text-muted mb-0">
                {{ $previewRows->where('status', 'new')->count() }} new,
                {{ $previewRows->where('status', 'needs_review')->count() }} needs review,
                {{ $previewRows->where('status', 'duplicate')->count() }} duplicate.
            </p>
        </div>
        @if($sourcePath)
            <a href="{{ \App\Support\MediaStorage::url($sourcePath) }}" target="_blank" class="btn btn-soft-secondary"><i class="ri-file-upload-line me-1"></i>Open Source</a>
        @endif
    </div>
    <div class="card-body border-bottom">
        <form action="{{ route('admin.accounting.expenses.import.confirm') }}" method="POST" class="row g-3 align-items-end">
            @csrf
            <input type="hidden" name="source_file" value="{{ $sourcePath }}">
            <input type="hidden" name="source_type" value="{{ $sourceType }}">
            <textarea name="rows" hidden>{{ json_encode($previewRows->values(), JSON_UNESCAPED_SLASHES) }}</textarea>
            <div class="col-lg-3">
                <label class="form-label">Default Unit</label>
                <select name="default_property_id" class="form-select">
                    <option value="">No unit assigned</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}">{{ $property->building?->name ? $property->building->name . ' - ' : '' }}{{ $property->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Default Category</label>
                <select name="default_category" class="form-select">
                    <option value="">Use detected category</option>
                    @foreach($expenseCategories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Paid From</label>
                <select name="default_paid_from_account_id" class="form-select">
                    <option value="">Select later</option>
                    @foreach($bankAccounts as $bankAccount)
                        <option value="{{ $bankAccount->id }}">{{ $bankAccount->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="default_owner_billable" value="1" id="importOwnerBillable">
                    <label class="form-check-label" for="importOwnerBillable">Deduct from owner statement after approval</label>
                </div>
                <button class="btn btn-primary w-100" @disabled($importableRows->isEmpty())>
                    Import {{ $importableRows->count() }} Draft Expense(s)
                </button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle">
                <tr>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Merchant / Supplier</th>
                    <th>Category</th>
                    <th>Reference</th>
                    <th class="text-end">VAT</th>
                    <th class="text-end">Total</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($previewRows as $row)
                    <tr>
                        <td>
                            @if($row['status'] === 'duplicate')
                                <span class="badge bg-secondary">Duplicate</span>
                            @elseif($row['status'] === 'needs_review')
                                <span class="badge bg-warning">Needs Review</span>
                            @else
                                <span class="badge bg-success">New</span>
                            @endif
                        </td>
                        <td>{{ $row['expense_date'] ?: '-' }}</td>
                        <td>{{ $row['supplier'] ?: '-' }}</td>
                        <td>{{ $expenseCategories[$row['category']] ?? ucfirst($row['category'] ?? 'other') }}</td>
                        <td>{{ $row['transaction_reference'] ?: $row['imported_transaction_id'] ?: '-' }}</td>
                        <td class="text-end">AED {{ number_format((float) ($row['vat_amount'] ?? 0), 2) }}</td>
                        <td class="text-end">AED {{ number_format((float) ($row['gross_amount'] ?? 0), 2) }}</td>
                        <td class="text-muted small">{{ $row['description'] ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
