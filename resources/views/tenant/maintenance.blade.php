@extends('layouts.tenant-pwa')
@section('content')
@include('tenant.partials.header', ['title' => 'Maintenance Requests'])
@include('tenant.partials.request-style')
<main class="guest-workspace">
    @if(session('success'))<div class="guest-alert" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="guest-alert guest-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <section class="guest-panel">
        <h1>How can we help?</h1>
        <p>Report a problem in your unit. Management will assign the request to the maintenance team.</p>
        <p class="guest-muted">For an immediate safety emergency, contact emergency services and management directly. This form is not an emergency response service.</p>
        @if($bookings->isEmpty())
            <div class="guest-alert">No active booking is linked to your account. Please contact management to check your booking details.</div>
        @else
        <form method="POST" action="{{ route('tenant.maintenance.store') }}" enctype="multipart/form-data" onsubmit="this.querySelector('button[type=submit]').disabled=true">
            @csrf
            <label for="booking_id">Unit / booking</label>
            <select id="booking_id" name="booking_id" required>
                @foreach($bookings as $booking)
                <option value="{{ $booking->id }}" @selected(old('booking_id') ? old('booking_id') === $booking->id : request('unit') === $booking->property_id)>{{ $booking->property->building?->building_name ?? 'No building' }} — {{ $booking->property->name }} · {{ $booking->booking_reference }}</option>
                @endforeach
            </select>
            <label for="title">Problem summary</label><input id="title" name="title" value="{{ old('title') }}" maxlength="150" placeholder="For example: AC is not cooling" required>
            <label for="priority">Priority</label><select id="priority" name="priority">@foreach(\App\Models\BookingTask::PRIORITIES as $value => $label)<option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>@endforeach</select>
            <label for="description">Details</label><textarea id="description" name="description" rows="4" maxlength="3000" placeholder="Where is the problem? When did it start?" required>{{ old('description') }}</textarea>
            <label for="pictures">Photos (optional)</label><input id="pictures" type="file" name="pictures[]" accept="image/jpeg,image/png,image/webp" multiple><p class="guest-muted">Up to 5 photos, 5 MB each. JPG, PNG or WebP.</p>
            <button type="submit" class="guest-button">Submit Maintenance Request</button>
        </form>
        @endif
    </section>
    <section class="guest-panel">
        <h2>My requests</h2><p class="guest-muted">Status comes directly from Task Manager. Internal staff notes and charges are not shown.</p>
        <div style="overflow-x:auto"><table class="guest-table"><thead><tr><th>Request</th><th>Unit</th><th>Status</th></tr></thead><tbody>
        @forelse($tasks as $task)
            <tr><td><strong>{{ $task->title }}</strong><div class="guest-muted">{{ $task->task_display_number }}<br>{{ $task->created_at->format('d M Y H:i') }}</div><details><summary>Request details</summary><p style="white-space:pre-wrap">{{ $task->description }}</p><span class="guest-muted">Priority: {{ $task->priority_label }}</span></details></td>
            <td>{{ $task->property?->name ?? 'Unavailable unit' }}<div class="guest-muted">{{ $task->property?->building?->building_name }}</div></td><td>{{ $task->status_label }}</td></tr>
        @empty<tr><td colspan="3">No requests yet.</td></tr>@endforelse
        </tbody></table></div>
        <div style="margin-top:16px">{{ $tasks->links() }}</div>
    </section>
</main>
@include('tenant.partials.bottom-nav')
@endsection
