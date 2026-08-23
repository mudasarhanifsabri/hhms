<!-- Right Side: Documents & Utilities (Full Width) -->
<div class="col-12">
    <div class="right-sidebar">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3 fw-semibold">Documents & Utilities</h5>

                <!-- Documents -->
                <fieldset class="mb-4 border rounded-3 p-3 bg-light-subtle">
                    <legend class="float-none w-auto px-2 text-primary fw-semibold">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Documents
                    </legend>
                    <div class="row">
                        <div class="col-12 mb-3"><div class="alert alert-info mb-0"><i class="ri-folder-shield-2-line me-1"></i>Upload DTCM Permits, Title Deeds, Insurance, and other files from the Unit Document Wallet after saving the unit.</div></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Permit No.</label>
                            <input type="text" name="dtcm_permit_no" class="form-control"
                                   value="{{ old('dtcm_permit_no', $property->dtcm_permit_no ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Permit Expiry</label>
                            <input type="date" name="dtcm_permit_expiry" class="form-control"
                                   value="{{ old('dtcm_permit_expiry', $property->dtcm_permit_expiry ?? '') }}">
                        </div>
                    </div>
                </fieldset>

                <!-- Utilities -->
                <fieldset class="mb-4 border rounded-3 p-3 bg-light-subtle">
                    <legend class="float-none w-auto px-2 text-success fw-semibold">
                        <i class="bi bi-lightning-charge me-2"></i>Utilities
                    </legend>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WiFi Provider</label>
                            <select name="wifi_provider" class="form-control">
                                <option value="">Choose provider</option>
                                <option value="du" {{ old('wifi_provider', $property->wifi_provider ?? '') == 'du' ? 'selected' : '' }}>du</option>
                                <option value="etisalat" {{ old('wifi_provider', $property->wifi_provider ?? '') == 'etisalat' ? 'selected' : '' }}>Etisalat</option>
                                <option value="virgin" {{ old('wifi_provider', $property->wifi_provider ?? '') == 'virgin' ? 'selected' : '' }}>Virgin Mobile</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WiFi Account No.</label>
                            <input type="text" name="wifi_account_no" class="form-control"
                                   value="{{ old('wifi_account_no', $property->wifi_account_no ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Electricity Provider</label>
                            <select name="electricity_provider" class="form-control">
                                <option value="">Select a provider</option>
                                <option value="dewa" {{ old('electricity_provider', $property->electricity_provider ?? '') == 'dewa' ? 'selected' : '' }}>DEWA (Dubai)</option>
                                <option value="sewa" {{ old('electricity_provider', $property->electricity_provider ?? '') == 'sewa' ? 'selected' : '' }}>SEWA (Sharjah)</option>
                                <option value="adwea" {{ old('electricity_provider', $property->electricity_provider ?? '') == 'adwea' ? 'selected' : '' }}>ADWEA (Abu Dhabi)</option>
                                <option value="fewa" {{ old('electricity_provider', $property->electricity_provider ?? '') == 'fewa' ? 'selected' : '' }}>FEWA (Northern Emirates)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Electricity Account No.</label>
                            <input type="text" name="electricity_account_no" class="form-control"
                                   value="{{ old('electricity_account_no', $property->electricity_account_no ?? '') }}">
                        </div>
                    </div>
                </fieldset>

                <!-- Smart Lock -->
                <fieldset class="border rounded-3 p-3 bg-light-subtle">
                    <legend class="float-none w-auto px-2 text-dark fw-semibold">
                        <i class="bi bi-shield-lock me-2"></i>Smart Lock
                    </legend>
                    <input type="text" name="smart_lock_info" class="form-control"
                           placeholder="e.g., Yale, Code 1234"
                           value="{{ old('smart_lock_info', $property->smart_lock_info ?? '') }}">
                </fieldset>
            </div>
        </div>
    </div>
</div>
