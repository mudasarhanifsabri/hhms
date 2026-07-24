@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Vendor Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vendorModal"><i class="ri-add-line me-1"></i>Add Vendor</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Vendor No.</th><th>Name</th><th>Category</th><th>Contact</th><th>TRN</th><th>Opening Balance</th><th>Expenses</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($vendors as $vendor)
                <tr>
                    <td class="fw-semibold">{{ $vendor->vendor_no }}</td>
                    <td>{{ $vendor->name }}</td>
                    <td>{{ $vendor->category ?? '-' }}</td>
                    <td>{{ $vendor->contact_person ?? '-' }}<p class="text-muted mb-0">{{ $vendor->phone }} {{ $vendor->email }}</p></td>
                    <td>{{ $vendor->trn ?? '-' }}</td>
                    <td>AED {{ number_format((float) $vendor->opening_balance, 2) }}</td>
                    <td>{{ $vendor->expenses_count }}</td>
                    <td><span class="badge {{ $vendor->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No vendors yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="vendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form class="modal-content" method="post" action="{{ route('admin.accounting.vendors.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Add Vendor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-6"><label class="form-label">Vendor Name</label><input name="name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Category</label><input name="category" class="form-control" placeholder="Maintenance, Cleaning, Utility"></div>
            <div class="col-md-4"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">TRN</label><input name="trn" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="0"></div>
            <div class="col-md-4"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
            <div class="col-12"><label class="form-label">Address</label><textarea name="address" rows="2" class="form-control"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save Vendor</button></div>
    </form></div>
</div>
@endsection
