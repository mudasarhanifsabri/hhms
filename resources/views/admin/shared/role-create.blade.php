@php
    $countries = \App\Support\CountryOptions::all();
    $nationalities = \App\Support\CountryOptions::nationalities();
    $defaultCountry = collect($countries)->firstWhere('iso', old('phone_country_iso', 'AE')) ?? collect($countries)->firstWhere('iso', 'AE');
    $phoneCountryLabel = old('phone_country_display', \App\Support\CountryOptions::phoneLabel($defaultCountry));
    $phoneDialCode = old('phone_country_code', $defaultCountry['dial']);
    $phoneLocal = old('phone_local', old('phone'));
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
    .ocr-upload .ocr-progress { grid-column: 1 / -1; height: 6px; border-radius: 99px; background: #eef2f7; overflow: hidden; display: none; }
    .ocr-upload .ocr-progress span { display: block; width: 0; height: 100%; border-radius: inherit; background: linear-gradient(135deg, #6d4dfc, #10b981); transition: width .28s ease; }
    .ocr-upload.processing .ocr-progress { display: block; }
    .ocr-upload.error { border-color: #fda29b; background: #fff8f7; }
    .ocr-upload.error .ocr-upload-status { color: #b42318; }
    .ocr-preview { width: 118px; height: 58px; border-radius: 7px; object-fit: cover; background: #f8fafc; border: 1px solid var(--ocr-line); display: grid; place-items: center; color: var(--ocr-muted); overflow: hidden; font-size: 20px; }
    .ocr-preview img { width: 100%; height: 100%; object-fit: cover; }
    .ocr-placeholder { width: 100%; height: 100%; padding: 8px; background: linear-gradient(135deg, #ffffff, #eef4ff); display: grid; align-content: center; gap: 4px; }
    .ocr-placeholder-line { display: block; height: 5px; border-radius: 99px; background: #cbd5e1; }
    .ocr-placeholder-line.short { width: 42%; }
    .ocr-placeholder-line.mid { width: 64%; }
    .ocr-placeholder-id { grid-template-columns: 28px 1fr; column-gap: 8px; }
    .ocr-placeholder-id .ocr-placeholder-photo { grid-row: 1 / 4; width: 28px; height: 34px; border-radius: 6px; background: #dbeafe; border: 1px solid #bfdbfe; }
    .ocr-placeholder-id:before { content: ""; position: absolute; }
    .ocr-placeholder-back { background: linear-gradient(135deg, #ffffff, #f7f7fb); }
    .ocr-placeholder-back .ocr-placeholder-chip { height: 13px; width: 36px; border-radius: 4px; background: #d9c99f; }
    .ocr-placeholder-passport { background: linear-gradient(135deg, #f6f1ff, #e9f3ff); }
    .ocr-placeholder-passport .ocr-placeholder-emblem { width: 26px; height: 26px; border-radius: 50%; border: 2px solid #7c3aed; margin: 0 auto 2px; opacity: .8; }
    .ocr-placeholder-user { background: linear-gradient(135deg, #f8fafc, #eef2ff); }
    .ocr-placeholder-user .ocr-placeholder-avatar { width: 30px; height: 30px; border-radius: 50%; background: #dbeafe; margin: 0 auto; position: relative; }
    .ocr-placeholder-user .ocr-placeholder-avatar:after { content: ""; position: absolute; left: -8px; right: -8px; bottom: -16px; height: 18px; border-radius: 50% 50% 0 0; background: #c7d2fe; }
    .ocr-camera { width: 100%; border: 1px solid #d0d5dd; border-radius: 8px; background: #fff; padding: 10px 14px; color: var(--ocr-primary); font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 4px; cursor: pointer; }
    .ocr-confidence { border-radius: 999px; background: #dff8ea; color: #027a48; padding: 8px 12px; font-weight: 800; font-size: 12px; }
    .ocr-confidence.is-warn { background: #fff3cd; color: #946200; }
    .ocr-confidence.is-error { background: #fee4e2; color: #b42318; }
    .ocr-status-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
    .ocr-status-pill { border: 1px solid var(--ocr-line); border-radius: 10px; padding: 10px 12px; background: #fbfcff; }
    .ocr-status-pill span { display: block; color: var(--ocr-muted); font-size: 11px; font-weight: 700; }
    .ocr-status-pill strong { display: block; font-size: 13px; margin-top: 2px; }
    .ocr-field label { font-weight: 700; font-size: 12px; margin-bottom: 6px; color: #344054; }
    .ocr-field .form-control, .ocr-field .form-select { min-height: 42px; border-radius: 8px; border-color: #d9dee8; }
    .ocr-field .input-group-text { border-radius: 8px 0 0 8px; background: #fff; }
    .ocr-phone-grid { display: grid; grid-template-columns: minmax(190px, .85fr) 1fr; gap: 8px; }
    .ocr-country-input { font-weight: 700; }
    .ocr-combo { position: relative; }
    .ocr-combo:after { content: "\ea4e"; font-family: remixicon; position: absolute; right: 13px; top: 50%; transform: translateY(-50%); color: #667085; pointer-events: none; }
    .ocr-combo .form-control { padding-right: 38px; }
    .ocr-combo-menu { position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 30; max-height: 220px; overflow: auto; border: 1px solid #d9dee8; border-radius: 10px; background: #fff; box-shadow: 0 18px 36px rgba(16, 24, 40, .16); padding: 6px; display: none; }
    .ocr-combo.open .ocr-combo-menu { display: block; }
    .ocr-combo-option { width: 100%; border: 0; background: transparent; border-radius: 7px; padding: 9px 10px; text-align: left; color: #111827; font-weight: 600; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .ocr-combo-option:hover, .ocr-combo-option.active { background: #f4f2ff; color: var(--ocr-primary); }
    .ocr-combo-empty { padding: 10px; color: var(--ocr-muted); font-size: 13px; display: none; }
    .ocr-combo.no-results .ocr-combo-empty { display: block; }
    .ocr-actions { position: sticky; bottom: 0; background: rgba(255,255,255,.96); border-top: 1px solid var(--ocr-line); padding: 12px 0 0; margin-top: 12px; display: flex; justify-content: space-between; gap: 14px; }
    .ocr-primary { background: linear-gradient(135deg, #6d4dfc, #3d2cf0); color: #fff; border: 0; border-radius: 8px; padding: 11px 28px; font-weight: 800; }
    .ocr-secondary { border: 1px solid #cfd5e1; background: #fff; border-radius: 8px; padding: 11px 22px; font-weight: 800; color: #344054; }
    .ocr-switch { border: 1px solid var(--ocr-line); border-radius: 9px; padding: 13px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #fbfcff; }
    @media (max-width: 991px) { .ocr-steps { grid-template-columns: 1fr; } .ocr-step:after { display: none; } .ocr-upload { grid-template-columns: 44px 1fr; } .ocr-preview { grid-column: 1 / -1; width: 100%; height: 160px; } .ocr-phone-grid, .ocr-status-grid { grid-template-columns: 1fr; } }
</style>
@endpush

<div class="ocr-create-shell">
    <a href="{{ $backUrl }}" class="ocr-back"><i class="ri-arrow-left-line"></i> Back to {{ $backLabel ?? $roleTitle . 's' }}</a>

    <div class="ocr-page-head">
        <div class="ocr-title">
            <h4>Add New {{ $roleTitle }}</h4>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><strong>Please check the form.</strong> {{ $errors->first() }}</div>
    @endif

    <form action="{{ $storeRoute }}" method="POST" enctype="multipart/form-data" data-ocr-endpoint="{{ url('/admin/document-ocr') }}">
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
                            <span><strong>Photo</strong><small>JPG or PNG</small><span class="ocr-ok"><i class="ri-check-line"></i> Uploaded</span><small class="ocr-upload-status"></small></span>
                            <span class="ocr-preview">{!! $placeholderUser ?? '<span class="ocr-placeholder ocr-placeholder-user"><span class="ocr-placeholder-avatar"></span></span>' !!}</span>
                            <span class="ocr-progress"><span></span></span>
                        </label>

                        <label class="form-label fw-semibold mt-1" data-document-heading>Upload Emirates ID</label>
                        <label class="ocr-upload" data-ocr-upload>
                            <input type="file" name="id_document" accept="image/*,.pdf" data-ocr-preview>
                            <span class="ocr-upload-icon"><i class="ri-id-card-line" data-front-icon></i></span>
                            <span><strong data-front-title>Front Side</strong><small data-front-help>JPG, PNG or PDF<br>Max 10MB</small><span class="ocr-ok"><i class="ri-check-line"></i> Uploaded</span><small class="ocr-upload-status"></small></span>
                            <span class="ocr-preview" data-front-preview>{!! $placeholderIdFront ?? '<span class="ocr-placeholder ocr-placeholder-id"><span class="ocr-placeholder-photo"></span><span class="ocr-placeholder-line mid"></span><span class="ocr-placeholder-line"></span><span class="ocr-placeholder-line short"></span></span>' !!}</span>
                            <span class="ocr-progress"><span></span></span>
                        </label>
                        <label class="ocr-upload" data-ocr-upload data-back-upload>
                            <input type="file" name="id_document_back" accept="image/*,.pdf" data-ocr-preview>
                            <span class="ocr-upload-icon"><i class="ri-bank-card-2-line" data-back-icon></i></span>
                            <span><strong data-back-title>Back Side</strong><small data-back-help>JPG, PNG or PDF<br>Max 10MB</small><span class="ocr-ok"><i class="ri-check-line"></i> Uploaded</span><small class="ocr-upload-status"></small></span>
                            <span class="ocr-preview" data-back-preview>{!! $placeholderIdBack ?? '<span class="ocr-placeholder ocr-placeholder-back"><span class="ocr-placeholder-chip"></span><span class="ocr-placeholder-line"></span><span class="ocr-placeholder-line mid"></span></span>' !!}</span>
                            <span class="ocr-progress"><span></span></span>
                        </label>
                        <label class="ocr-camera">
                            <i class="ri-camera-line"></i> Scan with Camera
                            <input type="file" name="camera_capture" accept="image/*" capture="environment" hidden data-ocr-preview>
                        </label>

                        <div class="alert alert-warning d-none mb-0 mt-3" data-ocr-error></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="ocr-panel">
                    <div class="ocr-panel-body">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
                            <h5 class="fw-bold mb-0">Document Information</h5>
                            <span class="ocr-confidence is-warn" data-ocr-confidence>Waiting Upload</span>
                        </div>

                        <div class="ocr-status-grid">
                            <div class="ocr-status-pill"><span>Document</span><strong data-ocr-doc-status>Not uploaded</strong></div>
                            <div class="ocr-status-pill"><span>Profile Crop</span><strong data-ocr-crop-status>Not started</strong></div>
                            <div class="ocr-status-pill"><span>Accuracy</span><strong data-ocr-accuracy>Manual review</strong></div>
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
                                <div class="ocr-phone-grid">
                                    <div class="ocr-combo" data-combo>
                                        <input type="text" name="phone_country_display" class="form-control ocr-country-input" value="{{ $phoneCountryLabel }}" data-combo-input data-phone-country-display autocomplete="off" required>
                                        <div class="ocr-combo-menu">
                                            @foreach($countries as $country)
                                                <button type="button" class="ocr-combo-option" data-combo-option data-value="{{ \App\Support\CountryOptions::phoneLabel($country) }}" data-iso="{{ $country['iso'] }}" data-dial="{{ $country['dial'] }}">
                                                    <span>{{ \App\Support\CountryOptions::phoneLabel($country) }}</span>
                                                </button>
                                            @endforeach
                                            <div class="ocr-combo-empty">No country found</div>
                                        </div>
                                    </div>
                                    <input type="text" name="phone_local" class="form-control" value="{{ $phoneLocal }}" placeholder="50 123 4567" data-phone-local required>
                                </div>
                                <input type="hidden" name="phone_country_iso" value="{{ old('phone_country_iso', $defaultCountry['iso']) }}" data-phone-country-iso>
                                <input type="hidden" name="phone_country_code" value="{{ $phoneDialCode }}" data-phone-country-code>
                                <input type="hidden" name="phone" value="{{ old('phone') }}" data-phone-full>
                            </div>
                            <div class="col-md-6 ocr-field">
                                <label>Nationality</label>
                                <div class="ocr-combo" data-combo>
                                    <input type="text" name="nationality" class="form-control" value="{{ old('nationality', 'Emirati') }}" data-combo-input placeholder="Search nationality" autocomplete="off">
                                    <div class="ocr-combo-menu">
                                        @foreach($nationalities as $nationality)
                                            <button type="button" class="ocr-combo-option" data-combo-option data-value="{{ $nationality }}">
                                                <span>{{ $nationality }}</span>
                                            </button>
                                        @endforeach
                                        <div class="ocr-combo-empty">No nationality found</div>
                                    </div>
                                </div>
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
    var validationError = validateUploadFile(file);
    if (validationError) {
        setUploadState(upload, 'error', validationError, 0);
        setOcrError(validationError);
        input.value = '';
        return;
    }

    setUploadState(upload, 'processing', 'Uploading...', 25);
    upload.classList.add('has-file');
    var preview = upload.querySelector('.ocr-preview');
    if (!preview) return;
    preview.innerHTML = file.type.indexOf('image/') === 0 ? '' : '<i class="ri-file-pdf-2-line fs-2"></i>';
    if (file.type.indexOf('image/') === 0) {
        var image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.onload = function () { URL.revokeObjectURL(image.src); };
        preview.appendChild(image);
        simulateUploadProgress(upload, function () {
            setUploadState(upload, 'done', 'Ready for review', 100);
            updateOcrStatus('Document uploaded', 'Review required', 'Medium confidence', 'warn');
            if (input.name === 'id_document' || input.name === 'camera_capture') {
                runDocumentOcr(input, file);
                tryAutoCropProfileFromDocument(file);
            }
        });
    } else {
        simulateUploadProgress(upload, function () {
            setUploadState(upload, 'done', 'PDF ready for review', 100);
            updateOcrStatus('PDF uploaded', 'Manual crop needed', 'Manual review', 'warn');
            if (input.name === 'id_document' || input.name === 'camera_capture') {
                runDocumentOcr(input, file);
            }
        });
    }
});

document.addEventListener('input', function (event) {
    var comboInput = event.target.closest('[data-combo-input]');
    if (comboInput) {
        openCombo(comboInput.closest('[data-combo]'));
        filterCombo(comboInput.closest('[data-combo]'));
    }

    if (event.target.closest('[data-phone-country-display]') || event.target.closest('[data-phone-local]')) {
        syncPhoneValue();
    }
});

document.addEventListener('submit', function (event) {
    if (event.target.closest('.ocr-create-shell form')) {
        syncPhoneValue();
    }
});

document.addEventListener('click', function (event) {
    var comboInput = event.target.closest('[data-combo-input]');
    if (comboInput) {
        openCombo(comboInput.closest('[data-combo]'));
        filterCombo(comboInput.closest('[data-combo]'), true);
        comboInput.select();
        return;
    }

    var comboOption = event.target.closest('[data-combo-option]');
    if (comboOption) {
        selectComboOption(comboOption);
        return;
    }

    if (!event.target.closest('[data-combo]')) {
        closeCombos();
    }
});

document.addEventListener('focusin', function (event) {
    var comboInput = event.target.closest('[data-combo-input]');
    if (comboInput) {
        openCombo(comboInput.closest('[data-combo]'));
        filterCombo(comboInput.closest('[data-combo]'), true);
    }
});

document.addEventListener('keydown', function (event) {
    var input = event.target.closest('[data-combo-input]');
    if (!input) return;
    var combo = input.closest('[data-combo]');
    var visibleOptions = Array.prototype.slice.call(combo.querySelectorAll('[data-combo-option]')).filter(function (option) {
        return option.style.display !== 'none';
    });
    var activeIndex = visibleOptions.findIndex(function (option) { return option.classList.contains('active'); });

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        openCombo(combo);
        setComboActive(visibleOptions, activeIndex + 1);
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        setComboActive(visibleOptions, activeIndex - 1);
    }

    if (event.key === 'Enter' && combo.classList.contains('open')) {
        var active = visibleOptions.find(function (option) { return option.classList.contains('active'); }) || visibleOptions[0];
        if (active) {
            event.preventDefault();
            selectComboOption(active);
        }
    }

    if (event.key === 'Escape') {
        closeCombos();
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
        setUploadState(upload, 'idle', '', 0);
        var preview = upload.querySelector('.ocr-preview');
        if (preview) preview.innerHTML = previewPlaceholderForUpload(upload);
    });
    setOcrError('');
    updateOcrStatus('Not uploaded', 'Not started', 'Manual review', 'warn');
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
    var frontPreview = document.querySelector('[data-front-preview]');
    var backPreview = document.querySelector('[data-back-preview]');

    if (heading) heading.textContent = isPassport ? 'Upload Passport' : 'Upload Emirates ID';
    if (idLabel) idLabel.textContent = isPassport ? 'Passport Number' : 'Emirates ID Number';
    if (frontTitle) frontTitle.textContent = isPassport ? 'Passport ID Page' : 'Front Side';
    if (frontHelp) frontHelp.innerHTML = isPassport ? 'Passport photo page<br>Max 10MB' : 'JPG, PNG or PDF<br>Max 10MB';
    if (backTitle) backTitle.textContent = 'Back Side';
    if (backUpload) backUpload.classList.toggle('d-none', isPassport);

    if (frontIcon) frontIcon.className = isPassport ? 'ri-passport-line' : 'ri-id-card-line';

    if (frontPreview && !frontPreview.closest('[data-ocr-upload]').classList.contains('has-file')) {
        frontPreview.innerHTML = isPassport ? passportPlaceholder() : idFrontPlaceholder();
    }

    if (backPreview && !backPreview.closest('[data-ocr-upload]').classList.contains('has-file')) {
        backPreview.innerHTML = idBackPlaceholder();
    }
}

function previewPlaceholderForUpload(upload) {
    var fileInput = upload.querySelector('input[type="file"]');
    if (fileInput && fileInput.name === 'profile_photo') {
        return userPlaceholder();
    }

    if (upload.hasAttribute('data-back-upload')) {
        return idBackPlaceholder();
    }

    var activeType = document.querySelector('[data-doc-type].active');
    return activeType && activeType.getAttribute('data-doc-type') === 'passport'
        ? passportPlaceholder()
        : idFrontPlaceholder();
}

function userPlaceholder() {
    return '<span class="ocr-placeholder ocr-placeholder-user"><span class="ocr-placeholder-avatar"></span></span>';
}

function idFrontPlaceholder() {
    return '<span class="ocr-placeholder ocr-placeholder-id"><span class="ocr-placeholder-photo"></span><span class="ocr-placeholder-line mid"></span><span class="ocr-placeholder-line"></span><span class="ocr-placeholder-line short"></span></span>';
}

function idBackPlaceholder() {
    return '<span class="ocr-placeholder ocr-placeholder-back"><span class="ocr-placeholder-chip"></span><span class="ocr-placeholder-line"></span><span class="ocr-placeholder-line mid"></span></span>';
}

function passportPlaceholder() {
    return '<span class="ocr-placeholder ocr-placeholder-passport"><span class="ocr-placeholder-emblem"></span><span class="ocr-placeholder-line"></span><span class="ocr-placeholder-line mid"></span></span>';
}

function syncPhoneValue() {
    var countryInput = document.querySelector('[data-phone-country-display]');
    var localInput = document.querySelector('[data-phone-local]');
    var fullInput = document.querySelector('[data-phone-full]');
    var isoInput = document.querySelector('[data-phone-country-iso]');
    var codeInput = document.querySelector('[data-phone-country-code]');
    if (!countryInput || !localInput || !fullInput) return;

    var selected = Array.prototype.slice.call(document.querySelectorAll('[data-phone-country-display] + .ocr-combo-menu [data-combo-option]')).find(function (option) {
        return option.getAttribute('data-value') === countryInput.value;
    });
    var dialCode = selected ? selected.getAttribute('data-dial') : (codeInput ? codeInput.value : '+971');

    if (selected) {
        if (isoInput) isoInput.value = selected.getAttribute('data-iso') || '';
        if (codeInput) codeInput.value = dialCode;
    }

    var localValue = localInput.value.trim();
    fullInput.value = localValue.indexOf('+') === 0 ? localValue : (dialCode + ' ' + localValue).trim();
}

syncPhoneValue();

function openCombo(combo) {
    if (!combo) return;
    closeCombos(combo);
    combo.classList.add('open');
}

function closeCombos(except) {
    document.querySelectorAll('[data-combo].open').forEach(function (combo) {
        if (combo !== except) combo.classList.remove('open');
    });
}

function filterCombo(combo, showAll) {
    if (!combo) return;
    var input = combo.querySelector('[data-combo-input]');
    var query = !showAll && input ? input.value.toLowerCase().trim() : '';
    var visibleCount = 0;
    combo.querySelectorAll('[data-combo-option]').forEach(function (option) {
        var matches = option.getAttribute('data-value').toLowerCase().indexOf(query) !== -1;
        option.style.display = matches ? '' : 'none';
        option.classList.remove('active');
        if (matches) visibleCount++;
    });
    combo.classList.toggle('no-results', visibleCount === 0);
    setComboActive(Array.prototype.slice.call(combo.querySelectorAll('[data-combo-option]')).filter(function (option) {
        return option.style.display !== 'none';
    }), 0);
}

function selectComboOption(option) {
    var combo = option.closest('[data-combo]');
    var input = combo.querySelector('[data-combo-input]');
    if (!input) return;
    input.value = option.getAttribute('data-value');

    if (input.hasAttribute('data-phone-country-display')) {
        var isoInput = document.querySelector('[data-phone-country-iso]');
        var codeInput = document.querySelector('[data-phone-country-code]');
        if (isoInput) isoInput.value = option.getAttribute('data-iso') || '';
        if (codeInput) codeInput.value = option.getAttribute('data-dial') || '';
        syncPhoneValue();
    }

    closeCombos();
}

function setComboActive(options, index) {
    if (!options.length) return;
    var nextIndex = ((index % options.length) + options.length) % options.length;
    options.forEach(function (option, optionIndex) {
        option.classList.toggle('active', optionIndex === nextIndex);
    });
    options[nextIndex].scrollIntoView({ block: 'nearest' });
}

function validateUploadFile(file) {
    var maxSize = 10 * 1024 * 1024;
    var allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    if (file.size > maxSize) {
        return 'File is too large. Maximum allowed size is 10MB.';
    }

    if (allowed.indexOf(file.type) === -1) {
        return 'Unsupported file type. Please upload PDF, JPG, PNG or WebP.';
    }

    return '';
}

function setUploadState(upload, state, message, percent) {
    if (!upload) return;
    upload.classList.toggle('processing', state === 'processing');
    upload.classList.toggle('error', state === 'error');

    var status = upload.querySelector('.ocr-upload-status');
    if (status) status.textContent = message || '';

    var bar = upload.querySelector('.ocr-progress span');
    if (bar) bar.style.width = (percent || 0) + '%';
}

function simulateUploadProgress(upload, callback) {
    var progress = 25;
    var timer = setInterval(function () {
        progress += 25;
        setUploadState(upload, 'processing', progress < 100 ? 'Processing...' : 'Finalizing...', Math.min(progress, 100));
        if (progress >= 100) {
            clearInterval(timer);
            callback();
        }
    }, 160);
}

function updateOcrStatus(documentStatus, cropStatus, accuracy, confidenceState) {
    var documentNode = document.querySelector('[data-ocr-doc-status]');
    var cropNode = document.querySelector('[data-ocr-crop-status]');
    var accuracyNode = document.querySelector('[data-ocr-accuracy]');
    var confidence = document.querySelector('[data-ocr-confidence]');

    if (documentNode) documentNode.textContent = documentStatus;
    if (cropNode) cropNode.textContent = cropStatus;
    if (accuracyNode) accuracyNode.textContent = accuracy;
    if (confidence) {
        confidence.classList.toggle('is-error', confidenceState === 'error');
        confidence.classList.toggle('is-warn', confidenceState === 'warn');
        confidence.textContent = confidenceState === 'error' ? 'Needs Review' : (confidenceState === 'ok' ? 'Profile Detected' : 'Review Required');
    }
}

async function runDocumentOcr(input, file) {
    var form = document.querySelector('.ocr-create-shell form');
    var endpoint = form ? form.getAttribute('data-ocr-endpoint') : '';
    var token = form ? form.querySelector('input[name="_token"]') : null;

    if (!endpoint || !token || input.name === 'profile_photo') return;
    var isOcrFile = file.type.indexOf('image/') === 0 || file.type === 'application/pdf';
    if (!isOcrFile) {
        updateOcrStatus('Document uploaded', 'Manual review', 'Manual review', 'warn');
        return;
    }

    updateOcrStatus('OCR processing', 'Review required', 'Reading document', 'warn');
    setOcrError('');

    var payload = new FormData();
    payload.append('_token', token.value);
    payload.append('document', file);
    payload.append('document_type', getActiveDocumentType());

    try {
        var response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload,
        });
        var result = await response.json();

        if (!response.ok || !result.ok) {
            throw new Error(result.message || 'OCR failed. Please enter details manually.');
        }

        applyOcrData(result.data || {});
    } catch (error) {
        updateOcrStatus('OCR failed', 'Review required', 'Manual review', 'error');
        setOcrError(error.message || 'OCR failed. Please enter details manually.');
    }
}

function getActiveDocumentType() {
    var activeType = document.querySelector('[data-doc-type].active');
    return activeType ? activeType.getAttribute('data-doc-type') : 'emirates_id';
}

function applyOcrData(data) {
    setFieldValue('name', data.name);
    setFieldValue('name_ar', data.name_ar);
    setFieldValue('eid_passport_no', data.eid_passport_no);
    setFieldValue('dob', data.dob);
    setFieldValue('id_issue_date', data.id_issue_date);
    setFieldValue('id_expiry_date', data.id_expiry_date);
    setFieldValue('nationality', data.nationality);
    setSelectValue('gender', data.gender);

    var confidence = parseInt(data.confidence || 0, 10);
    var confidenceState = confidence >= 80 ? 'ok' : (confidence >= 45 ? 'warn' : 'error');
    var accuracy = confidence ? (confidence + '% confidence') : 'Manual review';
    updateOcrStatus('OCR completed', document.querySelector('[data-ocr-crop-status]')?.textContent || 'Review required', accuracy, confidenceState);

    if (data.warnings && data.warnings.length) {
        setOcrError(data.warnings.join(' '));
    } else {
        setOcrError('');
    }
}

function setFieldValue(name, value) {
    if (!value) return;
    var field = document.querySelector('[name="' + name + '"]');
    if (field && !field.value) field.value = value;
}

function setSelectValue(name, value) {
    if (!value) return;
    var field = document.querySelector('select[name="' + name + '"]');
    if (!field) return;
    Array.prototype.slice.call(field.options).forEach(function (option) {
        if (option.value.toLowerCase() === String(value).toLowerCase()) {
            field.value = option.value;
        }
    });
}

function setOcrError(message) {
    var error = document.querySelector('[data-ocr-error]');
    if (!error) return;
    error.textContent = message || '';
    error.classList.toggle('d-none', !message);
}

async function tryAutoCropProfileFromDocument(file) {
    if (!file.type || file.type.indexOf('image/') !== 0) return;

    if (!('FaceDetector' in window)) {
        updateOcrStatus('Image uploaded', 'Manual photo needed', 'Manual review', 'warn');
        setOcrError('Profile photo could not be auto-cropped in this browser. Upload a profile photo manually if needed.');
        return;
    }

    try {
        var bitmap = await createImageBitmap(file);
        var detector = new FaceDetector({ fastMode: true, maxDetectedFaces: 3 });
        var faces = await detector.detect(bitmap);

        if (!faces.length) {
            updateOcrStatus('Image uploaded', 'Face not detected', 'Manual review', 'warn');
            setOcrError('Face was not detected clearly. Please upload profile photo manually or use a clearer document image.');
            return;
        }

        var face = faces.sort(function (a, b) {
            return (b.boundingBox.width * b.boundingBox.height) - (a.boundingBox.width * a.boundingBox.height);
        })[0].boundingBox;
        var croppedBlob = cropFaceFromBitmap(bitmap, face);
        if (!croppedBlob) return;

        var profileInput = document.querySelector('input[name="profile_photo"]');
        if (!profileInput || typeof DataTransfer === 'undefined') return;

        var croppedFile = new File([croppedBlob], 'profile-from-document.jpg', { type: 'image/jpeg' });
        var transfer = new DataTransfer();
        transfer.items.add(croppedFile);
        profileInput.files = transfer.files;

        var profileUpload = profileInput.closest('[data-ocr-upload]');
        if (profileUpload) {
            var preview = profileUpload.querySelector('.ocr-preview');
            profileUpload.classList.add('has-file');
            setUploadState(profileUpload, 'done', 'Auto-cropped from document', 100);
            if (preview) {
                preview.innerHTML = '';
                var image = document.createElement('img');
                image.src = URL.createObjectURL(croppedBlob);
                image.onload = function () { URL.revokeObjectURL(image.src); };
                preview.appendChild(image);
            }
        }

        setOcrError('');
        updateOcrStatus('Image uploaded', 'Profile cropped', 'High confidence', 'ok');
    } catch (error) {
        updateOcrStatus('Image uploaded', 'Crop failed', 'Manual review', 'warn');
        setOcrError('Auto-crop could not read this image clearly. Please upload profile photo manually.');
    }
}

function cropFaceFromBitmap(bitmap, face) {
    var paddingX = face.width * 0.75;
    var paddingTop = face.height * 0.9;
    var paddingBottom = face.height * 1.05;
    var cropX = Math.max(0, face.x - paddingX);
    var cropY = Math.max(0, face.y - paddingTop);
    var cropW = Math.min(bitmap.width - cropX, face.width + paddingX * 2);
    var cropH = Math.min(bitmap.height - cropY, face.height + paddingTop + paddingBottom);

    var canvas = document.createElement('canvas');
    canvas.width = 480;
    canvas.height = 600;
    var context = canvas.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(bitmap, cropX, cropY, cropW, cropH, 0, 0, canvas.width, canvas.height);

    var dataUrl = canvas.toDataURL('image/jpeg', .86);
    var parts = dataUrl.split(',');
    var binary = atob(parts[1]);
    var bytes = new Uint8Array(binary.length);
    for (var index = 0; index < binary.length; index++) {
        bytes[index] = binary.charCodeAt(index);
    }

    return new Blob([bytes], { type: 'image/jpeg' });
}
</script>
@endpush
