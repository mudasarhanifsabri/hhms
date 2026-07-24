<div class="card shadow-sm border-0">
    <div class="card-header">
        <h4 class="card-title mb-0">Property Information</h4>
    </div>
    <div class="card-body">
        <div class="row g-3">

            <!-- Landlord Dropdown with Email -->
            <div class="col-lg-6">
                <label for="landlord_id" class="form-label">Assign Landlord</label>
                <select class="form-control" id="landlord_id" name="landlord_id" data-choices data-placeholder="Select Landlord">
                    <option value="">Select Landlord</option>
                    @foreach ($landlords as $landlord)
                        <option value="{{ $landlord->id }}" {{ old('landlord_id', $property->landlord_id ?? '') == $landlord->id ? 'selected' : '' }}>
                            {{ $landlord->name }} ({{ $landlord->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Building Dropdown -->
            <div class="col-lg-6">
                <label for="building_id" class="form-label mb-0">Building</label>
                <select class="form-control" id="building_id" name="building_id" data-choices data-placeholder="Select Building">
                    <option value="">Select Building</option>
                    @foreach($buildings as $building)
                        <option value="{{ $building->id }}" {{ old('building_id', $property->building_id ?? '') == $building->id ? 'selected' : '' }}>
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
                } elseif (isset($property) && $property->relationLoaded('ownerShares')) {
                    $ownerRows = $property->ownerShares->map(fn ($share) => [
                        'owner_id' => $share->owner_id,
                        'share_percent' => $share->share_percent,
                    ]);
                }

                if ($ownerRows->isEmpty()) {
                    $ownerRows->push(['owner_id' => old('landlord_id', $property->landlord_id ?? ''), 'share_percent' => 100]);
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

            <!-- Unit No -->
            <div class="col-lg-6">
                <label for="unit_no" class="form-label">Unit Name</label>
                <input type="text" id="unit_no" name="name" class="form-control" placeholder="Enter Unit Number or Name" value="{{ old('name', $property->name ?? '') }}">
            </div>

            <!-- Property Category -->
            <div class="col-lg-6">
                <label for="category" class="form-label">Property Category</label>
                <select class="form-control" id="category" name="category" data-choices data-placeholder="Select Category">
                    <option value="">Select Category</option>
                    <option value="Villas" {{ old('category') == 'Villas' ? 'selected' : '' }}>Villas</option>
                    <option value="Residences" {{ old('category') == 'Residences' ? 'selected' : '' }}>Residences</option>
                    <option value="Bungalow" {{ old('category') == 'Bungalow' ? 'selected' : '' }}>Bungalow</option>
                    <option value="Apartment" {{ old('category') == 'Apartment' ? 'selected' : '' }}>Apartment</option>
                    <option value="Penthouse" {{ old('category') == 'Penthouse' ? 'selected' : '' }}>Penthouse</option>
                </select>
            </div>

            <!-- Rent -->
            <div class="col-lg-4">
                <label for="rent" class="form-label">Rent</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ri-money-dollar-circle-line"></i></span>
                    <input type="number" id="rent" name="rent" class="form-control" placeholder="Enter rent amount" value="{{ old('rent') }}">
                </div>
            </div>

            <!-- Management Fee -->
            <div class="col-lg-4">
                <label for="management_fee_percent" class="form-label">Management Fee %</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ri-percent-line"></i></span>
                    <input type="number" step="0.01" min="0" max="100" id="management_fee_percent" name="management_fee_percent" class="form-control" placeholder="Fee percent from rent" value="{{ old('management_fee_percent') }}">
                </div>
            </div>

            <!-- Bedrooms -->
            <div class="col-lg-4">
                <label for="bedrooms" class="form-label">Bedrooms</label>
                <div class="input-group">
                    <span class="input-group-text"><iconify-icon icon="solar:bed-broken"></iconify-icon></span>
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" placeholder="Number of bedrooms" value="{{ old('bedrooms') }}">
                </div>
            </div>

            <div class="col-lg-4">
                <label for="community" class="form-label">Community</label>
                <input type="text" id="community" name="community" class="form-control" placeholder="JVC, Marina, Downtown" value="{{ old('community') }}">
            </div>

            <div class="col-lg-4">
                <label for="room_no" class="form-label">No. Room</label>
                <input type="text" id="room_no" name="room_no" class="form-control" placeholder="Studio, 1 Bedroom" value="{{ old('room_no') }}">
            </div>

            <!-- Bathrooms -->
            <div class="col-lg-4">
                <label for="bathrooms" class="form-label">Bathrooms</label>
                <div class="input-group">
                    <span class="input-group-text"><iconify-icon icon="solar:bath-broken"></iconify-icon></span>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" placeholder="Number of bathrooms" value="{{ old('bathrooms') }}">
                </div>
            </div>

            <!-- Living Rooms -->
            <div class="col-lg-4">
                <label for="living_rooms" class="form-label">Living Rooms</label>
                <div class="input-group">
                    <span class="input-group-text"><iconify-icon icon="solar:sofa-2-bold-duotone"></iconify-icon></span>
                    <input type="number" id="living_rooms" name="living_rooms" class="form-control" placeholder="Number of living rooms" value="{{ old('living_rooms') }}">
                </div>
            </div>

            <!-- Kitchens -->
            <div class="col-lg-4">
                <label for="kitchens" class="form-label">Kitchens</label>
                <div class="input-group">
                    <span class="input-group-text"><iconify-icon icon="mdi:silverware-fork-knife"></iconify-icon></span>
                    <input type="number" id="kitchens" name="kitchens" class="form-control" placeholder="Number of kitchens" value="{{ old('kitchens') }}">
                </div>
            </div>

            <!-- Square Foot -->
            <div class="col-lg-4">
                <label for="square_foot" class="form-label">Square Foot</label>
                <div class="input-group">
                    <span class="input-group-text"><iconify-icon icon="solar:scale-broken"></iconify-icon></span>
                    <input type="number" id="square_foot" name="square_foot" class="form-control" placeholder="Total area" value="{{ old('square_foot') }}">
                </div>
            </div>

            <!-- Floor -->
            <div class="col-lg-4">
                <label for="floor" class="form-label">Floor</label>
                <div class="input-group">
                    <span class="input-group-text"><iconify-icon icon="solar:double-alt-arrow-up-broken"></iconify-icon></span>
                    <input type="number" id="floor" name="floor" class="form-control" placeholder="Floor number" value="{{ old('floor') }}">
                </div>
            </div>

            <div class="col-lg-4">
                <label for="unit_floor_label" class="form-label">Floor Label</label>
                <input type="text" id="unit_floor_label" name="unit_floor_label" class="form-control" placeholder="12th floor" value="{{ old('unit_floor_label') }}">
            </div>

            <div class="col-lg-4">
                <label for="parking_number" class="form-label">Parking Number</label>
                <input type="text" id="parking_number" name="parking_number" class="form-control" placeholder="Level 3: 173" value="{{ old('parking_number') }}">
            </div>

            <div class="col-lg-4">
                <label for="utilities_cap" class="form-label">Utilities Cap</label>
                <input type="number" step="0.01" min="0" id="utilities_cap" name="utilities_cap" class="form-control" placeholder="AED per month" value="{{ old('utilities_cap') }}">
            </div>

            <div class="col-lg-6">
                <label for="wifi_name" class="form-label">WiFi Name</label>
                <input type="text" id="wifi_name" name="wifi_name" class="form-control" placeholder="WiFi network" value="{{ old('wifi_name') }}">
            </div>

            <div class="col-lg-6">
                <label for="wifi_password" class="form-label">WiFi Password</label>
                <input type="text" id="wifi_password" name="wifi_password" class="form-control" placeholder="WiFi password" value="{{ old('wifi_password') }}">
            </div>

            <!-- Description -->
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter property description">{{ old('description') }}</textarea>
            </div>

        </div>
    </div>
</div>
