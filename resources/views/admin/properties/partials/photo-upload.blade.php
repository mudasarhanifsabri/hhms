<!-- Upload Property Photos -->
<fieldset class="border rounded-3 p-3 bg-light-subtle mb-4">
    <legend class="float-none w-auto px-2 text-primary fw-semibold">
        <i class="bi bi-images me-2"></i>Property Photos
    </legend>

    @if(isset($property) && $property->photos)
        @php
            $existingPhotos = is_array($property->photos)
                ? $property->photos
                : json_decode($property->photos, true);
        @endphp
        <div class="mb-3 d-flex flex-wrap gap-2">
            @foreach ($existingPhotos as $photo)
                <div class="border rounded p-2">
                    <img src="{{ asset('storage/' . $photo) }}" alt="Property Photo" width="120" class="img-thumbnail">
                    <div class="small text-muted">{{ basename($photo) }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <input type="file" name="photos[]" class="form-control" multiple accept="image/*">
    <small class="text-muted">Upload multiple JPG, JPEG, or PNG images.</small>
</fieldset>

<!-- Upload Video (Optional) -->
<fieldset class="border rounded-3 p-3 bg-light-subtle mb-4">
    <legend class="float-none w-auto px-2 text-success fw-semibold">
        <i class="bi bi-camera-video me-2"></i>Upload Video (Optional)
    </legend>

    @if(isset($property) && $property->video)
        <div class="mb-2">
            <video width="320" height="240" controls>
                <source src="{{ asset('storage/' . $property->video) }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <br>
            <small class="text-muted">Current video: {{ basename($property->video) }}</small>
        </div>
    @endif

    <input type="file" name="video" accept="video/*" class="form-control">
    <small class="text-muted">Accepted formats: MP4, MOV, AVI</small>
</fieldset>


<!-- Upload Floor Plan (Optional) -->
<fieldset class="border rounded-3 p-3 bg-light-subtle">
    <legend class="float-none w-auto px-2 text-dark fw-semibold">
        <i class="bi bi-file-earmark-richtext me-2"></i>Upload Floor Plan (Optional)
    </legend>

    @if(isset($property) && $property->floor_plan)
        <div class="mb-2">
            <a href="{{ asset('storage/' . $property->floor_plan) }}" target="_blank">
                View current floor plan
            </a>
            <br>
            <small class="text-muted">Current file: {{ basename($property->floor_plan) }}</small>
        </div>
    @endif

    <input type="file" name="floor_plan" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
    <small class="text-muted">Accepted formats: PDF, JPG, PNG</small>
</fieldset>
