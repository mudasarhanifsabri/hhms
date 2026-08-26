<div class="card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h4 class="card-title mb-0">Owner Account Statement</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $statementPdfRoute }}" class="btn btn-primary">
                    <i class="ri-download-2-line me-1"></i>PDF
                </a>
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#accountEntryModal">
                    <i class="ri-add-line me-1"></i>Add Entry
                </button>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.landlord.account-statement', $landlord->id) }}" class="row g-2 mt-3 align-items-end">
            <div class="col-lg-3">
                <label for="date_from" class="form-label">From</label>
                <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-lg-3">
                <label for="date_to" class="form-label">To</label>
                <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-lg-2">
                <label for="per_page" class="form-label">Per Page</label>
                <select id="per_page" name="per_page" class="form-control">
                    @foreach ([10, 25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Apply</button>
                <a href="{{ route('admin.landlord.account-statement', $landlord->id) }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="border p-2 rounded h-100">
                    <p class="text-muted mb-1">Total Credit</p>
                    <h4 class="mb-0 text-success">{{ number_format($accountTotals['credit'], 2) }} AED</h4>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border p-2 rounded h-100">
                    <p class="text-muted mb-1">Total Debit</p>
                    <h4 class="mb-0 text-danger">{{ number_format($accountTotals['debit'], 2) }} AED</h4>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border p-2 rounded h-100">
                    <p class="text-muted mb-1">Current Balance</p>
                    <h4 class="mb-0 {{ $accountTotals['balance'] >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format($accountTotals['balance'], 2) }} AED</h4>
                </div>
            </div>
        </div>
        <div class="alert alert-info d-flex flex-wrap justify-content-between gap-3 align-items-center">
            <div><strong>Owner Loan Management</strong><div class="small">Furnishing and owner advances are recovered automatically through the running owner balance as rental-income credits are posted.</div></div>
            <div class="d-flex flex-wrap gap-4">
                <span><small class="d-block text-muted">Loans / Advances</small><strong>AED {{ number_format($ownerLoanSummary['advanced'], 2) }}</strong></span>
                <span><small class="d-block text-muted">Direct Repayments</small><strong>AED {{ number_format($ownerLoanSummary['repaid'], 2) }}</strong></span>
                <span><small class="d-block text-muted">Total Owner Receivable</small><strong class="text-danger">AED {{ number_format($ownerLoanSummary['receivable'], 2) }}</strong></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light-subtle">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Property</th>
                        <th>Reference</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accountEntries as $entry)
                        @php
                            $invoiceUrl = \App\Support\MediaStorage::url($entry->invoice_attachment);
                            $receiptUrl = \App\Support\MediaStorage::url($entry->receipt_attachment);
                            $hasAttachments = $invoiceUrl || $receiptUrl;
                        @endphp
                        <tr>
                            <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $entry->direction === 'credit' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $entry->type_label }}
                                </span>
                                @if ($entry->description)
                                    <p class="text-muted fs-12 mb-0">{{ $entry->description }}</p>
                                @endif
                            </td>
                            <td>{{ $entry->property?->name ?? 'General' }}</td>
                            <td>{{ $entry->reference ?? '-' }}</td>
                            <td class="text-end text-success">
                                {{ $entry->direction === 'credit' ? number_format((float) $entry->amount, 2) : '-' }}
                            </td>
                            <td class="text-end text-danger">
                                {{ $entry->direction === 'debit' ? number_format((float) $entry->amount, 2) : '-' }}
                            </td>
                            <td class="text-end fw-semibold">{{ number_format((float) $entry->balance_after, 2) }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm {{ $hasAttachments ? 'btn-soft-primary' : 'btn-light' }}" data-bs-toggle="modal" data-bs-target="#statementAttachments{{ $entry->id }}">
                                        <i class="ri-eye-line me-1"></i>View
                                    </button>
                                    <form method="POST" action="{{ url('/admin/accounting/owner-statements/entries/'.$entry->id) }}" onsubmit="return confirm('Delete this owner statement entry? The owner balance will be recalculated.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger" title="Delete statement entry"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No owner account entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($accountEntries, 'links'))
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <p class="text-muted mb-0">
                    Showing {{ $accountEntries->firstItem() ?? 0 }} to {{ $accountEntries->lastItem() ?? 0 }} of {{ $accountEntries->total() }} entries
                </p>
                {{ $accountEntries->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

@foreach ($accountEntries as $entry)
    @php
        $invoiceUrl = \App\Support\MediaStorage::url($entry->invoice_attachment);
        $receiptUrl = \App\Support\MediaStorage::url($entry->receipt_attachment);
    @endphp
    <div class="modal fade" id="statementAttachments{{ $entry->id }}" tabindex="-1" aria-labelledby="statementAttachments{{ $entry->id }}Label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="statementAttachments{{ $entry->id }}Label">Statement Attachments</h5>
                        <div class="text-muted small">{{ $entry->entry_date?->format('d M Y') }} · {{ $entry->type_label }} · {{ $entry->reference ?? 'No reference' }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <h6 class="mb-0"><i class="ri-file-list-3-line me-1 text-primary"></i>Invoice</h6>
                                    @if ($invoiceUrl)
                                        <a href="{{ $invoiceUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">Open</a>
                                    @endif
                                </div>
                                @if ($invoiceUrl)
                                    <div class="text-muted small text-break">{{ basename($entry->invoice_attachment) }}</div>
                                @else
                                    <div class="text-muted">No invoice attached.</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <h6 class="mb-0"><i class="ri-receipt-line me-1 text-success"></i>Receipt</h6>
                                    @if ($receiptUrl)
                                        <a href="{{ $receiptUrl }}" target="_blank" class="btn btn-sm btn-outline-success">Open</a>
                                    @endif
                                </div>
                                @if ($receiptUrl)
                                    <div class="text-muted small text-break">{{ basename($entry->receipt_attachment) }}</div>
                                @else
                                    <div class="text-muted">No receipt attached.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="accountEntryModal" tabindex="-1" aria-labelledby="accountEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ $accountEntryRoute }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountEntryModalLabel">Owner Account Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label for="entry_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="entry_date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-lg-4">
                            <label for="type" class="form-label">Entry Type</label>
                            <select class="form-control" id="type" name="type" required>
                                @foreach ($accountEntryTypes as $type => $label)
                                    <option value="{{ $type }}" @selected(old('type') === $type)>{{ $label }}</option>
                                @endforeach
                                <option value="__custom__" @selected(old('type') === '__custom__')>+ Add Custom Category</option>
                            </select>
                            <small class="text-muted">Use Furnishing / Setup Cost to recover owner-approved furnishing spend from future rental income.</small>
                        </div>
                        <div class="col-lg-4">
                            <label for="custom_type" class="form-label">Custom Category</label>
                            <input type="text" class="form-control" id="custom_type" name="custom_type" value="{{ old('custom_type') }}" placeholder="Used only for Add Custom Category">
                        </div>
                        <div class="col-lg-4">
                            <label for="custom_direction" class="form-label">Custom Category Side</label>
                            <select class="form-control" id="custom_direction" name="custom_direction"><option value="debit" @selected(old('custom_direction')==='debit')>Debit - owner owes / deduction</option><option value="credit" @selected(old('custom_direction')==='credit')>Credit - owner income / payment</option></select>
                        </div>
                        <div class="col-lg-4">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="{{ old('amount') }}" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="property_id" class="form-label">Property</label>
                            <select class="form-control" id="property_id" name="property_id">
                                <option value="">General owner account</option>
                                @foreach ($relatedProperties as $property)
                                    <option value="{{ $property->id }}" @selected((string) old('property_id') === (string) $property->id)>{{ $property->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" value="{{ old('reference') }}" placeholder="Receipt, bill or transfer reference">
                        </div>
                        <div class="col-lg-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Example: Furnishing package for Unit 1207, recover from future rental income">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-lg-6">
                            <label for="invoice_attachment" class="form-label">Invoice Attachment</label>
                            <input type="file" class="form-control" id="invoice_attachment" name="invoice_attachment" accept=".pdf,image/*">
                            <small class="text-muted">PDF, JPG, PNG or WebP</small>
                        </div>
                        <div class="col-lg-6">
                            <label for="receipt_attachment" class="form-label">Receipt Attachment</label>
                            <input type="file" class="form-control" id="receipt_attachment" name="receipt_attachment" accept=".pdf,image/*">
                            <small class="text-muted">Payment proof or receipt</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
