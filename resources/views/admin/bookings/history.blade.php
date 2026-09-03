@extends('layouts.app')

@section('content')
@include('admin.bookings.partials.corrections')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">History - {{ $booking->booking_reference }}</h4>
        <a href="{{ route('admin.booking.show', $booking->id) }}" class="btn btn-sm btn-light">Back</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap table-hover table-centered mb-0">
                <thead class="bg-light-subtle">
                    <tr><th>Date</th><th>Action</th><th>Description</th></tr>
                </thead>
                <tbody>
                    @forelse($booking->histories as $history)
                        <tr>
                            <td>{{ $history->created_at->format('d M Y h:i A') }}</td>
                            <td>{{ $history->title }}</td>
                            <td class="text-wrap text-break" style="min-width:300px">{{ $history->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No history found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
