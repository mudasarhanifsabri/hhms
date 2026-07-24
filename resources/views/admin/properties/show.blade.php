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

        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    };

    // Photos
    $photos = $asArray($property->photos);
    $mainPhoto = !empty($photos)
        ? asset('storage/' . $photos[0])
        : asset('assets/images/properties/p-11.jpg');

    // Status / category labels
    $statusLabel = $property->status ?? null; // 'rented' / 'vacant' if you use it
    $categoryLabel = $property->category ?? null;

    $badgeText = $categoryLabel
        ? ucfirst($categoryLabel)
        : ($statusLabel ? ucfirst($statusLabel) : 'Unit');

    // Price
    $price = $property->rent;

    // Amenities / facilities
    $amenities = $asArray($property->amenities);
    $additionalFeatures = $asArray($property->additional_features);
    $securityUtilities = $asArray($property->security_utilities);
    $formatValue = fn ($value) => filled($value) ? $value : 'N/A';
    $formatMoney = fn ($value) => filled($value) ? number_format((float) $value, 2) . ' AED' : 'N/A';
    $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : 'N/A';

    $unitDetailGroups = [
        'Unit Overview' => [
            ['Status', ucfirst($property->status ?? 'N/A'), 'solar:check-circle-broken', 'success'],
            ['Unit Type', $formatValue($property->category), 'solar:home-angle-broken', 'primary'],
            ['Monthly Rent', $formatMoney($property->rent), 'solar:wallet-money-broken', 'warning'],
            ['Management Fee', $property->management_fee_percent !== null ? number_format((float) $property->management_fee_percent, 2) . '%' : 'N/A', 'solar:percent-circle-broken', 'info'],
        ],
        'Layout & Size' => [
            ['Bedrooms', $formatValue($property->bedrooms), 'solar:bed-broken', 'primary'],
            ['Bathrooms', $formatValue($property->bathrooms), 'solar:bath-broken', 'primary'],
            ['Living Rooms', $formatValue($property->living_rooms), 'solar:sofa-2-broken', 'primary'],
            ['Kitchens', $formatValue($property->kitchens), 'solar:chef-hat-broken', 'primary'],
            ['Square Foot', filled($property->square_foot) ? $property->square_foot . ' sqft' : 'N/A', 'solar:scale-broken', 'secondary'],
            ['Floor', $formatValue($property->floor), 'solar:double-alt-arrow-up-broken', 'secondary'],
        ],
        'Location & Access' => [
            ['Building', $formatValue($building?->building_name), 'solar:buildings-3-broken', 'primary'],
            ['Community', $formatValue($property->community), 'solar:map-point-broken', 'info'],
            ['Floor Label', $formatValue($property->unit_floor_label), 'solar:tag-broken', 'secondary'],
            ['Parking', $formatValue($property->parking_number), 'solar:parking-broken', 'success'],
            ['Distance to Road', $formatValue($property->distance_to_road), 'solar:map-arrow-square-broken', 'warning'],
        ],
        'Utilities & Compliance' => [
            ['DTCM Permit No.', $formatValue($property->dtcm_permit_no), 'solar:document-text-broken', 'dark'],
            ['DTCM Permit Expiry', $formatDate($property->dtcm_permit_expiry), 'solar:calendar-date-broken', 'danger'],
            ['WiFi Provider', $formatValue($property->wifi_provider), 'solar:wi-fi-router-broken', 'info'],
            ['WiFi Account No.', $formatValue($property->wifi_account_no), 'solar:hashtag-square-broken', 'secondary'],
            ['Electricity Provider', $formatValue($property->electricity_provider), 'solar:bolt-broken', 'warning'],
            ['Electricity Account No.', $formatValue($property->electricity_account_no), 'solar:hashtag-square-broken', 'secondary'],
            ['Utilities Cap', $formatMoney($property->utilities_cap), 'solar:bill-list-broken', 'success'],
        ],
    ];
@endphp

<div class="row">
    <div class="col-xl-3 col-lg-4">
        {{-- OWNER CARD --}}
        <div class="card">
            <div class="card-header bg-light-subtle">
                <h4 class="card-title mb-0">Unit Owners</h4>
            </div>
            <div class="card-body">
                <div class="vstack gap-3">
                    @forelse($ownerShares as $share)
                        <div class="d-flex align-items-center gap-3 border rounded p-2">
                            <img src="{{ $share->owner?->profile_photo ? asset('storage/'.$share->owner->profile_photo) : asset('assets/images/users/avatar-1.jpg') }}"
                                 alt=""
                                 class="avatar-md rounded-circle border">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">{{ $share->owner?->name ?? 'Owner' }}</div>
                                <div class="text-muted small">{{ $share->owner?->email }}</div>
                                <div class="text-muted small">{{ $share->owner?->phone }}</div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">{{ number_format((float) $share->share_percent, 2) }}%</span>
                        </div>
                    @empty
                        <div class="d-flex align-items-center gap-3 border rounded p-2">
                            <img src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="" class="avatar-md rounded-circle border">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">{{ $landlord->name ?? 'N/A' }}</div>
                                <div class="text-muted small">{{ $landlord->email ?? '' }}</div>
                                <div class="text-muted small">{{ $landlord->phone ?? '' }}</div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">100%</span>
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer bg-light-subtle">
                <div class="row g-2">
                    <div class="col-lg-6">
                        <a href="{{ isset($landlord->phone) ? 'tel:'.$landlord->phone : '#!' }}"
                           class="btn btn-primary w-100">
                            <iconify-icon icon="solar:phone-calling-bold-duotone" class="align-middle fs-18"></iconify-icon>
                            Call Us
                        </a>
                    </div>
                    <div class="col-lg-6">
                        <a href="#!" class="btn btn-success w-100">
                            <iconify-icon icon="solar:chat-round-dots-bold-duotone" class="align-middle fs-16"></iconify-icon>
                            Message
                        </a>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('admin.property.owner-documents.index', $property->id) }}" class="btn btn-dark w-100">
                            <i class="ri-file-sign-line me-1"></i>Owner Documents
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedule tour --}}
        <div class="card">
            <div class="card-header bg-light-subtle">
                <h4 class="card-title">Schedule A Tour</h4>
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <input type="text" id="schedule-date" class="form-control" placeholder="dd-mm-yyyy">
                    </div>
                    <div class="mb-3">
                        <input type="text" id="schedule-time" class="form-control" placeholder="12:00 PM">
                    </div>
                    <div class="mb-3">
                        <input type="text" id="schedule-name" name="schedule-name" class="form-control" placeholder="Your Full Name">
                    </div>
                    <div class="mb-3">
                        <input type="email" id="schedule-email" name="schedule-email" class="form-control" placeholder="Email">
                    </div>
                    <div class="mb-3">
                        <input type="number" id="schedule-number" name="schedule-number" class="form-control" placeholder="Number">
                    </div>
                    <div>
                        <textarea class="form-control" id="schedule-textarea" rows="5" placeholder="Message"></textarea>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light-subtle">
                <a href="#!" class="btn btn-primary w-100">Send Information</a>
            </div>
        </div>
    </div>

    <div class="col-xl-9 col-lg-8">
        <div class="card">
            <div class="card-body">
                {{-- MAIN IMAGE --}}
                <div class="position-relative">
                    <img src="{{ $mainPhoto }}" alt="" class="img-fluid rounded">
                    <span class="position-absolute top-0 start-0 p-2">
                        <span class="badge bg-warning text-light px-2 py-1 fs-13">
                            {{ $badgeText }}
                        </span>
                    </span>
                </div>

                {{-- TITLE + ADDRESS --}}
                <div class="d-flex flex-wrap justify-content-between my-3 gap-2">
                    <div>
                        <a href="#!" class="fs-18 text-dark fw-medium">
                            {{ optional($property->building)->building_name ?? 'No Building' }} - {{ $property->name ?? 'Unit Title' }}
                        </a>
                        <p class="d-flex align-items-center gap-1 mt-1 mb-0">
                            <iconify-icon icon="solar:map-point-wave-bold-duotone" class="fs-18 text-primary"></iconify-icon>
                            {{ $building->building_name ?? '' }}
                            @if(!empty($building?->location))
                                , {{ $building->location }}
                            @endif
                        </p>
                    </div>
                    <div>
                        {{-- action buttons --}}
                        <ul class="list-inline float-end d-flex gap-1 mb-0 align-items-center">
                            <li class="list-inline-item fs-20 dropdown">
                                <a href="javascript: void(0);" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-dark fs-20" data-bs-toggle="modal" data-bs-target="#videocall">
                                    <iconify-icon icon="solar:share-bold-duotone"></iconify-icon>
                                </a>
                            </li>
                            <li class="list-inline-item fs-20 dropdown">
                                <a href="javascript: void(0);" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-danger fs-20" data-bs-toggle="modal" data-bs-target="#voicecall">
                                    <iconify-icon icon="solar:heart-angle-bold-duotone"></iconify-icon>
                                </a>
                            </li>
                            <li class="list-inline-item fs-20 dropdown">
                                <a href="{{ route('admin.property.owner-documents.index', $property->id) }}" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-success fs-20" title="Owner Documents">
                                    <iconify-icon icon="solar:document-add-broken"></iconify-icon>
                                </a>
                            </li>
                            <li class="list-inline-item fs-20 dropdown">
                                <a data-bs-toggle="offcanvas" href="#user-profile" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-warning fs-20">
                                    <iconify-icon icon="solar:star-bold-duotone"></iconify-icon>
                                </a>
                            </li>
                            <li class="list-inline-item fs-20 dropdown d-none d-md-flex">
                                <a href="javascript: void(0);" class="dropdown-toggle arrow-none text-dark" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-more-2-fill"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript: void(0);"><i class="ri-user-6-line me-2"></i>View Profile</a>
                                    <a class="dropdown-item" href="javascript: void(0);"><i class="ri-music-2-line me-2"></i>Media, Links and Docs</a>
                                    <a class="dropdown-item" href="javascript: void(0);"><i class="ri-search-2-line me-2"></i>Search</a>
                                    <a class="dropdown-item" href="javascript: void(0);"><i class="ri-image-line me-2"></i>Wallpaper</a>
                                    <a class="dropdown-item" href="javascript: void(0);"><i class="ri-arrow-right-circle-line me-2"></i>More</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- PRICE --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm bg-success-subtle rounded">
                        <iconify-icon icon="solar:wallet-money-bold-duotone" class="fs-24 text-success avatar-title"></iconify-icon>
                    </div>
                    <p class="fw-medium text-dark fs-18 mb-0">
                        {{ $price ? number_format($price, 2) . ' AED' : 'Price on request' }}
                    </p>
                </div>

              {{-- QUICK STATS ROW --}}
<div class="bg-light-subtle p-2 mt-3 rounded border border-dashed">
    <div class="row align-items-center text-center g-2">

        {{-- Bedrooms --}}
        <div class="col-xl-2 col-lg-3 col-md-6 col-6 border-end">
            <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:bed-broken" class="fs-18 text-primary"></iconify-icon>
                {{ $property->bedrooms ?? 0 }} Bedroom
            </p>
        </div>

        {{-- Bathrooms --}}
        <div class="col-xl-2 col-lg-3 col-md-6 col-6 border-end">
            <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:bath-broken" class="fs-18 text-primary"></iconify-icon>
                {{ $property->bathrooms ?? 0 }} Bathroom
            </p>
        </div>

        {{-- Living Rooms --}}
        <div class="col-xl-2 col-lg-3 col-md-6 col-6 border-end">
            <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:sofa-2-broken" class="fs-18 text-primary"></iconify-icon>
                {{ $property->living_rooms ?? 0 }} Living
            </p>
        </div>

        {{-- Kitchens --}}
        <div class="col-xl-2 col-lg-3 col-md-6 col-6 border-end">
            <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:chef-hat-broken" class="fs-18 text-primary"></iconify-icon>
                {{ $property->kitchens ?? 0 }} Kitchen
            </p>
        </div>

        {{-- Area (sqft) --}}
        <div class="col-xl-2 col-lg-3 col-md-6 col-6 border-end">
            <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:scale-broken" class="fs-18 text-primary"></iconify-icon>
                {{ $property->square_foot ?? 0 }} sqft
            </p>
        </div>

        {{-- Status (rented/vacant/category) --}}
        <div class="col-xl-2 col-lg-3 col-md-6 col-6">
            <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:check-circle-broken" class="fs-18 text-primary"></iconify-icon>
                {{ ucfirst($property->status ?? $badgeText) }}
            </p>
        </div>

    </div>
</div>

               {{-- AMENITIES / FACILITIES --}}
<h5 class="text-dark fw-medium mt-3">AMENITIES / FACILITIES</h5>

<div class="d-flex flex-wrap align-items-center gap-2 mt-3">

    {{-- MAIN AMENITIES --}}
    @if(!empty($amenities))
        @foreach($amenities as $amenity)
            <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1">
                {{ $amenity }}
            </span>
        @endforeach
    @endif

    {{-- SECURITY UTILITIES --}}
    @if(!empty($securityUtilities))
        @foreach($securityUtilities as $sec)
            <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1">
                🔒 {{ $sec }}
            </span>
        @endforeach
    @endif

    {{-- ADDITIONAL FEATURES --}}
    @if(!empty($additionalFeatures))
        @foreach($additionalFeatures as $feature)
            <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1">
                ⭐ {{ $feature }}
            </span>
        @endforeach
    @endif

    {{-- HAS SECURITY --}}
    @if(!empty($property->has_security))
        <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1">
            Security: {{ $property->has_security ? 'Yes' : 'No' }}
        </span>
    @endif

    {{-- DISTANCE TO ROAD --}}
    @if(!empty($property->distance_to_road))
        <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1">
            📍 {{ $property->distance_to_road }}
        </span>
    @endif

    {{-- NOTES --}}
    @if(!empty($property->additional_notes))
        <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1">
            📝 {{ $property->additional_notes }}
        </span>
    @endif


    {{-- FALLBACK WHEN NO DATA --}}
    @if(
        empty($amenities) &&
        empty($securityUtilities) &&
        empty($additionalFeatures) &&
        empty($property->has_security) &&
        empty($property->distance_to_road) &&
        empty($property->additional_notes)
    )
        <span class="text-muted fs-13">No amenities or facilities added.</span>
    @endif

</div>


                {{-- DESCRIPTION --}}
                <h5 class="text-dark fw-medium mt-3">Unit Details</h5>
                <p class="mt-2">
                    {!! nl2br(e($property->description ?? 'No description provided for this unit.')) !!}
                </p>

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <a href="#!" class="link-primary fw-medium">View More Detail <i class="ri-arrow-right-line"></i></a>
                    <div>
                        <p class="mb-0 d-flex align-items-center gap-1">
                            <iconify-icon icon="solar:calendar-date-broken" class="fs-18 text-primary"></iconify-icon>
                            {{ optional($property->created_at)->format('d M Y') }}
                        </p>
                    </div>
                </div>

                {{-- FULL DETAILS --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
                    <div>
                        <h5 class="text-dark fw-semibold mb-1">Full Unit Details</h5>
                        <p class="text-muted mb-0">Complete operational profile for this unit.</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        {{ $property->name ?? 'Unit' }}
                    </span>
                </div>

                <div class="row g-3 mt-1">
                    @foreach($unitDetailGroups as $groupTitle => $items)
                        <div class="col-xl-6">
                            <div class="card border shadow-none h-100 mb-0">
                                <div class="card-header bg-light-subtle py-2">
                                    <h6 class="card-title mb-0">{{ $groupTitle }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="vstack gap-2">
                                        @foreach($items as [$label, $value, $icon, $color])
                                            <div class="d-flex align-items-start gap-3 border-bottom pb-2">
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

                <div class="card border shadow-none mt-3 mb-0">
                    <div class="card-header bg-light-subtle py-2">
                        <h6 class="card-title mb-0">Owners, Media & Documents</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-2">Owners</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($ownerShares as $share)
                                            <span class="badge bg-light-subtle text-muted border px-2 py-1">
                                                {{ $share->owner?->name ?? 'Owner' }} - {{ number_format((float) $share->share_percent, 2) }}%
                                            </span>
                                        @empty
                                            <span class="badge bg-light-subtle text-muted border px-2 py-1">
                                                {{ $landlord->name ?? 'N/A' }} - 100%
                                            </span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-2">Documents</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($property->video)
                                            <a href="{{ asset('storage/'.$property->video) }}" target="_blank" class="btn btn-sm btn-soft-primary">View Video</a>
                                        @endif
                                        @if($property->floor_plan)
                                            <a href="{{ asset('storage/'.$property->floor_plan) }}" target="_blank" class="btn btn-sm btn-soft-info">Floor Plan</a>
                                        @endif
                                        @if($property->dtcm_unit_permit)
                                            <a href="{{ asset('storage/'.$property->dtcm_unit_permit) }}" target="_blank" class="btn btn-sm btn-soft-dark">DTCM Permit</a>
                                        @endif
                                        @if($property->title_deed)
                                            <a href="{{ asset('storage/'.$property->title_deed) }}" target="_blank" class="btn btn-sm btn-soft-secondary">Title Deed</a>
                                        @endif
                                        @if(!$property->video && !$property->floor_plan && !$property->dtcm_unit_permit && !$property->title_deed)
                                            <span class="text-muted">No documents uploaded.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <div class="text-muted small mb-2">Photos</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($photos as $img)
                                            <a href="{{ asset('storage/'.$img) }}" target="_blank">
                                                <img src="{{ asset('storage/'.$img) }}" width="78" height="58" class="rounded border object-fit-cover" alt="Unit photo">
                                            </a>
                                        @empty
                                            <span class="text-muted">No photos uploaded.</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- MAP (currently static) --}}
<div class="row">
    <div class="col-lg-12">
        <div class="mapouter">
            <div class="gmap_canvas mb-2">
                <iframe class="gmap_iframe rounded"
                        width="100%"
                        style="height: 400px;"
                        frameborder="0"
                        scrolling="no"
                        marginheight="0"
                        marginwidth="0"
                        src="https://maps.google.com/maps?width=1980&amp;height=400&amp;hl=en&amp;q=University of Oxford&amp;t=&amp;z=14&amp;ie=UTF8&amp;iwloc=B&amp;output=embed">
                </iframe>
            </div>
        </div>
    </div>
</div>

@endsection
