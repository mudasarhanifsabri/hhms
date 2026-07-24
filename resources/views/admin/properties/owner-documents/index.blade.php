@extends('layouts.app')

@section('content')
@php
    $owner = $property->landlord;
@endphp

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header bg-light-subtle">
                <h4 class="card-title mb-0">Generate Owner Documents</h4>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <p class="mb-1"><strong>Unit:</strong> {{ $property->name }}</p>
                <p class="mb-1"><strong>Owner:</strong> {{ $owner->name ?? 'N/A' }}</p>
                <p class="mb-3"><strong>Expiry:</strong> One year from sending date</p>

                <form action="{{ route('admin.property.owner-documents.store', $property->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="furniture_amount" class="form-label">Furniture Amount</label>
                        <input type="number" step="0.01" min="0" id="furniture_amount" name="furniture_amount" class="form-control" value="{{ old('furniture_amount', 0) }}">
                        <small class="text-muted">VAT 5% is calculated from furniture only.</small>
                    </div>
                    <div class="mb-3">
                        <label for="startup_dtcm_fee" class="form-label">Startup / DTCM Fee</label>
                        <input type="number" step="0.01" min="0" id="startup_dtcm_fee" name="startup_dtcm_fee" class="form-control" value="{{ old('startup_dtcm_fee', 0) }}">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-send-plane-line me-1"></i>Generate & Send For Signature
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Owner Signature Tracking</h4>
                <a href="{{ route('admin.property.show', $property->id) }}" class="btn btn-light btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Unit Details
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th>Document</th>
                                <th>Ref No</th>
                                <th>Status</th>
                                <th>Expiry</th>
                                <th>Signed File</th>
                                <th>Owner Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $document)
                                <tr>
                                    <td>{{ $document->title }}</td>
                                    <td>{{ $document->reference_no }}</td>
                                    <td>
                                        <span class="badge {{ $document->status === 'signed' ? 'bg-success' : ($document->status === 'viewed' ? 'bg-info' : 'bg-warning') }}">
                                            {{ $document->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $document->expires_at?->format('d M Y') }}</td>
                                    <td>
                                        @if($document->signed_document_path)
                                            <a href="{{ route('owner-documents.pdf', $document->signing_token) }}" target="_blank" class="btn btn-sm btn-dark">Open PDF</a>
                                        @else
                                            <a href="{{ route('owner-documents.pdf', $document->signing_token) }}" target="_blank" class="btn btn-sm btn-light">Preview</a>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('owner-documents.show', $document->signing_token) }}" target="_blank" class="btn btn-sm btn-soft-primary">
                                            Sign Link
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No owner documents generated yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
