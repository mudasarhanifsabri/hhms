@extends('layouts.tenant-pwa', ['title' => 'Inspection Submitted'])

@section('content')
<div class="tenant-screen">
    @include('tenant.partials.header', ['title' => 'Inspection Submitted', 'back' => route('tenant.booking.show', $inspection->booking_id)])
    <main class="tenant-content tenant-success">
        <div class="tenant-success-check"><i class="ri-check-line"></i></div>
        <h2>Inspection Submitted!</h2>
        <p>Your {{ strtolower($inspection->type_label) }} inspection has been submitted successfully.</p>
        <div class="tenant-inspection-id">
            <span>Inspection ID</span>
            <strong>{{ $inspection->inspection_number }}</strong>
        </div>
        <a href="{{ route('tenant.booking.show', $inspection->booking_id) }}" class="tenant-primary">Back to Booking</a>
    </main>
</div>
@endsection
