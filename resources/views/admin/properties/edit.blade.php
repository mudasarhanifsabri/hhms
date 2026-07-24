@extends('layouts.app')

@section('title', 'Edit Unit')

@section('content')
<form action="{{ route('admin.property.update', $property->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Edit Unit</h4>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label for="landlord_id" class="form-label">Assign Landlord</label>
                    <select class="form-control" id="landlord_id" name="landlord_id" required>
                        @foreach ($landlords as $landlord)
                            <option value="{{ $landlord->id }}" @selected(old('landlord_id', $property->landlord_id) == $landlord->id)>
                                {{ $landlord->name }} ({{ $landlord->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-6">
                    <label for="building_id" class="form-label">Building</label>
                    <select class="form-control" id="building_id" name="building_id" required>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}" @selected(old('building_id', $property->building_id) == $building->id)>
                                {{ $building->building_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @php
                    $oldOwnerIds = old('owner_ids');
                    $oldOwnerShares = old('owner_shares');
                    $ownerRows = collect();

                    if (is_array($oldOwnerIds)) {
                        foreach ($oldOwnerIds as $index => $ownerId) {
                            $ownerRows->push(['owner_id' => $ownerId, 'share_percent' => $oldOwnerShares[$index] ?? '']);
                        }
                    } else {
                        $ownerRows = $property->ownerShares->map(fn ($share) => [
                            'owner_id' => $share->owner_id,
                            'share_percent' => $share->share_percent,
                        ]);
                    }

                    if ($ownerRows->isEmpty()) {
                        $ownerRows->push(['owner_id' => $property->landlord_id, 'share_percent' => 100]);
                    }
                @endphp

                <div class="col-12">
                    <div class="border rounded p-3 bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label mb-0">Unit Owners & Share %</label>
                                <div class="text-muted small">Use this when a unit has more than one owner. Total share should be 100%.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-soft-primary" id="addOwnerShare">
                                <i class="ri-add-line me-1"></i>Add Owner
                            </button>
                        </div>
                        <div id="ownerShareRows" class="vstack gap-2">
                            @foreach($ownerRows as $row)
                                <div class="row g-2 owner-share-row align-items-center">
                                    <div class="col-md-8">
                                        <select class="form-control" name="owner_ids[]">
                                            <option value="">Select owner</option>
                                            @foreach($landlords as $landlord)
                                                <option value="{{ $landlord->id }}" @selected($row['owner_id'] == $landlord->id)>
                                                    {{ $landlord->name }} ({{ $landlord->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" max="100" name="owner_shares[]" class="form-control" value="{{ $row['share_percent'] }}" placeholder="Share">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-1 d-grid">
                                        <button type="button" class="btn btn-light remove-owner-share" title="Remove">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <label for="name" class="form-label">Unit Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $property->name) }}" required>
                </div>

                <div class="col-lg-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status" required>
                        @foreach(\App\Models\Property::STATUSES as $status => $label)
                            <option value="{{ $status }}" @selected(old('status', $property->status) === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4">
                    <label for="category" class="form-label">Category</label>
                    <input type="text" id="category" name="category" class="form-control" value="{{ old('category', $property->category) }}">
                </div>

                <div class="col-lg-4">
                    <label for="rent" class="form-label">Rent</label>
                    <input type="number" step="0.01" id="rent" name="rent" class="form-control" value="{{ old('rent', $property->rent) }}">
                </div>

                <div class="col-lg-4">
                    <label for="management_fee_percent" class="form-label">Management Fee %</label>
                    <input type="number" step="0.01" min="0" max="100" id="management_fee_percent" name="management_fee_percent" class="form-control" value="{{ old('management_fee_percent', $property->management_fee_percent) }}">
                </div>

                <div class="col-lg-4">
                    <label for="dtcm_permit_expiry" class="form-label">DTCM Permit Expiry</label>
                    <input type="date" id="dtcm_permit_expiry" name="dtcm_permit_expiry" class="form-control" value="{{ old('dtcm_permit_expiry', optional($property->dtcm_permit_expiry)->format('Y-m-d')) }}">
                </div>

                <div class="col-lg-4">
                    <label for="community" class="form-label">Community</label>
                    <input type="text" id="community" name="community" class="form-control" value="{{ old('community', $property->community) }}">
                </div>

                <div class="col-lg-4">
                    <label for="room_no" class="form-label">No. Room</label>
                    <input type="text" id="room_no" name="room_no" class="form-control" value="{{ old('room_no', $property->room_no) }}">
                </div>

                <div class="col-lg-4">
                    <label for="unit_floor_label" class="form-label">Floor Label</label>
                    <input type="text" id="unit_floor_label" name="unit_floor_label" class="form-control" value="{{ old('unit_floor_label', $property->unit_floor_label) }}">
                </div>

                <div class="col-lg-4">
                    <label for="parking_number" class="form-label">Parking Number</label>
                    <input type="text" id="parking_number" name="parking_number" class="form-control" value="{{ old('parking_number', $property->parking_number) }}">
                </div>

                <div class="col-lg-4">
                    <label for="utilities_cap" class="form-label">Utilities Cap</label>
                    <input type="number" step="0.01" min="0" id="utilities_cap" name="utilities_cap" class="form-control" value="{{ old('utilities_cap', $property->utilities_cap) }}">
                </div>

                <div class="col-lg-4">
                    <label for="wifi_name" class="form-label">WiFi Name</label>
                    <input type="text" id="wifi_name" name="wifi_name" class="form-control" value="{{ old('wifi_name', $property->wifi_name) }}">
                </div>

                <div class="col-lg-4">
                    <label for="wifi_password" class="form-label">WiFi Password</label>
                    <input type="text" id="wifi_password" name="wifi_password" class="form-control" value="{{ old('wifi_password', $property->wifi_password) }}">
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4">{{ old('description', $property->description) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="{{ route('admin.property.index') }}" class="btn btn-danger">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Unit</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
const ownerRows = document.getElementById('ownerShareRows');
const addOwnerShare = document.getElementById('addOwnerShare');
if (ownerRows && addOwnerShare) {
    addOwnerShare.addEventListener('click', function () {
        const row = ownerRows.querySelector('.owner-share-row').cloneNode(true);
        row.querySelectorAll('select, input').forEach((input) => input.value = '');
        ownerRows.appendChild(row);
    });

    ownerRows.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-owner-share');
        if (!removeButton) return;
        if (ownerRows.querySelectorAll('.owner-share-row').length === 1) return;
        removeButton.closest('.owner-share-row').remove();
    });
}
</script>
@endpush
