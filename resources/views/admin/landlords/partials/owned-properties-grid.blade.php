@if ($relatedProperties->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ $propertiesTitle }}</h4>
        </div>
    </div>
    <div class="row mt-3">
        @foreach ($relatedProperties as $property)
            <div class="col-lg-4">
                <div class="card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar bg-light rounded">
                                <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                            </div>
                            <div>
                                <a href="{{ route('admin.property.show', $property->id) }}" class="text-dark fw-medium fs-16">{{ $property->name }}</a>
                                <p class="text-muted mb-0">{{ $property->category ?? 'Property' }}</p>
                            </div>
                        </div>
                        <div class="row mt-3 g-2">
                            <div class="col-6">
                                <span class="badge bg-light-subtle text-muted border fs-12">
                                    <iconify-icon icon="solar:bed-broken" class="align-middle fs-16"></iconify-icon>
                                    {{ $property->bedrooms ?? 0 }} Beds
                                </span>
                            </div>
                            <div class="col-6">
                                <span class="badge bg-light-subtle text-muted border fs-12">
                                    <iconify-icon icon="solar:bath-broken" class="align-middle fs-16"></iconify-icon>
                                    {{ $property->bathrooms ?? 0 }} Bath
                                </span>
                            </div>
                            <div class="col-6">
                                <span class="badge {{ $property->status === 'rented' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} fs-12">
                                    {{ ucfirst($property->status) }}
                                </span>
                            </div>
                            <div class="col-6">
                                <span class="badge bg-primary-subtle text-primary fs-12">
                                    {{ $property->rent ? number_format($property->rent, 0) : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if (method_exists($relatedProperties, 'links'))
        <div class="mt-2">
            {{ $relatedProperties->links() }}
        </div>
    @endif
@else
    <div class="card">
        <div class="card-body text-center text-muted py-4">
            No owned properties found.
        </div>
    </div>
@endif
