@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0">Operations Dashboard</h4>
    <span class="text-muted">As of {{ now()->format('d M Y H:i') }} ({{ config('app.timezone') }})</span>
</div>

<div class="card border-warning-subtle">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h4 class="card-title mb-1">Bookings Expiring Within 3 Days</h4>
            <p class="text-muted small mb-0">Contact the guest to confirm an extension or scheduled checkout.</p>
        </div>
        <span class="badge bg-warning-subtle text-warning fs-13">{{ $expiringBookings->count() }} requiring follow-up</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="bg-light-subtle"><tr><th>Checkout</th><th>Guest</th><th>Unit</th><th>Booking</th><th>Status</th><th class="text-end">Contact & Action</th></tr></thead>
                <tbody>
                @forelse($expiringBookings as $booking)
                    @php
                        $daysLeft = today()->diffInDays($booking->check_out, false);
                        $phone = preg_replace('/\D+/', '', (string) $booking->guest_phone);
                        $unit = $booking->property?->name ?? 'Unit';
                        $building = $booking->property?->building?->building_name ?? $booking->property?->building?->name;
                        $stayName = trim($unit.($building ? ' - '.$building : ''));
                        $extensionMessage = rawurlencode("Hello {$booking->guest_name}, this is PATTERN Vacation Homes. Your booking {$booking->booking_reference} for {$stayName} is scheduled to end on {$booking->check_out?->format('d M Y')}. Would you like to extend your stay? Please reply so we can confirm availability and prepare the extension invoice.");
                        $checkoutMessage = rawurlencode("Hello {$booking->guest_name}, this is PATTERN Vacation Homes. Please confirm your scheduled checkout for booking {$booking->booking_reference} at {$stayName} on {$booking->check_out?->format('d M Y')}. Kindly reply with your expected checkout time.");
                    @endphp
                    <tr>
                        <td><strong>{{ $booking->check_out?->format('d M Y') }}</strong><small class="d-block text-muted">{{ $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00' }}</small></td>
                        <td><span class="fw-semibold">{{ $booking->guest_name }}</span><small class="d-block text-muted">{{ $booking->guest_phone }}</small></td>
                        <td>{{ $unit }}<small class="d-block text-muted">{{ $building ?: 'No building' }}</small></td>
                        <td><a href="{{ route('admin.booking.show', $booking) }}">{{ $booking->booking_reference }}</a></td>
                        <td>
                            <span class="badge {{ $daysLeft <= 0 ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' }}">{{ $daysLeft <= 0 ? 'Due today' : $daysLeft.' '.Str::plural('day', $daysLeft).' left' }}</span>
                            <small class="d-block text-muted mt-1">{{ str($booking->status)->replace('_', ' ')->headline() }}</small>
                        </td>
                        <td class="text-end text-nowrap">
                            @if($phone)
                                <a class="btn btn-sm btn-light" href="tel:+{{ $phone }}" title="Call guest"><i class="ri-phone-line"></i></a>
                                <a class="btn btn-sm btn-outline-success" target="_blank" rel="noopener" href="https://wa.me/{{ $phone }}?text={{ $extensionMessage }}"><i class="ri-whatsapp-line me-1"></i>Ask extension</a>
                                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://wa.me/{{ $phone }}?text={{ $checkoutMessage }}"><i class="ri-checkbox-circle-line me-1"></i>Confirm checkout</a>
                            @else
                                <span class="text-danger small">Phone missing</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4"><i class="ri-calendar-check-line fs-24 d-block mb-1"></i>No bookings expire during the next three days.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-2">Properties</p>
                        <h3 class="text-dark fw-bold mb-0">{{ number_format($totalProperties) }}</h3>
                    </div>
                    <div class="avatar-md bg-light bg-opacity-50 rounded">
                        <iconify-icon icon="solar:buildings-2-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <span class="badge bg-success-subtle text-success">Booked status {{ number_format($propertiesRented) }}</span>
                    <span class="badge bg-warning-subtle text-warning">Available status {{ number_format($propertiesVacant) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-2">Users</p>
                        <h3 class="text-dark fw-bold mb-0">{{ number_format($totalRegisteredUsers) }}</h3>
                    </div>
                    <div class="avatar-md bg-light bg-opacity-50 rounded">
                        <iconify-icon icon="solar:users-group-two-rounded-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="badge bg-light text-dark">Landlords {{ number_format($landlordCount) }}</span>
                    <span class="badge bg-light text-dark">Agents {{ number_format($agentCount) }}</span>
                    <span class="badge bg-light text-dark">Tenants {{ number_format($tenantCount) }}</span>
                    <span class="badge bg-light text-dark">Maintainers {{ number_format($maintainerCount) }}</span>
                    <span class="badge bg-light text-dark">Other roles {{ number_format($otherUsers) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-2">DTCM Expiring</p>
                        <h3 class="text-dark fw-bold mb-0">{{ number_format($upcomingDtcmExpiry) }}</h3>
                    </div>
                    <div class="avatar-md bg-light bg-opacity-50 rounded">
                        <iconify-icon icon="solar:calendar-mark-broken" class="fs-32 text-danger avatar-title"></iconify-icon>
                    </div>
                </div>
                <p class="text-muted mb-0 mt-3">Latest wallet permits: today through {{ today()->addDays(30)->format('d M Y') }}</p>
                <a href="{{ route('admin.property.dtcm-permits') }}" class="small">View permit list</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-2">Current Occupancy</p>
                        <h3 class="text-dark fw-bold mb-0">
                            {{ $occupancyPercent }}%
                        </h3>
                    </div>
                    <div class="avatar-md bg-light bg-opacity-50 rounded">
                        <iconify-icon icon="solar:chart-2-broken" class="fs-32 text-success avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" aria-valuenow="{{ $occupancyPercent }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $occupancyPercent }}%"></div>
                </div>
                <p class="small text-muted mt-2 mb-0">{{ $occupiedUnits }} checked-in units / {{ $totalProperties }} total units. Future reservations excluded.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @foreach (['Arrivals due today' => $arrivalsToday, 'Departures due today' => $departuresToday, 'Overdue checkouts' => $overdueDepartures] as $label => $count)
        <div class="col-md-4"><div class="card"><div class="card-body">
            <p class="text-muted mb-2">{{ $label }}</p><h3 class="mb-0">{{ number_format($count) }}</h3>
        </div></div></div>
    @endforeach
</div>
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Recent Properties</h4>
                <a href="{{ route('admin.property.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th>Property</th>
                                <th>Status</th>
                                <th>Listed Rent (AED)</th>
                                <th>DTCM Expiry</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentProperties as $property)
                                <tr>
                                    <td class="fw-medium">{{ $property->name }}<small class="d-block text-muted">{{ $property->building?->building_name ?? 'No building' }}</small></td>
                                    <td>
                                        <span class="badge {{ in_array($property->status, ['booked', 'rented']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                            {{ str($property->status)->replace('_', ' ')->headline() }}
                                        </span>
                                    </td>
                                    <td>{{ $property->rent !== null ? number_format($property->rent, 2) : 'N/A' }}</td>
                                    <td>{{ $property->wallet_expiry ? \Carbon\Carbon::parse($property->wallet_expiry)->format('d M Y') : 'No wallet expiry' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.property.show', $property->id) }}" class="btn btn-sm btn-light">
                                            <iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No properties found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Quick Actions</h4>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('admin.property.create') }}" class="btn btn-primary">Add Property</a>
                <a href="{{ route('admin.landlord.create') }}" class="btn btn-outline-primary">Add Landlord</a>
                <a href="{{ route('admin.agent.create') }}" class="btn btn-outline-primary">Add Agent</a>
                <a href="{{ route('admin.tenant.create') }}" class="btn btn-outline-primary">Add Tenant</a>
            </div>
        </div>
    </div>
</div>
@endsection
