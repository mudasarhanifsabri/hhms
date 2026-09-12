<div data-inspection-photos data-item-id="{{ $item->id }}" data-saved="{{ json_encode(collect($item->pictures ?? [])->map(fn ($path) => ['path' => $path, 'url' => \App\Support\MediaStorage::url($path)])->values()) }}" class="pwa-field">
    <label>Photos <small class="text-muted">Optional · up to 5</small></label>
    <div class="d-flex gap-2 flex-wrap">
        <label class="btn btn-outline-primary btn-sm"><i class="ri-camera-line"></i> Camera<input data-photo-pick type="file" accept="image/jpeg,image/png,image/webp" capture="environment" class="visually-hidden"></label>
        <label class="btn btn-outline-primary btn-sm"><i class="ri-image-add-line"></i> Gallery<input data-photo-pick type="file" accept="image/jpeg,image/png,image/webp" multiple class="visually-hidden"></label>
    </div>
    <input data-photo-files type="file" name="pictures[{{ $item->id }}][]" accept="image/jpeg,image/png,image/webp" multiple hidden>
    <div data-photo-list class="d-flex flex-wrap gap-2 mt-2"></div>
    <small data-photo-message role="status" aria-live="polite">No photos selected</small>
    <noscript>Use the file picker below:<input type="file" name="pictures[{{ $item->id }}][]" accept="image/*" multiple></noscript>
</div>
