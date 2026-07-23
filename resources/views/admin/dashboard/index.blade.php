@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
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
                    <span class="badge bg-success-subtle text-success">Rented {{ number_format($propertiesRented) }}</span>
                    <span class="badge bg-warning-subtle text-warning">Vacant {{ number_format($propertiesVacant) }}</span>
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
                <p class="text-muted mb-0 mt-3">Permits expiring in the next 30 days</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-2">Occupancy</p>
                        <h3 class="text-dark fw-bold mb-0">
                            {{ $totalProperties > 0 ? round(($propertiesRented / $totalProperties) * 100) : 0 }}%
                        </h3>
                    </div>
                    <div class="avatar-md bg-light bg-opacity-50 rounded">
                        <iconify-icon icon="solar:chart-2-broken" class="fs-32 text-success avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $totalProperties > 0 ? round(($propertiesRented / $totalProperties) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
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
                                <th>Rent</th>
                                <th>DTCM Expiry</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentProperties as $property)
                                <tr>
                                    <td class="fw-medium">{{ $property->name }}</td>
                                    <td>
                                        <span class="badge {{ $property->status === 'rented' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                            {{ ucfirst($property->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $property->rent ? number_format($property->rent, 2) : 'N/A' }}</td>
                                    <td>{{ $property->dtcm_permit_expiry?->format('d M Y') ?? 'N/A' }}</td>
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
