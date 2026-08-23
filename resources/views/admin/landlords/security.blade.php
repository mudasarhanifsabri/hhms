@extends('layouts.app')

@section('title', 'Owner Security')

@section('content')
    @include('admin.landlords.partials.profile-tabs')

    <div class="row">
        <div class="col-xl-8">
            @if (session('temporary_password'))
                <div class="alert alert-warning border-warning">
                    <h5 class="alert-heading"><i class="ri-key-2-line me-1"></i>Temporary password — shown once</h5>
                    <p class="mb-2">Copy this password now and provide it securely to the owner. It cannot be viewed again after leaving this page.</p>
                    <div class="input-group">
                        <input id="temporary-owner-password" class="form-control font-monospace fw-bold" value="{{ session('temporary_password') }}" readonly>
                        <button type="button" class="btn btn-dark" onclick="navigator.clipboard.writeText(document.getElementById('temporary-owner-password').value);this.innerHTML='<i class=&quot;ri-check-line me-1&quot;></i>Copied'">
                            <i class="ri-file-copy-line me-1"></i>Copy
                        </button>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0"><i class="ri-shield-keyhole-line me-1"></i>Login Security</h4>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label text-muted">Username / Email</label>
                            <div class="input-group">
                                <input id="owner-username" class="form-control" value="{{ $landlord->email }}" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('owner-username').value)"><i class="ri-file-copy-line"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Account Status</label>
                            <div><span class="badge {{ $landlord->is_active ? 'bg-success' : 'bg-danger' }} fs-13 px-3 py-2">{{ $landlord->is_active ? 'Active' : 'Inactive' }}</span></div>
                        </div>
                    </div>

                    <div class="alert alert-info"><i class="ri-information-line me-1"></i>Current passwords are encrypted and cannot be displayed. Generate a temporary password only when the owner needs account recovery.</div>

                    <form method="POST" action="{{ route('admin.landlord.security.reset-password', $landlord->id) }}" onsubmit="return confirm('Generate a new temporary password? The owner’s current password will stop working immediately.')">
                        @csrf
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" name="email_credentials" id="email-credentials" checked>
                            <label class="form-check-label" for="email-credentials">Email the new credentials to {{ $landlord->email }}</label>
                        </div>
                        <button class="btn btn-warning" type="submit"><i class="ri-refresh-line me-1"></i>Generate New Temporary Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
