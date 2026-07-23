<!-- Amenities, Security & Features -->
<div class="col-12">
    <div class="right-sidebar">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3 fw-semibold">Amenities, Security & Features</h5>

                {{-- Amenities --}}
                <fieldset class="mb-4 border rounded-3 p-3 bg-light-subtle">
                    <legend class="float-none w-auto px-2 text-primary fw-semibold">
                        <i class="bi bi-building-check me-2"></i>Amenities
                    </legend>
                    @php
                        $amenities = ['Parking Space', 'Lift/Elevator', 'Swimming Pool', 'Gym', 'Garden', "Children's Play Area"];
                        $oldAmenities = old('amenities', []);
                    @endphp
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2">
                        @foreach ($amenities as $amenity)
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="{{ $amenity }}" id="amenity-{{ $loop->index }}"
                                        {{ in_array($amenity, $oldAmenities) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="amenity-{{ $loop->index }}">{{ $amenity }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </fieldset>

               {{-- Security & Utilities --}}
<fieldset class="mb-4 border rounded-3 p-3 bg-light-subtle">
    <legend class="float-none w-auto px-2 text-danger fw-semibold">
        <i class="bi bi-shield-lock me-2"></i>Security & Utilities
    </legend>

    {{-- Has Security Radio --}}
    <div class="mb-3">
        <label class="form-label d-block">Security</label>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="has_security" value="1" id="security-yes"
                {{ old('has_security') == '1' ? 'checked' : '' }}>
            <label class="form-check-label" for="security-yes">Yes</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="has_security" value="0" id="security-no"
                {{ old('has_security') == '0' ? 'checked' : '' }}>
            <label class="form-check-label" for="security-no">No</label>
        </div>
    </div>

    {{-- Security Utilities Checkboxes --}}
    @php
        $utilities = [
            'CCTV Surveillance',
            'Fire Safety',
            'Power Backup',
            'Water Supply (24/7)',
            'Water Supply (Timed)',
            'Internet Connection'
        ];
        $oldUtilities = old('security_utilities', []);
    @endphp

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2">
        @foreach ($utilities as $utility)
            <div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="security_utilities[]" value="{{ $utility }}"
                        id="utility-{{ $loop->index }}"
                        {{ in_array($utility, $oldUtilities) ? 'checked' : '' }}>
                    <label class="form-check-label" for="utility-{{ $loop->index }}">{{ $utility }}</label>
                </div>
            </div>
        @endforeach
    </div>
</fieldset>
                {{-- Additional Features --}}
                <fieldset class="border rounded-3 p-3 bg-light-subtle">
                    <legend class="float-none w-auto px-2 text-success fw-semibold">
                        <i class="bi bi-geo-alt me-2"></i>Additional Features
                    </legend>
                    @php
                        $features = ['Nearby Schools', 'Nearby Hospitals', 'Nearby Bus/Metro Stops'];
                        $oldFeatures = old('additional_features', []);
                    @endphp
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2 mb-3">
                        @foreach ($features as $feature)
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="additional_features[]" value="{{ $feature }}" id="feature-{{ $loop->index }}"
                                        {{ in_array($feature, $oldFeatures) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="feature-{{ $loop->index }}">{{ $feature }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Distance to Main Road</label>
                            <input type="text" name="distance_to_road" class="form-control" placeholder="e.g., 200m"
                                value="{{ old('distance_to_road') }}">
                        </div>
                        <div class="col-md-12 mb-0">
                            <label class="form-label">Additional Notes</label>
                            <textarea name="additional_notes" rows="3" class="form-control" placeholder="Any other information...">{{ old('additional_notes') }}</textarea>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
</div>
