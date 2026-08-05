@php
    $user = $profileUser;
    $photo = $user->profile_photo ? \App\Support\MediaStorage::url($user->profile_photo) : asset('default-avatar.png');
    $statusClass = $user->is_active ? 'bg-success' : 'bg-danger';
    $statusText = $user->is_active ? 'Active' : 'Inactive';
    $documentUrl = $user->id_document ? \App\Support\MediaStorage::url($user->id_document) : null;
    $documentName = $user->id_document ? basename($user->id_document) : null;
    $bankDetails = [
        'bank_name' => ['label' => 'Bank Name', 'value' => $user->bank_name],
        'bank_account_holder' => ['label' => 'Account Holder', 'value' => $user->bank_account_holder],
        'bank_account_number' => ['label' => 'Account Number', 'value' => $user->bank_account_number],
        'bank_account_type' => ['label' => 'Account Type', 'value' => $user->bank_account_type],
        'swift_code' => ['label' => 'SWIFT Code', 'value' => $user->swift_code],
        'iban' => ['label' => 'IBAN', 'value' => $user->iban],
        'bank_branch' => ['label' => 'Bank Branch', 'value' => $user->bank_branch],
    ];
@endphp

<div class="row">
    <div class="col-xl-8 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center my-3 gap-3">
                    <img src="{{ $photo }}" alt="{{ $user->name }}" class="rounded-circle avatar-xl img-thumbnail">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $user->name }}</h3>
                        <span class="badge bg-info text-white fs-12 px-2 py-1">{{ $roleLabel }}</span>
                        <span class="badge {{ $statusClass }} text-white fs-12 px-2 py-1">{{ $statusText }}</span>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mt-3">
                    <div class="d-flex flex-wrap gap-2">
                        @if ($user->phone)
                            <a href="tel:{{ $user->phone }}" class="btn btn-primary">
                                <i class="ri-phone-fill me-1"></i>Phone
                            </a>
                        @endif
                        <a href="mailto:{{ $user->email }}" class="btn btn-outline-primary">
                            <i class="ri-mail-fill me-1"></i>Email
                        </a>
                        <a href="{{ $backRoute }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i>Back
                        </a>
                        @if (! empty($welcomeEmailRoute))
                            <form action="{{ $welcomeEmailRoute }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-success">
                                    <i class="ri-mail-send-line me-1"></i>Send Welcome Email Again
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ $editRoute }}" class="btn btn-dark avatar-sm d-flex align-items-center justify-content-center fs-20" title="Edit Profile">
                            <i class="ri-edit-fill"></i>
                        </a>
                    </div>
                </div>

                <div class="row my-4 g-3">
                    <div class="col-lg-3">
                        <p class="text-dark fw-semibold fs-16 mb-1">Email Address :</p>
                        <p class="mb-0">{{ $user->email }}</p>
                    </div>
                    <div class="col-lg-3">
                        <p class="text-dark fw-semibold fs-16 mb-1">Phone Number :</p>
                        <p class="mb-0">{{ $user->phone ?? 'Not provided' }}</p>
                    </div>
                    <div class="col-lg-4">
                        <p class="text-dark fw-semibold fs-16 mb-1">Location :</p>
                        <p class="mb-0">{{ $user->address ?? 'Not provided' }}</p>
                    </div>
                    <div class="col-lg-2">
                        <p class="text-dark fw-semibold fs-16 mb-1">Status :</p>
                        <p class="mb-0"><span class="badge {{ $statusClass }} text-white fs-12 px-2 py-1">{{ $statusText }}</span></p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <h4 class="card-title mb-2">Identity Document</h4>
                        @if ($documentUrl)
                            <div class="d-flex p-2 gap-2 bg-light-subtle align-items-center text-start position-relative border rounded mt-3">
                                <iconify-icon icon="solar:file-check-bold" class="text-danger fs-24"></iconify-icon>
                                <div class="overflow-hidden">
                                    <h4 class="fs-14 mb-1 text-truncate">
                                        <a href="{{ $documentUrl }}" class="text-dark stretched-link" target="_blank">{{ $documentName }}</a>
                                    </h4>
                                    <p class="fs-12 mb-0">Uploaded document</p>
                                </div>
                                <a href="{{ $documentUrl }}" download="{{ $documentName }}" class="ms-auto position-relative">
                                    <i class="ri-download-cloud-line fs-20 text-muted"></i>
                                </a>
                            </div>
                        @else
                            <div class="border rounded p-3 bg-light-subtle mt-3">
                                <p class="text-muted mb-0">No identity document uploaded.</p>
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-8">
                        <h4 class="card-title mb-2">Details :</h4>
                        <p class="mb-1">
                            <i class="ri-cake-line fs-16 me-2 text-success"></i>
                            Date of Birth: {{ $user->dob ? \Carbon\Carbon::parse($user->dob)->format('d M Y') : 'Not provided' }}
                        </p>
                        <p class="mb-1">
                            <i class="ri-pass-valid-line fs-16 me-2 text-success"></i>
                            EID/Passport No: {{ $user->eid_passport_no ?? 'Not provided' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if (isset($landlord, $accountStatementRoute))
            @include('admin.landlords.partials.profile-tabs', [
                'detailsRoute' => route('admin.landlord.show', $landlord->id),
                'accountStatementRoute' => $accountStatementRoute,
                'ownedPropertiesRoute' => $ownedPropertiesRoute ?? route('admin.landlord.owned-properties', $landlord->id),
            ])
        @endif

        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($summaryCards as $index => $card)
                        @php
                            $colors = ['success', 'warning', 'primary'];
                            $icons = ['solar:home-2-bold', 'solar:home-bold', 'solar:money-bag-bold'];
                            $color = $colors[$index % count($colors)];
                            $icon = $icons[$index % count($icons)];
                        @endphp
                        <div class="col-lg-4">
                            <div class="border p-2 rounded h-100">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="avatar bg-{{ $color }} bg-opacity-10 rounded">
                                        <iconify-icon icon="{{ $icon }}" class="fs-28 text-{{ $color }} avatar-title"></iconify-icon>
                                    </div>
                                    <div>
                                        <p class="text-dark fw-semibold fs-16 mb-0">{{ $card['label'] }}</p>
                                        <p class="mb-0">{{ $card['value'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <div class="col-xl-4 col-lg-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Bank Account</h4>
                @if (! empty($bankRoute))
                    <button type="button" class="btn btn-dark avatar-sm d-flex align-items-center justify-content-center fs-20" data-bs-toggle="modal" data-bs-target="#bankDetailsModal" aria-label="Update Bank Details">
                        <i class="ri-edit-fill"></i>
                    </button>
                @endif
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach ($bankDetails as $detail)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <strong>{{ $detail['label'] }}:</strong>
                            <span class="text-muted text-end">{{ $detail['value'] ?: 'Not provided' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

@if (! empty($bankRoute))
    <div class="modal fade" id="bankDetailsModal" tabindex="-1" aria-labelledby="bankDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ $bankRoute }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="bankDetailsModalLabel">Bank Details Form</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @foreach ($bankDetails as $field => $detail)
                            <div class="mb-3">
                                <label for="{{ $field }}" class="form-label">{{ $detail['label'] }}</label>
                                <input type="text" class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ $detail['value'] }}">
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Bank Details</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

