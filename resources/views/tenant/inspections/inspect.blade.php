@extends('layouts.tenant-pwa', ['title' => $area])

@section('content')
<div class="tenant-screen">
    @include('tenant.partials.header', ['title' => $area, 'back' => route('tenant.inspection.areas', $inspection->id)])
    <main class="tenant-content">
        <form action="{{ route('tenant.inspection.inspect.store', [$inspection->id, $area]) }}" method="POST" enctype="multipart/form-data" class="tenant-form" data-tenant-inspection-form>
            @csrf
            @foreach($items as $index => $item)
                <section class="tenant-inspect-item">
                    <h3>{{ $index + 1 }}. {{ $item->item }}</h3>
                    <p>Check condition and add photo if needed.</p>
                    <div class="tenant-condition-row">
                        @foreach(['good' => 'Good', 'issue' => 'Issue', 'na' => 'N/A'] as $value => $label)
                            <label class="{{ $value }}">
                                <input type="radio" name="items[{{ $item->id }}][condition]" value="{{ $value }}" @checked($item->condition === $value) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <textarea name="items[{{ $item->id }}][comment]" rows="2" placeholder="Comment if any issue">{{ $item->comment }}</textarea>
                    <label class="tenant-camera">
                        <i class="ri-camera-line"></i>
                        <span>Add Photo</span>
                        <input type="file" name="pictures[{{ $item->id }}][]" accept="image/*" capture="environment" multiple data-tenant-upload>
                    </label>
                    <div class="tenant-upload-preview" data-tenant-upload-preview></div>
                </section>
            @endforeach
            <div class="tenant-actions">
                <button class="tenant-secondary" type="button" onclick="history.back()">Previous</button>
                <button class="tenant-primary" type="submit">{{ $nextArea ? 'Next' : 'Review' }}</button>
            </div>
        </form>
    </main>
</div>
@endsection
