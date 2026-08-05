@extends('layouts.app')

@section('content')
@php
    $value = fn (string $key, $default = null) => old($key, $settings[$key] ?? $default);
    $mediaUrl = fn (?string $path) => $path ? \App\Support\MediaStorage::url($path) : null;
    $logoUrl = $mediaUrl($settings['logo_path'] ?? null);
    $faviconUrl = $mediaUrl($settings['favicon_path'] ?? null);
    $whatsappCallbackUrl = rtrim(config('app.url'), '/') . '/webhooks/whatsapp';
@endphp

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1">Application Settings</h4>
                <p class="text-muted mb-0">Manage system branding, storage, email, WhatsApp, and SMS from separate setting tabs.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                Media: {{ strtoupper($value('media_disk', 'public')) }}
            </span>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please check the settings form.</strong>
        <div class="small mt-1">{{ $errors->first() }}</div>
    </div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-xxl-3 col-xl-4">
            <div class="card">
                <div class="card-header bg-light-subtle">
                    <h4 class="card-title mb-0">Settings Menu</h4>
                </div>
                <div class="card-body">
                    <div class="nav flex-column nav-pills gap-2" id="settings-tabs" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active text-start" id="branding-tab" data-bs-toggle="pill" data-bs-target="#branding-panel" type="button" role="tab">
                            <i class="ri-brush-line me-1"></i>Branding
                        </button>
                        <button class="nav-link text-start" id="media-tab" data-bs-toggle="pill" data-bs-target="#media-panel" type="button" role="tab">
                            <i class="ri-cloud-line me-1"></i>Media & S3
                        </button>
                        <button class="nav-link text-start" id="email-tab" data-bs-toggle="pill" data-bs-target="#email-panel" type="button" role="tab">
                            <i class="ri-mail-settings-line me-1"></i>Email SMTP
                        </button>
                        <button class="nav-link text-start" id="whatsapp-tab" data-bs-toggle="pill" data-bs-target="#whatsapp-panel" type="button" role="tab">
                            <i class="ri-whatsapp-line me-1"></i>WhatsApp
                        </button>
                        <button class="nav-link text-start" id="sms-tab" data-bs-toggle="pill" data-bs-target="#sms-panel" type="button" role="tab">
                            <i class="ri-message-2-line me-1"></i>SMS
                        </button>
                    </div>

                    <div class="border rounded p-3 mt-3 bg-light-subtle">
                        <p class="fw-semibold mb-1">Save once</p>
                        <p class="text-muted small mb-0">Change any tab, then click Save Settings. Secret fields can stay blank to keep the saved value.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6 col-xl-8">
            <div class="tab-content" id="settings-tabs-content">
                <div class="tab-pane fade show active" id="branding-panel" role="tabpanel" aria-labelledby="branding-tab">
                    <div class="card">
                        <div class="card-header bg-light-subtle">
                            <h4 class="card-title mb-0">Branding</h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="company_name">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="{{ $value('company_name', config('app.name')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="company_phone">Company Phone</label>
                                    <input type="text" class="form-control" id="company_phone" name="company_phone" value="{{ $value('company_phone') }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="company_email">Company Email</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email" value="{{ $value('company_email') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="logo">Logo</label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                    @if($logoUrl)
                                        <div class="border rounded p-2 mt-2 bg-light-subtle">
                                            <img src="{{ $logoUrl }}" alt="Current logo" class="img-fluid" style="max-height: 54px;">
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="favicon">Favicon</label>
                                    <input type="file" class="form-control" id="favicon" name="favicon" accept=".ico,image/png">
                                    @if($faviconUrl)
                                        <div class="border rounded p-2 mt-2 bg-light-subtle">
                                            <img src="{{ $faviconUrl }}" alt="Current favicon" width="34" height="34">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="media-panel" role="tabpanel" aria-labelledby="media-tab">
                    <div class="card">
                        <div class="card-header bg-light-subtle">
                            <h4 class="card-title mb-0">Media Storage & AWS S3</h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="media_disk">Media Storage</label>
                                    <select class="form-select" id="media_disk" name="media_disk">
                                        <option value="public" @selected($value('media_disk', 'public') === 'public')>Local Public Storage</option>
                                        <option value="s3" @selected($value('media_disk') === 's3')>AWS S3 Bucket</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="aws_default_region">AWS Region</label>
                                    <input type="text" class="form-control" id="aws_default_region" name="aws_default_region" value="{{ $value('aws_default_region') }}" placeholder="me-central-1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="aws_bucket">Bucket Name</label>
                                    <input type="text" class="form-control" id="aws_bucket" name="aws_bucket" value="{{ $value('aws_bucket') }}" placeholder="hhms-media">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="aws_access_key_id">Access Key ID</label>
                                    <input type="text" class="form-control" id="aws_access_key_id" name="aws_access_key_id" value="{{ $value('aws_access_key_id') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="aws_secret_access_key">Secret Access Key</label>
                                    <input type="password" class="form-control" id="aws_secret_access_key" name="aws_secret_access_key" placeholder="{{ filled($settings['aws_secret_access_key'] ?? null) ? 'Saved - leave blank to keep current' : '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="aws_url">Public URL</label>
                                    <input type="url" class="form-control" id="aws_url" name="aws_url" value="{{ $value('aws_url') }}" placeholder="https://cdn.example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="aws_endpoint">Endpoint</label>
                                    <input type="url" class="form-control" id="aws_endpoint" name="aws_endpoint" value="{{ $value('aws_endpoint') }}" placeholder="Only for custom S3 providers">
                                </div>
                            </div>
                            <div class="alert alert-light border mt-3 mb-0">
                                Select S3 only after the bucket, region, and keys are correct.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="email-panel" role="tabpanel" aria-labelledby="email-tab">
                    <div class="card">
                        <div class="card-header bg-light-subtle">
                            <h4 class="card-title mb-0">Email SMTP</h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="mail_mailer">Mailer</label>
                                    <select class="form-select" id="mail_mailer" name="mail_mailer">
                                        <option value="log" @selected($value('mail_mailer', config('mail.default')) === 'log')>Log</option>
                                        <option value="smtp" @selected($value('mail_mailer') === 'smtp')>SMTP</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="mail_host">SMTP Host</label>
                                    <input type="text" class="form-control" id="mail_host" name="mail_host" value="{{ $value('mail_host') }}" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="mail_port">Port</label>
                                    <input type="number" class="form-control" id="mail_port" name="mail_port" value="{{ $value('mail_port') }}" placeholder="587">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="mail_encryption">Security</label>
                                    <select class="form-select" id="mail_encryption" name="mail_encryption">
                                        <option value="">None</option>
                                        <option value="tls" @selected($value('mail_encryption') === 'tls')>TLS</option>
                                        <option value="ssl" @selected($value('mail_encryption') === 'ssl')>SSL</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_username">Username</label>
                                    <input type="text" class="form-control" id="mail_username" name="mail_username" value="{{ $value('mail_username') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_password">Password</label>
                                    <input type="password" class="form-control" id="mail_password" name="mail_password" placeholder="{{ filled($settings['mail_password'] ?? null) ? 'Saved - leave blank to keep current' : '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_from_address">From Email</label>
                                    <input type="email" class="form-control" id="mail_from_address" name="mail_from_address" value="{{ $value('mail_from_address') }}" placeholder="info@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_from_name">From Name</label>
                                    <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" value="{{ $value('mail_from_name') }}" placeholder="{{ config('app.name') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="whatsapp-panel" role="tabpanel" aria-labelledby="whatsapp-tab">
                    <div class="card">
                        <div class="card-header bg-light-subtle">
                            <h4 class="card-title mb-0">WhatsApp API</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info border d-flex gap-2 align-items-start">
                                <i class="ri-information-line fs-20"></i>
                                <div>
                                    <div class="fw-semibold">Meta WhatsApp Webhook Callback URL</div>
                                    <input type="text" class="form-control form-control-sm font-monospace mt-2" value="{{ $whatsappCallbackUrl }}" readonly onclick="this.select()">
                                    <div class="small mt-2">Use the same URL for webhook verification and incoming WhatsApp callbacks. The verify token below must match Meta.</div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="whatsapp_provider">Provider</label>
                                    <input type="text" class="form-control" id="whatsapp_provider" name="whatsapp_provider" value="{{ $value('whatsapp_provider') }}" placeholder="Meta Cloud API">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="whatsapp_phone_number_id">Phone Number ID</label>
                                    <input type="text" class="form-control" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" value="{{ $value('whatsapp_phone_number_id') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="whatsapp_token">Access Token</label>
                                    <input type="password" class="form-control" id="whatsapp_token" name="whatsapp_token" placeholder="{{ filled($settings['whatsapp_token'] ?? null) ? 'Saved - leave blank to keep current' : '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="whatsapp_verify_token">Webhook Verify Token</label>
                                    <input type="text" class="form-control" id="whatsapp_verify_token" name="whatsapp_verify_token" value="{{ $value('whatsapp_verify_token') }}" placeholder="Paste this in Meta">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="sms-panel" role="tabpanel" aria-labelledby="sms-tab">
                    <div class="card">
                        <div class="card-header bg-light-subtle">
                            <h4 class="card-title mb-0">SMS API</h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="sms_provider">Provider</label>
                                    <input type="text" class="form-control" id="sms_provider" name="sms_provider" value="{{ $value('sms_provider') }}" placeholder="Twilio / local gateway">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="sms_sender_id">Sender ID</label>
                                    <input type="text" class="form-control" id="sms_sender_id" name="sms_sender_id" value="{{ $value('sms_sender_id') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="sms_api_key">API Key</label>
                                    <input type="text" class="form-control" id="sms_api_key" name="sms_api_key" value="{{ $value('sms_api_key') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="sms_api_secret">API Secret</label>
                                    <input type="password" class="form-control" id="sms_api_secret" name="sms_api_secret" placeholder="{{ filled($settings['sms_api_secret'] ?? null) ? 'Saved - leave blank to keep current' : '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-12">
            <div class="card position-sticky" style="top: 86px;">
                <div class="card-header bg-light-subtle">
                    <h4 class="card-title mb-0">Save Changes</h4>
                </div>
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-save-3-line me-1"></i>Save Settings
                    </button>
                    <a href="{{ route('admin.software-update.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="ri-download-cloud-2-line me-1"></i>Update Software
                    </a>
                    <p class="text-muted small mt-3 mb-0">
                        Passwords and API secrets are encrypted in the database. Leave secret fields blank to keep existing saved values.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
