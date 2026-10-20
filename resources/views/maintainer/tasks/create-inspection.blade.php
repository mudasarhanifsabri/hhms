@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'New Inspection', 'back' => route('maintainer.task.index', ['inspections_only' => 1])])

    <div class="pwa-content">
        @if ($errors->any())
            <div class="pwa-alert"><i class="ri-error-warning-line"></i><p>{{ $errors->first() }}</p></div>
        @endif

        <div class="pwa-alert">
            <i class="ri-shield-check-line"></i>
            <p><strong>No admin request needed.</strong><br>Choose any unit and the Task App will assign the inspection to you, generate every room checklist, and open it immediately.</p>
        </div>

        <form action="{{ route('maintainer.inspection.store') }}" method="POST" class="pwa-form">
            @csrf
            <div class="pwa-field">
                <label for="property_id">Unit <b>*</b></label>
                <select id="property_id" name="property_id" required>
                    <option value="">Choose building and unit</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected(old('property_id', request('property_id')) == $property->id)>
                            {{ $property->building?->name ?? 'No building' }} — Unit {{ $property->name }}
                        </option>
                    @endforeach
                </select>
                <small>All active units are available to inspect.</small>
            </div>

            <div class="pwa-field">
                <label>Inspection type <b>*</b></label>
                <div class="pwa-segment">
                    <label><input type="radio" name="inspection_type" value="routine" @checked(old('inspection_type', 'routine') === 'routine')><span>Routine</span></label>
                    <label><input type="radio" name="inspection_type" value="check_in" @checked(old('inspection_type') === 'check_in')><span>Check-in</span></label>
                    <label><input type="radio" name="inspection_type" value="check_out" @checked(old('inspection_type') === 'check_out')><span>Check-out</span></label>
                </div>
            </div>

            <div class="pwa-field">
                <label for="priority">Priority <b>*</b></label>
                <select id="priority" name="priority" required>
                    @foreach(\App\Models\BookingTask::PRIORITIES as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pwa-field">
                <label for="due_date">Inspection date <b>*</b></label>
                <input id="due_date" type="date" name="due_date" value="{{ old('due_date', now('Asia/Dubai')->toDateString()) }}" required>
            </div>

            <div class="pwa-field">
                <label for="notes">Reason / notes</label>
                <textarea id="notes" name="notes" rows="4" placeholder="Why are you inspecting this unit?">{{ old('notes') }}</textarea>
            </div>

            <button class="pwa-primary-button purple" type="submit"><i class="ri-play-circle-line me-2"></i>Create &amp; Start Inspection</button>
        </form>
    </div>
    @include('maintainer.partials.mobile-nav')
</div>
@endsection
