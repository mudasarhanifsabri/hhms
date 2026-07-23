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
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accountEntries as $entry)
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No owner account entries yet.</td>
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

<div class="modal fade" id="accountEntryModal" tabindex="-1" aria-labelledby="accountEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ $accountEntryRoute }}" method="POST">
                @csrf
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
                            </select>
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
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Short note for this statement entry">{{ old('description') }}</textarea>
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
