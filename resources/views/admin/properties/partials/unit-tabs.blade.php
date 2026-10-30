@php
    $unitStatementOwnerId = $property->landlord_id;
    if (! $unitStatementOwnerId) {
        $unitStatementOwnerId = $property->ownerShares->sortByDesc('is_primary')->first()?->owner_id;
    }
@endphp
<div class="card mb-3"><div class="card-body py-2"><ul class="nav nav-pills flex-wrap gap-2">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.show') ? 'active' : '' }}" href="{{ route('admin.property.show',$property) }}"><i class="ri-home-4-line me-1"></i>Unit Details</a></li>
    <li class="nav-item">
        @if($unitStatementOwnerId)
            <a class="nav-link {{ request()->routeIs('admin.property.account-statement') ? 'active' : '' }}" href="{{ route('admin.property.account-statement', $property) }}"><i class="ri-file-list-3-line me-1"></i>Unit Account Statement</a>
        @else
            <span class="nav-link disabled" title="Assign an owner to open the unit statement"><i class="ri-file-list-3-line me-1"></i>Unit Account Statement</span>
        @endif
    </li>
    @if(Route::has('admin.property.document-wallet.index'))<li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.document-wallet.*') ? 'active' : '' }}" href="{{ route('admin.property.document-wallet.index',$property) }}"><i class="ri-folder-shield-2-line me-1"></i>Document Wallet</a></li>@endif
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.owner-documents.*') ? 'active' : '' }}" href="{{ route('admin.property.owner-documents.index',$property) }}"><i class="ri-file-sign-line me-1"></i>Agreement Signing</a></li>
    @can('accounting.view')
        @if(Route::has('admin.property.expenses'))
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.expenses') ? 'active' : '' }}" href="{{ route('admin.property.expenses',$property) }}"><i class="ri-money-dollar-circle-line me-1"></i>Expenses</a></li>
        @endif
    @endcan
</ul></div></div>
@if(Route::has('admin.inventory.index') && !request()->routeIs('admin.property.show'))<a class="btn btn-outline-primary btn-sm mb-3" href="{{ route('admin.inventory.index', ['property_id' => $property->id]) }}"><i class="ri-archive-line me-1"></i>Unit Inventory & Inspections</a>@endif
