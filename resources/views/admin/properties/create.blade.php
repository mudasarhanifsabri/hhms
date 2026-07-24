@extends('layouts.app')

@section('content')

<style>
    .sticky-save-bar {
        position: sticky;
        bottom: 0;
        z-index: 999;
        background-color: #fff;
        padding: 1rem;
        border-top: 1px solid #dee2e6;
        box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
    }
</style>

<form id="propertyForm" action="{{ route('admin.property.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <!-- Unit Info -->
    @include('admin.properties.partials.property-info')

    <!-- Amenities & Features -->
    @include('admin.properties.partials.amenities-security-features')

    <!-- Photos Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h4 class="card-title">Upload Unit Photos</h4>
        </div>
        <div class="card-body">
            <div class="dropzone bg-light-subtle py-5" id="propertyPhotoDropzone">
                <div class="fallback">
                    <input name="photos[]" type="file" multiple />
                </div>
                <div class="dz-message needsclick">
                    <i class="ri-upload-cloud-2-line fs-48 text-primary"></i>
                    <h3 class="mt-4">Drop photos here or <span class="text-primary">click to browse</span></h3>
                    <span class="text-muted fs-13">1600x1200 (4:3) recommended. PNG, JPG, GIF allowed.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Upload -->
    <div class="card mt-4">
        <div class="card-header">
            <h4 class="card-title">Upload Unit Video <small class="text-muted">(Optional)</small></h4>
        </div>
        <div class="card-body">
            <input type="file" name="video" accept="video/*" class="form-control" />
            <small class="form-text text-muted">Supported formats: MP4, WebM, AVI. Max size: 100MB</small>
        </div>
    </div>

    <!-- Floor Plan -->
    <div class="card mt-4">
        <div class="card-header">
            <h4 class="card-title">Upload Floor Plan <small class="text-muted">(Optional)</small></h4>
        </div>
        <div class="card-body">
            <input type="file" name="floor_plan" accept=".pdf,.jpg,.png" class="form-control" />
            <small class="form-text text-muted">Accepted: PDF, JPG, PNG</small>
        </div>
    </div>

    <!-- Documents & Utilities -->
    @include('admin.properties.partials.documents-utilities')
    @include('admin.properties.partials.utility-account-setup')

    <!-- Sticky Save Bar -->
    <div class="sticky-save-bar d-flex justify-content-between align-items-center mt-4">
        <div>
            <button type="button" class="btn btn-outline-secondary me-2" id="saveDraftBtn">Save Draft</button>
            <button type="submit" class="btn btn-primary">Create Unit</button>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.getElementById('saveDraftBtn').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const formData = new FormData(document.getElementById('propertyForm'));
    fetch('{{ route("admin.property.draft") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert('Draft saved successfully!');
    })
    .catch(() => {
        alert('Failed to save draft.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Save Draft';
    });
});

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
