@extends('layouts.tenant-pwa', ['title' => $area])

@section('content')
@php($draft = json_decode($inspection->draft_payload ?? '{}', true) ?: [])
<style>.tenant-inspect-item{min-width:0}.tenant-inspect-item textarea{width:100%;font-size:16px}.tenant-inspect-item .btn{min-height:44px}.tenant-inspect-item [data-photo-list]{display:flex;gap:8px;flex-wrap:wrap}</style>
<div class="tenant-screen">
    @include('tenant.partials.header', ['title' => $area, 'back' => route('tenant.inspection.areas', $inspection->id)])
    <main class="tenant-content">
        <form action="{{ route('tenant.inspection.inspect.store', [$inspection->id, $area]) }}" method="POST" enctype="multipart/form-data" class="tenant-form" id="inspection-wizard" data-tenant-draft data-draft-url="{{ route('tenant.inspection.draft', $inspection) }}" data-photo-url="{{ route('tenant.inspection.photo', $inspection) }}" data-scope="{{ auth()->id() }}:{{ $inspection->id }}" data-revision="{{ $inspection->draft_revision }}" data-step="0">
            @csrf
<input type="hidden" name="draft_revision" value="{{ $inspection->draft_revision }}">
<p id="draft-status" role="status">Draft ready</p><button type="button" id="draft-save" class="tenant-secondary">Save draft</button>
            @foreach($items as $index => $item)
                <section class="tenant-inspect-item">
                    <h3>{{ $index + 1 }}. {{ $item->item }}</h3>
                    <p>Check condition and add photo if needed.</p>
                    <div class="tenant-condition-row">
                        @foreach(['good' => 'Good', 'issue' => 'Issue', 'na' => 'N/A'] as $value => $label)
                            <label class="{{ $value }}">
                                <input type="radio" name="items[{{ $item->id }}][condition]" value="{{ $value }}" @checked(old('items.'.$item->id.'.condition', data_get($draft, 'items.'.$item->id.'.condition', $item->condition)) === $value) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <textarea name="items[{{ $item->id }}][comment]" rows="2" placeholder="Comment if any issue">{{ old('items.'.$item->id.'.comment', data_get($draft, 'items.'.$item->id.'.comment', $item->comment)) }}</textarea>
                    @include('maintainer.tasks.inspection-photos')
                </section>
            @endforeach
            <div class="tenant-actions">
                <button class="tenant-secondary" type="button" onclick="history.back()">Previous</button>
                <button id="wizard-submit" class="tenant-primary" type="submit">{{ $nextArea ? 'Next' : 'Review' }}</button>
            </div>
        </form>
    </main>
</div>
<script src="{{ asset('assets/js/inspection-draft.js') }}" defer></script>
@endsection
