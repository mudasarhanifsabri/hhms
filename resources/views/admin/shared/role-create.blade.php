@php
    $nationalities = [
        'United Arab Emirates', 'India', 'Pakistan', 'Bangladesh', 'Sri Lanka', 'Nepal', 'Philippines',
        'United Kingdom', 'United States', 'Canada', 'Egypt', 'Jordan', 'Lebanon', 'Saudi Arabia',
        'Oman', 'Kuwait', 'Qatar', 'Bahrain', 'Other',
    ];
    $roleTitle = $roleTitle ?? 'User';
    $backUrl = $backUrl ?? url()->previous();
@endphp

@push('styles')
<style>
    .ocr-create-shell { --ocr-primary: #5b3df5; --ocr-ink: #111827; --ocr-muted: #667085; --ocr-line: #e5e7eb; color: var(--ocr-ink); max-height: calc(100vh - 92px); overflow: hidden; }
    .ocr-page-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 12px; }
    .ocr-back { display: inline-flex; align-items: center; gap: 8px; color: #344054; font-weight: 600; margin-bottom: 10px; }
    .ocr-title h4 { font-size: 24px; margin: 0 0 2px; font-weight: 800; }
    .ocr-title p { color: var(--ocr-muted); margin: 0; }
    .ocr-steps { display: grid; grid-template-columns: 1fr 1fr 1fr; align-items: center; gap: 14px; margin-bottom: 14px; }
    .ocr-step { display: flex; align-items: center; gap: 12px; color: #344054; font-weight: 700; }
    .ocr-step:after { content: ""; height: 1px; flex: 1; background: var(--ocr-line); }
    .ocr-step:last-child:after { display: none; }
    .ocr-step span { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; border: 1px solid var(--ocr-line); background: #fff; }
    .ocr-step.active span { background: var(--ocr-primary); color: #fff; box-shadow: 0 10px 20px rgba(91, 61, 245, .22); }
    .ocr-panel { border: 1px solid var(--ocr-line); border-radius: 10px; background: #fff; box-shadow: 0 16px 40px rgba(16, 24, 40, .04); height: 100%; }
    .ocr-panel-body { padding: 18px; }
    .ocr-doc-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
    .ocr-doc-tab { border: 1px solid var(--ocr-line); border-radius: 8px; padding: 10px 14px; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 800; background: #fff; }
    .ocr-doc-tab.active { color: var(--ocr-primary); border-color: var(--ocr-primary); background: #f5f3ff; }
    .ocr-upload { border: 1px dashed #cfd5e1; border-radius: 9px; padding: 12px; display: grid; grid-template-columns: 38px 1fr 118px; gap: 12px; align-items: center; margin-bottom: 10px; cursor: pointer; min-height: 88px; }
    .ocr-upload input { display: none; }
    .ocr-upload-icon { width: 36px; height: 36px; border-radius: 12px; display: grid; place-items: center; color: var(--ocr-primary); background: #f4f2ff; font-size: 22px; }
    .ocr-upload strong { display: block; font-size: 15px; }
    .ocr-upload small, .ocr-meta { color: var(--ocr-muted); display: block; }
    .ocr-upload .ocr-ok { color: #10b981; font-weight: 700; margin-top: 4px; display: none; }
    .ocr-upload.has-file .ocr-ok { display: block; }
    .ocr-preview { width: 118px; height: 58px; border-radius: 7px; object-fit: cover; background: #f8fafc; border: 1px solid var(--ocr-line); display: grid; place-items: center; color: var(--ocr-muted); overflow: hidden; font-size: 20px; }
    .ocr-preview img { width: 100%; height: 100%; object-fit: cover; }
    .ocr-camera { width: 100%; border: 1px solid #d0d5dd; border-radius: 8px; background: #fff; padding: 10px 14px; color: var(--ocr-primary); font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 4px; cursor: pointer; }
    .ocr-note { background: linear-gradient(135deg, #f8f5ff, #f1fbff); border-radius: 9px; padding: 12px; color: #344054; margin-top: 10px; }
    .ocr-confidence { border-radius: 999px; background: #dff8ea; color: #027a48; padding: 8px 12px; font-weight: 800; font-size: 12px; }
    .ocr-field label { font-weight: 700; font-size: 12px; margin-bottom: 6px; color: #344054; }
    .ocr-field .form-control, .ocr-field .form-select { min-height: 42px; border-radius: 8px; border-color: #d9dee8; }
    .ocr-field .input-group-text { border-radius: 8px 0 0 8px; background: #fff; }
    .ocr-actions { position: sticky; bottom: 0; background: rgba(255,255,255,.96); border-top: 1px solid var(--ocr-line); padding: 12px 0 0; margin-top: 12px; display: flex; justify-content: space-between; gap: 14px; }
    .ocr-primary { background: linear-gradient(135deg, #6d4dfc, #3d2cf0); color: #fff; border: 0; border-radius: 8px; padding: 11px 28px; font-weight: 800; }
    .ocr-secondary { border: 1px solid #cfd5e1; background: #fff; border-radius: 8px; padding: 11px 22px; font-weight: 800; color: #344054; }
    .ocr-switch { border: 1px solid var(--ocr-line); border-radius: 9px; padding: 13px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #fbfcff; }
    @media (max-width: 991px) { .ocr-steps { grid-template-columns: 1fr; } .ocr-step:after { display: none; } .ocr-upload { grid-template-columns: 44px 1fr; } .ocr-preview { grid-column: 1 / -1; width: 100%; height: 160px; } }
</style>
@endpush

<div class="ocr-create-shell">
    <a href="{{ $backUrl }}" class="ocr-back"><i class="ri-arrow-left-line"></i> Back to {{ $backLabel ?? $roleTitle . 's' }}</a>

    <div class="ocr-page-head">
        <div class="ocr-title">
            <h4>Add New {{ $roleTitle }}</h4>
            <p>Capture Emirates ID or Passport with OCR-ready manual review</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><strong>Please check the form.</strong> {{ $errors->first() }}</div>
    @endif

    <form action="{{ $storeRoute }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="ocr-steps">
            <div class="ocr-step active"><span>1</span> Document Scan</div>
            <div class="ocr-step" data-wizard-step-label="1"><span>2</span> OCR Preview</div>
            <div class="ocr-step" data-wizard-step-label="2"><span>3</span> Bank & Save</div>
        </div>

        <div class="row g-4" data-wizard-panel="1">
            <div class="col-xl-4">
                <div class="ocr-panel">
                    <div class="ocr-panel-body">
                        <h5 class="fw-bold mb-2">Document Scan</h5>
                        <label class="form-label fw-semibold">Select Document Type</label>
                        <div class="ocr-doc-tabs">
                            <button type="button" class="ocr-doc-tab active" data-doc-type="emirates_id"><i class="ri-id-card-line"></i> Emirates ID</button>
                            <button type="button" class="ocr-doc-tab" data-doc-type="passport"><i class="ri-passport-line"></i> Passport</button>
                        </div>

                        <label class="form-label fw-semibold">Profile Photo</label>
                        <label class="ocr-upload" data-ocr-upload>
                            <input type="file" name="profile_photo" accept="image/*" data-ocr-preview>
                            <span class="ocr-upload-icon"><i class="ri-user-smile-line"></i></span>
                            <span><strong>Photo</strong><small>JPG or PNG</small><span class="ocr-ok"><i class="ri-check-line"></i> Uploaded</span></span>
                            <span class="ocr-preview"><i class="ri-user-smile-line"></i></span>
                        </label>

                        <label class="form-label fw-semibold mt-1" data-document-heading>Upload Emirates ID</label>
                        <label class="ocr-upload" data-ocr-upload>
                            <input type="file" name="id_document" accept="image/*,.pdf" data-ocr-preview>
                            <span class="ocr-upload-icon"><i class="ri-id-card-line" data-front-icon></i></span>
                            <span><strong data-front-title>Front Side</strong><small data-front-help>JPG, PNG or PDF<br>Max 10MB</small><span class="ocr-ok"><i class="ri-check-line"></i> Uploaded</span></span>
                            <span class="ocr-preview"><i class="ri-id-card-line" data-front-preview-icon></i></span>
                        </label>
                        <label class="ocr-upload" data-ocr-upload data-back-upload>
                            <input type="file" name="id_document_back" accept="image/*,.pdf" data-ocr-preview>
                            <span class="ocr-upload-icon"><i class="ri-bank-card-2-line" data-back-icon></i></span>
                            <span><strong data-back-title>Back Side</strong><small data-back-help>JPG, PNG or PDF<br>Max 10MB</small><span class="ocr-ok"><i class="ri-check-line"></i> Uploaded</span></span>
                            <span class="ocr-preview"><i class="ri-bank-card-2-line" data-back-preview-icon></i></span>
                        </label>
                        <label class="ocr-camera">
                            <i class="ri-camera-line"></i> Scan with Camera
                            <input type="file" name="camera_capture" accept="image/*" capture="environment" hidden data-ocr-preview>
                        </label>

                        <div class="ocr-note">
                            <div class="fw-bold mb-1"><i class="ri-sparkling-2-line text-primary"></i> AI OCR Technology</div>
                            <div class="small">Upload preview is ready now. If OCR result is unclear, enter or correct details manually before save.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="ocr-panel">
                    <div class="ocr-panel-body">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
                            <h5 class="fw-bold mb-0">Extracted Information (OCR Results)</h5>
                            <span class="ocr-confidence">Manual Review <i class="ri-check-line"></i></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 ocr-field">
                                <label>Full Name (English)</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label data-id-label>Emirates ID Number</label>
                                <input type="text" name="eid_passport_no" class="form-control" value="{{ old('eid_passport_no') }}" required>
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Full Name (Arabic)</label>
                                <input type="text" name="name_ar" class="form-control text-end" dir="rtl" value="{{ old('name_ar') }}">
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Issue Date</label>
                                <input type="date" name="id_issue_date" class="form-control" value="{{ old('id_issue_date') }}">
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Expiry Date</label>
                                <input type="date" name="id_expiry_date" class="form-control" value="{{ old('id_expiry_date') }}">
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="{{ old('dob') }}" required>
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                                    <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                                    <option value="Other" @selected(old('gender') === 'Other')>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">AE +971</span>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Nationality</label>
                                <select name="nationality" class="form-select">
                                    <option value="">Select Nationality</option>
                                    @foreach($nationalities as $nationality)
                                        <option value="{{ $nationality }}" @selected(old('nationality') === $nationality)>{{ $nationality }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 d-none" data-wizard-panel="2">
            <div class="col-xl-8 mx-auto">
                <div class="ocr-panel">
                    <div class="ocr-panel-body">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
                            <div>
                                <h5 class="fw-bold mb-1">Bank Details</h5>
                                <div class="ocr-meta">Optional account details can be added now or updated later from the profile.</div>
                            </div>
                            <span class="ocr-confidence">Final Step <i class="ri-bank-card-line"></i></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 ocr-field">
                                <label>Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}">
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>IBAN</label>
                                <input type="text" name="iban" class="form-control" value="{{ old('iban') }}">
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Bank Account Holder</label>
                                <input type="text" name="bank_account_holder" class="form-control" value="{{ old('bank_account_holder') }}">
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Bank Account Number</label>
                                <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number') }}">
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Swift Code</label>
                                <input type="text" name="swift_code" class="form-control" value="{{ old('swift_code') }}">
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Bank Branch</label>
                                <input type="text" name="bank_branch" class="form-control" value="{{ old('bank_branch') }}">
                            </div>
                            <div class="col-12">
                                <div class="ocr-switch">
                                    <div>
                                        <strong>Send Welcome Email</strong>
                                        <div class="ocr-meta">Ask before create and send only when enabled.</div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" name="send_welcome_email" value="1" @checked(old('send_welcome_email', true))>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ocr-actions">
            <a href="{{ $backUrl }}" class="ocr-secondary">Cancel</a>
            <div class="d-flex gap-2">
                <button type="button" class="ocr-secondary" data-ocr-reset><i class="ri-refresh-line"></i> Scan Again</button>
                <button type="button" class="ocr-secondary d-none" data-wizard-prev><i class="ri-arrow-left-line"></i> Back</button>
                <button type="button" class="ocr-primary" data-wizard-next>Continue <i class="ri-arrow-right-line"></i></button>
                <button type="submit" class="ocr-primary d-none" data-wizard-submit>Create {{ $roleTitle }} <i class="ri-check-line"></i></button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('change', function (event) {
    var input = event.target.closest('[data-ocr-preview]');
    if (!input) return;
    var upload = input.closest('[data-ocr-upload]') || input.closest('.ocr-camera');
    if (!upload || !input.files || !input.files.length) return;
    var file = input.files[0];
    upload.classList.add('has-file');
    var preview = upload.querySelector('.ocr-preview');
    if (!preview) return;
    preview.innerHTML = file.type.indexOf('image/') === 0 ? '' : '<i class="ri-file-pdf-2-line fs-2"></i>';
    if (file.type.indexOf('image/') === 0) {
        var image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.onload = function () { URL.revokeObjectURL(image.src); };
        preview.appendChild(image);
    }
});

document.addEventListener('click', function (event) {
    var documentTypeButton = event.target.closest('[data-doc-type]');
    if (documentTypeButton) {
        setDocumentType(documentTypeButton.getAttribute('data-doc-type'));
        return;
    }

    if (!event.target.closest('[data-ocr-reset]')) return;
    document.querySelectorAll('[data-ocr-preview]').forEach(function (input) { input.value = ''; });
    document.querySelectorAll('[data-ocr-upload]').forEach(function (upload) {
        upload.classList.remove('has-file');
        var preview = upload.querySelector('.ocr-preview');
        if (preview) preview.innerHTML = '<i class="' + previewIconForUpload(upload) + '"></i>';
    });
});

document.addEventListener('click', function (event) {
    if (event.target.closest('[data-wizard-next]')) {
        setWizardStep(2);
    }

    if (event.target.closest('[data-wizard-prev]')) {
        setWizardStep(1);
    }
});

function setWizardStep(step) {
    document.querySelectorAll('[data-wizard-panel]').forEach(function (panel) {
        panel.classList.toggle('d-none', panel.getAttribute('data-wizard-panel') !== String(step));
    });

    document.querySelectorAll('[data-wizard-step-label]').forEach(function (label) {
        label.classList.toggle('active', label.getAttribute('data-wizard-step-label') === String(step));
    });

    var next = document.querySelector('[data-wizard-next]');
    var previous = document.querySelector('[data-wizard-prev]');
    var submit = document.querySelector('[data-wizard-submit]');
    var reset = document.querySelector('[data-ocr-reset]');

    if (next) next.classList.toggle('d-none', step === 2);
    if (previous) previous.classList.toggle('d-none', step === 1);
    if (submit) submit.classList.toggle('d-none', step !== 2);
    if (reset) reset.classList.toggle('d-none', step === 2);
}

function setDocumentType(type) {
    document.querySelectorAll('[data-doc-type]').forEach(function (button) {
        button.classList.toggle('active', button.getAttribute('data-doc-type') === type);
    });

    var isPassport = type === 'passport';
    var heading = document.querySelector('[data-document-heading]');
    var idLabel = document.querySelector('[data-id-label]');
    var frontTitle = document.querySelector('[data-front-title]');
    var frontHelp = document.querySelector('[data-front-help]');
    var backTitle = document.querySelector('[data-back-title]');
    var backUpload = document.querySelector('[data-back-upload]');
    var frontIcon = document.querySelector('[data-front-icon]');
    var frontPreviewIcon = document.querySelector('[data-front-preview-icon]');

    if (heading) heading.textContent = isPassport ? 'Upload Passport' : 'Upload Emirates ID';
    if (idLabel) idLabel.textContent = isPassport ? 'Passport Number' : 'Emirates ID Number';
    if (frontTitle) frontTitle.textContent = isPassport ? 'Passport ID Page' : 'Front Side';
    if (frontHelp) frontHelp.innerHTML = isPassport ? 'Passport photo page<br>Max 10MB' : 'JPG, PNG or PDF<br>Max 10MB';
    if (backTitle) backTitle.textContent = 'Back Side';
    if (backUpload) backUpload.classList.toggle('d-none', isPassport);

    [frontIcon, frontPreviewIcon].forEach(function (icon) {
        if (!icon) return;
        icon.className = isPassport ? 'ri-passport-line' : 'ri-id-card-line';
    });
}

function previewIconForUpload(upload) {
    var fileInput = upload.querySelector('input[type="file"]');
    if (fileInput && fileInput.name === 'profile_photo') {
        return 'ri-user-smile-line';
    }

    if (upload.hasAttribute('data-back-upload')) {
        return 'ri-bank-card-2-line';
    }

    var activeType = document.querySelector('[data-doc-type].active');
    return activeType && activeType.getAttribute('data-doc-type') === 'passport'
        ? 'ri-passport-line'
        : 'ri-id-card-line';
}
</script>
@endpush
