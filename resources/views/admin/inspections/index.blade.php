@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header border-bottom">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h4 class="card-title mb-0">Inspection Management</h4>
            <span class="badge bg-primary-subtle text-primary">Tracking Only</span>
        </div>
        <form action="{{ route('admin.inspection.index') }}" method="GET" class="row g-2">
            <div class="col-lg-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search inspection, booking, guest"></div>
            <div class="col-lg-2">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="check_in" @selected(request('type') === 'check_in')>Check In</option>
                    <option value="check_out" @selected(request('type') === 'check_out')>Check Out</option>
                </select>
            </div>
            <div class="col-lg-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-fill">Filter</button>
                <a href="{{ route('admin.inspection.index') }}" class="btn btn-outline-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-centered table-hover align-middle text-nowrap mb-0">
            <thead class="bg-light-subtle">
                <tr>
                    <th>Inspection</th>
                    <th>Booking</th>
                    <th>Guest</th>
                    <th>Property</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Issues</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inspections as $inspection)
                    <tr>
                        <td class="fw-semibold">{{ $inspection->inspection_number }}</td>
                        <td>{{ $inspection->booking?->booking_reference ?? '-' }}</td>
                        <td>{{ $inspection->booking?->guest_name ?? '-' }}</td>
                        <td>{{ $inspection->booking?->property?->building?->name ?? '-' }} - {{ $inspection->booking?->property?->name ?? '-' }}</td>
                        <td>{{ $inspection->type_label }}</td>
                        <td><span class="badge {{ $inspection->status_class }} text-white">{{ $inspection->status_label }}</span></td>
                        <td>{{ $inspection->issue_items }} / {{ $inspection->total_items }}</td>
                        <td>{{ $inspection->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admin.inspection.show', $inspection->id) }}" class="btn btn-light btn-sm"><iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No inspections found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $inspections->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
