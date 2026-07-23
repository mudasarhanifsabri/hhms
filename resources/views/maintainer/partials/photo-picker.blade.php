@props(['name' => 'pictures[]', 'label' => 'Photos', 'max' => 'Max 5 photos'])

<div class="pwa-field">
    <label>{{ $label }}</label>
    <div class="pwa-photo-row">
        <label class="pwa-photo-add">
            <i class="ri-camera-line"></i>
            <span>Camera</span>
            <input type="file" name="{{ $name }}" accept="image/*" capture="environment" multiple data-upload-preview data-safe-preview>
        </label>
        <label class="pwa-photo-add is-gallery">
            <i class="ri-image-add-line"></i>
            <span>Gallery</span>
            <input type="file" name="{{ $name }}" accept="image/*" multiple data-upload-preview data-safe-preview>
        </label>
    </div>
    <div class="pwa-upload-preview" data-upload-preview-list></div>
    <small>{{ $max }}</small>
</div>
