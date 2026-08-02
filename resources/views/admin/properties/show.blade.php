@extends('layouts.app')

@section('content')

@php
    $landlord = $property->landlord ?? null;
    $building = $property->building ?? null;
    $ownerShares = $property->ownerShares ?? collect();

    $asArray = function ($value) {
        if (is_array($value)) {
            return $value;
        }

        if (blank($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    };

    $formatValue = fn ($value) => filled($value) ? $value : 'N/A';
    $formatMoney = fn ($value) => filled($value) ? number_format((float) $value, 2) . ' AED' : 'N/A';
    $formatPercent = fn ($value) => filled($value) ? number_format((float) $value, 2) . '%' : 'N/A';
    $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : 'N/A';
    $fileUrl = fn ($path) => \App\Support\MediaStorage::url($path);

    $photos = $asArray($property->photos);
    $mainPhoto = filled($photos[0] ?? null)
        ? \App\Support\MediaStorage::url($photos[0])
        : asset('assets/images/properties/p-11.jpg');

    $unitName = $property->name ?: 'Unit';
    $buildingName = $building?->building_name ?: 'No Building';
    $locationParts = array_filter([
        $building?->address,
        $property->community,
    ]);
    $locationText = !empty($locationParts) ? implode(', ', $locationParts) : 'Location not added';
    $statusClass = $property->status_class ?: 'bg-secondary';
    $statusTextClass = str_contains($statusClass, 'warning') ? 'text-dark' : 'text-light';
    $monthlyRent = $formatMoney($property->rent);
    $managementFee = filled($property->management_fee_percent)
        ? $formatPercent($property->management_fee_percent)
        : $formatMoney($property->management_fee);

    $amenities = $asArray($property->amenities);
    $additionalFeatures = $asArray($property->additional_features);
    $securityUtilities = $asArray($property->security_utilities);

    $quickStats = [
        ['Bedrooms', $formatValue($property->bedrooms), 'solar:bed-broken', 'primary'],
        ['Bathrooms', $formatValue($property->bathrooms), 'solar:bath-broken', 'primary'],
        ['Area', filled($property->square_foot) ? $property->square_foot . ' sqft' : 'N/A', 'solar:scale-broken', 'secondary'],
        ['Floor', $formatValue($property->floor), 'solar:double-alt-arrow-up-broken', 'secondary'],
    ];

    $detailGroups = [
        'Unit Overview' => [
            ['Status', $property->status_label, 'solar:check-circle-broken', 'success'],
            ['Unit Type', $formatValue($property->category), 'solar:home-angle-broken', 'primary'],
            ['Monthly Rent', $monthlyRent, 'solar:wallet-money-broken', 'warning'],
            ['Management Fee', $managementFee, 'solar:percent-circle-broken', 'info'],
            ['Room No.', $formatValue($property->room_no), 'solar:hashtag-square-broken', 'secondary'],
        ],
        'Layout & Access' => [
            ['Living Rooms', $formatValue($property->living_rooms), 'solar:sofa-2-broken', 'primary'],
            ['Kitchens', $formatValue($property->kitchens), 'solar:chef-hat-broken', 'primary'],
            ['Floor Label', $formatValue($property->unit_floor_label), 'solar:tag-broken', 'secondary'],
            ['Parking', $formatValue($property->parking_number), 'solar:parking-broken', 'success'],
            ['Distance to Road', $formatValue($property->distance_to_road), 'solar:map-arrow-square-broken', 'warning'],
        ],
        'Utilities' => [
            ['WiFi Provider', $formatValue($property->wifi_provider), 'solar:wi-fi-router-broken', 'info'],
            ['WiFi Name', $formatValue($property->wifi_name), 'solar:wi-fi-router-broken', 'info'],
            ['WiFi Account No.', $formatValue($property->wifi_account_no), 'solar:hashtag-square-broken', 'secondary'],
            ['Electricity Provider', $formatValue($property->electricity_provider), 'solar:bolt-broken', 'warning'],
            ['Electricity Account No.', $formatValue($property->electricity_account_no), 'solar:hashtag-square-broken', 'secondary'],
            ['Utilities Cap', $formatMoney($property->utilities_cap), 'solar:bill-list-broken', 'success'],
        ],
        'Compliance' => [
            ['DTCM Permit No.', $formatValue($property->dtcm_permit_no), 'solar:document-text-broken', 'dark'],
            ['DTCM Permit Expiry', $formatDate($property->dtcm_permit_expiry), 'solar:calendar-date-broken', 'danger'],
            ['Created', $formatDate($property->created_at), 'solar:calendar-add-broken', 'secondary'],
            ['Updated', $formatDate($property->updated_at), 'solar:calendar-mark-broken', 'secondary'],
        ],
    ];

    $documents = [
        ['Video', $property->video, 'solar:videocamera-record-broken', 'primary'],
        ['Floor Plan', $property->floor_plan, 'solar:ruler-cross-pen-broken', 'info'],
        ['DTCM Permit', $property->dtcm_unit_permit, 'solar:document-text-broken', 'dark'],
        ['Title Deed', $property->title_deed, 'solar:document-add-broken', 'secondary'],
    ];
@endphp

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1">{{ $buildingName }} - {{ $unitName }}</h4>
                <p class="text-muted mb-0 d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:map-point-wave-bold-duotone" class="fs-18 text-primary"></iconify-icon>
                    {{ $locationText }}
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.property.index') }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Units
                </a>
                <a href="{{ route('admin.property.edit', $property->id) }}" class="btn btn-primary">
                    <i class="ri-edit-line me-1"></i>Edit Unit
                </a>
                <a href="{{ route('admin.property.owner-documents.index', $property->id) }}" class="btn btn-dark">
                    <i class="ri-file-sign-line me-1"></i>Owner Documents
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="position-relative">
                    <img src="{{ $mainPhoto }}" alt="Unit photo" class="img-fluid rounded w-100 object-fit-cover" style="max-height: 320px;">
                    <span class="badge {{ $statusClass }} {{ $statusTextClass }} position-absolute top-0 start-0 m-2 px-3 py-2">
                        {{ $property->status_label }}
                    </span>
                </div>
                <div class="mt-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <p class="text-muted mb-1">Monthly Rent</p>
                            <h4 class="mb-0">{{ $monthlyRent }}</h4>
                        </div>
                        <div class="avatar-lg bg-success-subtle rounded">
                            <iconify-icon icon="solar:wallet-money-bold-duotone" class="fs-30 text-success avatar-title"></iconify-icon>
                        </div>
                    </div>
                    <div class="row g-2 mt-3">
                        @foreach($quickStats as [$label, $value, $icon, $color])
                            <div class="col-6">
                                <div class="border rounded p-2 h-100">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm bg-{{ $color }}-subtle rounded d-flex align-items-center justify-content-center flex-shrink-0">
                                            <iconify-icon icon="{{ $icon }}" class="fs-20 text-{{ $color }}"></iconify-icon>
                                        </div>
                                        <div>
                                            <p class="text-muted small mb-0">{{ $label }}</p>
                                            <p class="fw-semibold text-dark mb-0">{{ $value }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light-subtle">
                <h4 class="card-title mb-0">Unit Owners</h4>
            </div>
            <div class="card-body">
                <div class="vstack gap-3">
                    @forelse($ownerShares as $share)
                        @php
                            $owner = $share->owner;
                            $ownerPhoto = $owner?->profile_photo
                                ? \App\Support\MediaStorage::url($owner->profile_photo)
                                : asset('assets/images/users/avatar-1.jpg');
                        @endphp
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $ownerPhoto }}" alt="Owner photo" class="avatar-md rounded-circle border">
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <h6 class="mb-0">{{ $owner?->name ?? 'Owner' }}</h6>
                                        @if($share->is_primary)
                                            <span class="badge bg-success-subtle text-success">Primary</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ $owner?->email ?: 'Email not added' }}</div>
                                    <div class="text-muted small">{{ $owner?->phone ?: 'Phone not added' }}</div>
                                </div>
                                <span class="badge bg-primary-subtle text-primary">{{ number_format((float) $share->share_percent, 2) }}%</span>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <a href="{{ $owner?->phone ? 'tel:' . $owner->phone : '#!' }}" class="btn btn-soft-primary btn-sm flex-fill {{ $owner?->phone ? '' : 'disabled' }}">
                                    <i class="ri-phone-line me-1"></i>Call
                                </a>
                                <a href="{{ $owner?->email ? 'mailto:' . $owner->email : '#!' }}" class="btn btn-soft-success btn-sm flex-fill {{ $owner?->email ? '' : 'disabled' }}">
                                    <i class="ri-mail-line me-1"></i>Email
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="Owner photo" class="avatar-md rounded-circle border">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $landlord?->name ?? 'Owner not assigned' }}</h6>
                                    <div class="text-muted small">{{ $landlord?->email ?: 'Email not added' }}</div>
                                    <div class="text-muted small">{{ $landlord?->phone ?: 'Phone not added' }}</div>
                                </div>
                                <span class="badge bg-primary-subtle text-primary">100%</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7">
        <div class="card">
            <div class="card-header bg-light-subtle">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h4 class="card-title mb-1">Unit Profile</h4>
                        <p class="text-muted mb-0">Operational information used for bookings, owner documents, and accounts.</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ $property->category ?: 'Unit' }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($detailGroups as $groupTitle => $items)
                        <div class="col-xl-6">
                            <div class="border rounded h-100">
                                <div class="px-3 py-2 bg-light-subtle border-bottom">
                                    <h6 class="mb-0">{{ $groupTitle }}</h6>
                                </div>
                                <div class="p-3">
                                    <div class="vstack gap-2">
                                        @foreach($items as [$label, $value, $icon, $color])
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="avatar-sm bg-{{ $color }}-subtle rounded d-flex align-items-center justify-content-center flex-shrink-0">
                                                    <iconify-icon icon="{{ $icon }}" class="fs-20 text-{{ $color }}"></iconify-icon>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="text-muted small">{{ $label }}</div>
                                                    <div class="fw-semibold text-dark">{{ $value }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light-subtle">
                <h4 class="card-title mb-0">Amenities & Notes</h4>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2">Amenities</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse($amenities as $amenity)
                                    <span class="badge bg-light-subtle text-muted border">{{ $amenity }}</span>
                                @empty
                                    <span class="text-muted small">No amenities added.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2">Security & Utilities</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @if($property->has_security)
                                    <span class="badge bg-success-subtle text-success border">Security Available</span>
                                @endif
                                @forelse($securityUtilities as $securityUtility)
                                    <span class="badge bg-light-subtle text-muted border">{{ $securityUtility }}</span>
                                @empty
                                    @unless($property->has_security)
                                        <span class="text-muted small">No security utilities added.</span>
                                    @endunless
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2">Additional Features</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse($additionalFeatures as $feature)
                                    <span class="badge bg-light-subtle text-muted border">{{ $feature }}</span>
                                @empty
                                    <span class="text-muted small">No additional features added.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="mb-2">Description</h6>
                            <p class="mb-0 text-muted">
                                {!! nl2br(e($property->description ?: 'No description provided for this unit.')) !!}
                            </p>
                            @if(filled($property->additional_notes))
                                <div class="alert alert-light border mt-3 mb-0">
                                    <span class="fw-semibold text-dark">Notes:</span>
                                    {{ $property->additional_notes }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light-subtle">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h4 class="card-title mb-0">Media & Documents</h4>
                    <a href="{{ route('admin.property.owner-documents.index', $property->id) }}" class="btn btn-soft-dark btn-sm">
                        <i class="ri-file-sign-line me-1"></i>Signed Documents
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($documents as [$label, $path, $icon, $color])
                        <div class="col-md-6 col-xl-3">
                            <div class="border rounded p-3 h-100">
                                <div class="avatar-sm bg-{{ $color }}-subtle rounded d-flex align-items-center justify-content-center mb-2">
                                    <iconify-icon icon="{{ $icon }}" class="fs-20 text-{{ $color }}"></iconify-icon>
                                </div>
                                <h6 class="mb-1">{{ $label }}</h6>
                                @if($path)
                                    <a href="{{ $fileUrl($path) }}" target="_blank" class="link-primary small">Open file</a>
                                @else
                                    <span class="text-muted small">Not uploaded</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <h6 class="mb-0">Unit Photos</h6>
                                <span class="text-muted small">{{ count($photos) }} uploaded</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse($photos as $photo)
                                    <a href="{{ \App\Support\MediaStorage::url($photo) }}" target="_blank">
                                        <img src="{{ \App\Support\MediaStorage::url($photo) }}" width="92" height="68" class="rounded border object-fit-cover" alt="Unit photo">
                                    </a>
                                @empty
                                    <span class="text-muted small">No photos uploaded.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
