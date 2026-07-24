@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Owner Statement Preview</h4>
        @if($owner)
            <a class="btn btn-primary" href="{{ route('admin.accounting.owner-statements.pdf', ['landlord_id' => $owner->id, 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString()]) }}"><i class="ri-download-2-line me-1"></i>Download Full PDF</a>
        @endif
    </div>
    <div class="card-body border-bottom">
        <form class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">Owner</label><select name="landlord_id" class="form-select">@foreach($owners as $candidate)<option value="{{ $candidate->id }}" @selected($owner?->id===$candidate->id)>{{ $candidate->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">From</label><input type="date" name="date_from" value="{{ $from->toDateString() }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">To</label><input type="date" name="date_to" value="{{ $to->toDateString() }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-soft-primary w-100">Preview</button></div>
        </form>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
            <div><h5 class="mb-1">{{ $owner?->name ?? 'No owner selected' }}</h5><p class="text-muted mb-0">{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</p></div>
            <div class="text-end"><span class="text-muted">Statement Total</span><h4 class="mb-0">AED {{ number_format($entries->flatten(1)->sum(fn($entry) => $entry->direction === 'credit' ? (float) $entry->amount : -(float) $entry->amount), 2) }}</h4></div>
        </div>
        @forelse($entries as $propertyEntries)
            @php($property = $propertyEntries->first()?->property)
            <div class="border rounded mb-3">
                <div class="p-3 bg-light-subtle d-flex justify-content-between">
                    <strong>{{ $property?->name ?? 'General Owner Ledger' }}</strong>
                    <span>Balance: AED {{ number_format($propertyEntries->sum(fn($entry) => $entry->direction === 'credit' ? (float) $entry->amount : -(float) $entry->amount), 2) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Reference</th><th>Debit</th><th>Credit</th></tr></thead>
                        <tbody>@foreach($propertyEntries as $entry)<tr><td>{{ $entry->entry_date?->format('d M Y') }}</td><td>{{ $entry->type_label }}</td><td>{{ $entry->description }}</td><td>{{ $entry->reference }}</td><td>{{ $entry->direction === 'debit' ? 'AED '.number_format((float)$entry->amount, 2) : '-' }}</td><td>{{ $entry->direction === 'credit' ? 'AED '.number_format((float)$entry->amount, 2) : '-' }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="text-center text-muted py-4 mb-0">No owner statement entries for this period.</p>
        @endforelse
    </div>
</div>
@endsection
