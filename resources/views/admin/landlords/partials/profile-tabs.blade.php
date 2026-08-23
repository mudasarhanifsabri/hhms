<div class="card">
    <div class="card-body py-2">
        <ul class="nav nav-pills gap-2">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.landlord.show') ? 'active' : '' }}" href="{{ $detailsRoute ?? route('admin.landlord.show', $landlord->id) }}">
                    <i class="ri-user-line me-1"></i>Owner Details
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.landlord.account-statement') ? 'active' : '' }}" href="{{ $accountStatementRoute ?? route('admin.landlord.account-statement', $landlord->id) }}">
                    <i class="ri-file-list-3-line me-1"></i>Account Statement
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.landlord.owned-properties') ? 'active' : '' }}" href="{{ $ownedPropertiesRoute ?? route('admin.landlord.owned-properties', $landlord->id) }}">
                    <i class="ri-home-4-line me-1"></i>Owned Properties
                </a>
            </li>
            @if (Route::has('admin.landlord.security'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.landlord.security*') ? 'active' : '' }}" href="{{ ($securityRoute ?? null) ?: route('admin.landlord.security', $landlord->id) }}">
                        <i class="ri-shield-keyhole-line me-1"></i>Security
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
