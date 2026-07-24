@php
    $utilityTypes = \App\Models\UtilityAccount::TYPES;
    $responsibilities = \App\Models\UtilityAccount::RESPONSIBILITIES;
    $existingUtilityAccounts = isset($property)
        ? $property->utilityAccounts->keyBy('utility_type')
        : collect();
@endphp

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="card-title mb-0">Unit Utilities & Responsibility</h4>
            <p class="text-muted mb-0 small">Set DEWA, gas, internet, chiller, and other recurring utilities while creating the unit.</p>
        </div>
        <span class="badge bg-soft-primary text-primary">Accounting Linked</span>
    </div>
    <div class="card-body">
        <div class="accordion" id="unitUtilityAccordion">
            @foreach($utilityTypes as $type => $label)
                @php($account = $existingUtilityAccounts->get($type))
                @php($enabled = old("utility_accounts.$type.enabled", $account ? 1 : 0))
                <div class="accordion-item border rounded mb-2">
                    <h2 class="accordion-header" id="utilityHeading{{ $type }}">
                        <button class="accordion-button {{ $enabled ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#utilityPanel{{ $type }}" aria-expanded="{{ $enabled ? 'true' : 'false' }}" aria-controls="utilityPanel{{ $type }}">
                            <span class="me-2 fw-semibold">{{ $label }}</span>
                            @if($account)
                                <span class="badge bg-light text-dark">{{ $account->responsibility_label }}</span>
                            @endif
                        </button>
                    </h2>
                    <div id="utilityPanel{{ $type }}" class="accordion-collapse collapse {{ $enabled ? 'show' : '' }}" aria-labelledby="utilityHeading{{ $type }}" data-bs-parent="#unitUtilityAccordion">
                        <div class="accordion-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="utility_accounts[{{ $type }}][enabled]" value="1" id="utilityEnabled{{ $type }}" @checked($enabled)>
                                        <label class="form-check-label" for="utilityEnabled{{ $type }}">Enable</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Responsibility</label>
                                    <select name="utility_accounts[{{ $type }}][responsibility]" class="form-select">
                                        @foreach($responsibilities as $key => $responsibility)
                                            <option value="{{ $key }}" @selected(old("utility_accounts.$type.responsibility", $account->responsibility ?? 'company') === $key)>{{ $responsibility }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Supplier</label>
                                    <input name="utility_accounts[{{ $type }}][supplier]" class="form-control" value="{{ old("utility_accounts.$type.supplier", $account->supplier ?? '') }}" placeholder="DEWA, Du, Empower">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Account No.</label>
                                    <input name="utility_accounts[{{ $type }}][account_number]" class="form-control" value="{{ old("utility_accounts.$type.account_number", $account->account_number ?? '') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Portal Username</label>
                                    <input name="utility_accounts[{{ $type }}][username]" class="form-control" value="{{ old("utility_accounts.$type.username", $account->username ?? '') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Portal Password</label>
                                    <input type="password" name="utility_accounts[{{ $type }}][portal_password]" class="form-control" placeholder="{{ $account?->password_encrypted ? 'Leave blank to keep saved password' : '' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Contract No.</label>
                                    <input name="utility_accounts[{{ $type }}][contract_number]" class="form-control" value="{{ old("utility_accounts.$type.contract_number", $account->contract_number ?? '') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="utility_accounts[{{ $type }}][connection_status]" class="form-select">
                                        @foreach(['active' => 'Active', 'pending' => 'Pending', 'disconnected' => 'Disconnected'] as $key => $status)
                                            <option value="{{ $key }}" @selected(old("utility_accounts.$type.connection_status", $account->connection_status ?? 'active') === $key)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="utility_accounts[{{ $type }}][connection_start_date]" class="form-control" value="{{ old("utility_accounts.$type.connection_start_date", optional($account?->connection_start_date)->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="utility_accounts[{{ $type }}][contract_expiry_date]" class="form-control" value="{{ old("utility_accounts.$type.contract_expiry_date", optional($account?->contract_expiry_date)->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Billing Day</label>
                                    <input type="number" min="1" max="31" name="utility_accounts[{{ $type }}][billing_day]" class="form-control" value="{{ old("utility_accounts.$type.billing_day", $account->billing_day ?? '') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <input name="utility_accounts[{{ $type }}][notes]" class="form-control" value="{{ old("utility_accounts.$type.notes", $account->notes ?? '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
