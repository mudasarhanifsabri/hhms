@extends('layouts.portal', [
    'portalTitle' => 'Owner Portal',
    'portalEyebrow' => 'Owner',
    'portalHeading' => 'Owner Dashboard',
])

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('landlord.app') }}" class="btn btn-primary"><i class="ri-smartphone-line me-1"></i> Open Mobile Owner App</a>
</div>
<div class="portal-stat-grid">
    <div class="portal-card portal-stat"><div><span>Properties</span><strong>{{ $properties->count() }}</strong></div><i class="ri-community-line fs-28 text-primary"></i></div>
    <div class="portal-card portal-stat"><div><span>Bookings</span><strong>{{ $bookings->count() }}</strong></div><i class="ri-calendar-check-line fs-28 text-success"></i></div>
    <div class="portal-card portal-stat"><div><span>Balance</span><strong>AED {{ number_format($balance, 2) }}</strong></div><i class="ri-wallet-3-line fs-28 text-info"></i></div>
    <div class="portal-card portal-stat"><div><span>Available</span><strong>{{ $properties->where('status', 'available')->count() }}</strong></div><i class="ri-home-smile-line fs-28 text-warning"></i></div>
</div>

<div class="portal-grid">
    <section class="portal-card" id="properties">
        <h4>Owned Properties</h4>
        <div class="portal-list">
            @forelse($properties as $property)
                <div class="portal-list-item">
                    <div>
                        <strong>{{ $property->name }}</strong>
                        <p>{{ $property->building?->name ?? $property->building?->building_name ?? 'Building' }}</p>
                    </div>
                    <span class="badge {{ $property->status_class }} text-white">{{ $property->status_label }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">No owned properties found.</p>
            @endforelse
        </div>
    </section>
    <section class="portal-card" id="statement">
        <h4>Recent Statement</h4>
        <div class="portal-list">
            @forelse($entries as $entry)
                <div class="portal-list-item">
                    <div>
                        <strong>{{ $entry->type_label }}</strong>
                        <p class="mb-1">{{ $entry->description ?: 'No additional description' }}</p>
                        <p>{{ $entry->entry_date?->format('d M Y') }} · {{ $entry->property?->name ?? 'General account' }}@if($entry->reference) · {{ $entry->reference }}@endif</p>
                    </div>
                    <span>{{ $entry->direction === 'credit' ? '+' : '-' }} AED {{ number_format((float) $entry->amount, 2) }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">No statement entries yet.</p>
            @endforelse
        </div>
    </section>
</div>

<section class="portal-card mt-3" id="documents">
    <h4>Unit Document Wallet</h4>
    <div class="portal-list">
        @foreach($unitDocuments as $document)
            <div class="portal-list-item">
                <div><strong>{{ $document->title }}</strong><p>{{ $document->property?->building?->building_name }} - {{ $document->property?->name }} · {{ $document->expires_at ? 'Expires '.$document->expires_at->format('d M Y') : 'No expiry' }}</p></div>
                <div class="d-flex align-items-center gap-2"><span class="badge {{ $document->expiry_status === 'expired' ? 'bg-danger' : ($document->expiry_status === 'expiring' ? 'bg-warning' : 'bg-success') }}">{{ str($document->expiry_status)->headline() }}</span><a href="{{ \App\Support\MediaStorage::url($document->file_path) }}" target="_blank" class="btn btn-sm btn-dark">Open</a></div>
            </div>
        @endforeach
        @forelse($documents as $document)
            <div class="portal-list-item">
                <div>
                    <strong>{{ $document->title }}</strong>
                    <p>{{ $document->property?->name }} - {{ $document->reference_no }}</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $document->status === 'signed' ? 'bg-success' : 'bg-warning' }}">{{ $document->status_label }}</span>
                    <a href="{{ route('owner-documents.show', $document->signing_token) }}" target="_blank" class="btn btn-sm btn-primary">Open</a>
                </div>
            </div>
        @empty
            @if($unitDocuments->isEmpty())
            <p class="text-muted mb-0">No owner documents yet.</p>
            @endif
        @endforelse
    </div>
</section>
@endsection
