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
        ['Unit Type', $property->unit_type_label, 'solar:home-angle-broken', 'primary'],
        ['Bathrooms', $formatValue($property->bathrooms), 'solar:bath-broken', 'primary'],
        ['Area', filled($property->square_foot) ? $property->square_foot . ' sqft' : 'N/A', 'solar:scale-broken', 'secondary'],
        ['Floor', $formatValue($property->floor), 'solar:double-alt-arrow-up-broken', 'secondary'],
    ];

    $detailGroups = [
        'Unit Overview' => [
            ['Status', $property->status_label, 'solar:check-circle-broken', 'success'],
            ['Unit Type', $property->unit_type_label, 'solar:home-angle-broken', 'primary'],
            ['Monthly Rent', $monthlyRent, 'solar:wallet-money-broken', 'warning'],
            ['Management Fee', $managementFee, 'solar:percent-circle-broken', 'info'],
            ['Bedroom Count', $formatValue($property->bedrooms), 'solar:bed-broken', 'secondary'],
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
    ];
    $profileChecks = collect([$property->rent,$property->management_fee_percent,$property->bedrooms,$property->bathrooms,$property->square_foot,$property->wifi_name,$property->electricity_account_no,$property->dtcm_permit_no,$property->description]);
    $profileScore = (int) round($profileChecks->filter(fn($value) => filled($value))->count() / max(1, $profileChecks->count()) * 100);
    $permitDays = $property->dtcm_permit_expiry ? now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($property->dtcm_permit_expiry), false) : null;
    $healthTone = $profileScore >= 80 ? 'success' : ($profileScore >= 50 ? 'warning' : 'danger');
@endphp

<style>
.unit-command{--uc-ink:#172432;--uc-violet:#6557e8;--uc-soft:#f4f5fb}.unit-command .uc-hero{position:relative;overflow:hidden;border-radius:24px;padding:28px;background:linear-gradient(120deg,#172432 0%,#253b58 58%,#5a4ccf 100%);color:#fff;box-shadow:0 18px 45px rgba(24,38,57,.18)}.unit-command .uc-hero:after{content:"";position:absolute;width:360px;height:360px;border-radius:50%;right:-140px;top:-220px;background:rgba(255,255,255,.09)}.uc-eyebrow{font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#b9c8dc;font-weight:700}.uc-title{font-size:30px;font-weight:800;letter-spacing:-.03em;margin:5px 0}.uc-location{color:#c9d5e4}.uc-actions{position:relative;z-index:2}.uc-actions .btn{border:0;border-radius:12px;padding:10px 14px;font-weight:600}.uc-actions .btn-light{background:#fff}.uc-actions .btn-glass{background:rgba(255,255,255,.13);color:#fff;border:1px solid rgba(255,255,255,.18)}.uc-status{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:999px;background:rgba(49,196,141,.18);color:#83efc5;font-weight:700}.uc-status:before{content:"";width:8px;height:8px;border-radius:50%;background:#46db9f;box-shadow:0 0 0 5px rgba(70,219,159,.14)}.uc-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:16px 0 22px}.uc-metric{background:#fff;border:1px solid #e8ebf2;border-radius:18px;padding:15px 17px;display:flex;align-items:center;gap:12px;box-shadow:0 8px 24px rgba(31,45,61,.05)}.uc-metric-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;font-size:21px;background:#eeeafd;color:#6557e8}.uc-metric small{display:block;color:#8a96a5}.uc-metric strong{display:block;color:#172432;font-size:16px}.unit-command>.card,.unit-command .row>.col-xl-4>.card,.unit-command .row>.col-xl-8>.card{border:0;border-radius:20px;box-shadow:0 10px 32px rgba(32,44,66,.07);overflow:hidden}.unit-command .card-header{border:0;background:#fff!important;padding:19px 20px}.unit-command .card-body{padding:20px}.unit-command .border.rounded{border-color:#e7eaf1!important;border-radius:15px!important}.unit-command .bg-light-subtle{background:#f7f8fc!important}.unit-command .nav-pills{gap:6px!important}.unit-command .nav-pills .nav-link{border-radius:11px;padding:9px 14px;font-weight:600}.unit-command .nav-pills .nav-link.active{background:#6557e8;box-shadow:0 7px 16px rgba(101,87,232,.22)}.uc-alert{border-left:4px solid #f2b84b;background:#fff9e9;border-radius:14px;padding:12px 15px;color:#6d5622}.uc-mini-action{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #e6e9f0;color:#435166;border-radius:10px;padding:8px 11px;font-weight:600}.uc-mini-action:hover{border-color:#6557e8;color:#6557e8}@media(max-width:991px){.uc-metrics{grid-template-columns:repeat(2,1fr)}.uc-title{font-size:24px}}@media(max-width:575px){.unit-command .uc-hero{padding:20px;border-radius:18px}.uc-metrics{grid-template-columns:1fr}.uc-actions{margin-top:16px}.uc-actions .btn{width:100%}}
</style>
<div class="unit-command">
    <section class="uc-hero mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 position-relative" style="z-index:2">
            <div><div class="uc-eyebrow">Unit Operations Center</div><h1 class="uc-title">{{ $buildingName }} <span class="fw-light">· {{ $unitName }}</span></h1><div class="uc-location"><i class="ri-map-pin-2-line me-1"></i>{{ $locationText }}</div><div class="mt-3 d-flex flex-wrap gap-2"><span class="uc-status">{{ $property->status_label }}</span><span class="badge rounded-pill bg-white bg-opacity-10 px-3 py-2">{{ $property->category ?: $property->unit_type_label }}</span><span class="badge rounded-pill bg-white bg-opacity-10 px-3 py-2">Floor {{ $formatValue($property->floor) }}</span></div></div>
            <div class="uc-actions d-flex flex-wrap justify-content-end gap-2"><a href="{{ route('admin.property.index') }}" class="btn btn-glass"><i class="ri-arrow-left-line me-1"></i>All Units</a><a href="{{ route('admin.property.edit',$property) }}" class="btn btn-light"><i class="ri-edit-line me-1"></i>Edit Unit</a><a href="{{ Route::has('admin.property.document-wallet.index') ? route('admin.property.document-wallet.index',$property) : route('admin.property.owner-documents.index',$property) }}" class="btn btn-glass"><i class="ri-folder-shield-2-line me-1"></i>Documents</a></div>
        </div>
    </section>
    @include('admin.properties.partials.unit-tabs')
    <div class="d-flex flex-wrap gap-2 mb-3"><a class="uc-mini-action" href="{{ route('admin.property.guest-qr',$property) }}"><i class="ri-qr-code-line"></i>Guest QR</a>@if(Route::has('admin.inventory.index'))<a class="uc-mini-action" href="{{ route('admin.inventory.index',['property_id'=>$property->id]) }}"><i class="ri-archive-line"></i>Inventory & Inspections</a>@endif<a class="uc-mini-action text-danger" href="{{ route('admin.property.cancellations.create',$property) }}"><i class="ri-file-close-line"></i>Cancel Agreement</a></div>
    <div class="uc-metrics">
        <div class="uc-metric"><span class="uc-metric-icon"><i class="ri-pulse-line"></i></span><div><small>Profile health</small><strong class="text-{{ $healthTone }}">{{ $profileScore }}% complete</strong></div></div>
        <div class="uc-metric"><span class="uc-metric-icon"><i class="ri-money-dollar-circle-line"></i></span><div><small>Monthly rent</small><strong>{{ $monthlyRent }}</strong></div></div>
        <div class="uc-metric"><span class="uc-metric-icon"><i class="ri-shield-check-line"></i></span><div><small>DTCM permit</small><strong>{{ $permitDays === null ? 'Not configured' : ($permitDays < 0 ? 'Expired' : $permitDays.' days left') }}</strong></div></div>
        <div class="uc-metric"><span class="uc-metric-icon"><i class="ri-image-line"></i></span><div><small>Unit media</small><strong>{{ count($photos) }} photos · {{ collect($documents)->filter(fn($d)=>filled($d[1]))->count() }} files</strong></div></div>
    </div>
    @if($profileScore < 80 || $permitDays === null || $permitDays < 30)<div class="uc-alert mb-3"><i class="ri-alert-line me-2"></i><strong>Action recommended:</strong> {{ $profileScore < 80 ? 'Complete missing unit profile information.' : '' }} {{ $permitDays === null ? 'Add the DTCM permit and expiry.' : ($permitDays < 30 ? 'DTCM permit requires attention.' : '') }}</div>@endif

@if($property->managementCancellations->isNotEmpty())
<div class="card border-danger-subtle mb-3"><div class="card-header d-flex justify-content-between align-items-center"><h4 class="card-title mb-0">Management Agreement Cancellations</h4><span class="badge bg-danger-subtle text-danger">{{ $property->managementCancellations->count() }}</span></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Reference</th><th>Owner</th><th>Effective Date</th><th>Status</th><th></th></tr></thead><tbody>@foreach($property->managementCancellations->sortByDesc('created_at') as $cancellation)<tr><td>{{ $cancellation->reference_no }}</td><td>{{ $cancellation->owner_name }}</td><td>{{ $cancellation->effective_date->format('d M Y') }}</td><td><span class="badge {{ $cancellation->cancelled_at ? 'bg-danger' : ($cancellation->completed_at ? 'bg-success' : 'bg-warning text-dark') }}">{{ $cancellation->status_label }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.property.cancellations.show',[$property,$cancellation]) }}">Track &amp; Manage</a></td></tr>@endforeach</tbody></table></div></div>
@endif

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
                    <h4 class="card-title mb-0">Unit Media</h4>
                    <a href="{{ Route::has('admin.property.document-wallet.index') ? route('admin.property.document-wallet.index', $property->id) : route('admin.property.owner-documents.index', $property->id) }}" class="btn btn-soft-dark btn-sm">
                        <i class="ri-folder-shield-2-line me-1"></i>Document Wallet
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
</div>

@endsection
