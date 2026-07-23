@extends('layouts.portal', [
    'portalTitle' => 'Owner Portal',
    'portalEyebrow' => 'Owner',
    'portalHeading' => 'Owner Dashboard',
])

@section('content')
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
                        <strong>{{ $entry->description }}</strong>
                        <p>{{ $entry->entry_date?->format('d M Y') }}</p>
                    </div>
                    <span>{{ $entry->direction === 'credit' ? '+' : '-' }} AED {{ number_format((float) $entry->amount, 2) }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">No statement entries yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
