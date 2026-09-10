<div class="card mb-3"><div class="card-body py-2"><ul class="nav nav-pills gap-2">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.show') ? 'active' : '' }}" href="{{ route('admin.property.show',$property) }}"><i class="ri-home-4-line me-1"></i>Unit Details</a></li>
    @if(Route::has('admin.property.document-wallet.index'))<li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.document-wallet.*') ? 'active' : '' }}" href="{{ route('admin.property.document-wallet.index',$property) }}"><i class="ri-folder-shield-2-line me-1"></i>Document Wallet</a></li>@endif
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.owner-documents.*') ? 'active' : '' }}" href="{{ route('admin.property.owner-documents.index',$property) }}"><i class="ri-file-sign-line me-1"></i>Agreement Signing</a></li>
    @php($unitStatementOwnerId = $property->landlord_id ?: $property->ownerShares?->first()?->owner_id)
    <li class="nav-item">
        @if($unitStatementOwnerId)
            <a class="nav-link" href="{{ route('admin.landlord.account-statement', ['id' => $unitStatementOwnerId, 'property_id' => $property->id]) }}"><i class="ri-file-list-3-line me-1"></i>Unit Account Statement</a>
        @else
            <span class="nav-link disabled" title="Assign an owner to open the unit statement"><i class="ri-file-list-3-line me-1"></i>Unit Account Statement</span>
        @endif
    </li>
</ul></div></div>
@if(Route::has('admin.inventory.index'))<a class="btn btn-outline-primary btn-sm mb-3" href="{{ route('admin.inventory.index', ['property_id' => $property->id]) }}"><i class="ri-archive-line me-1"></i>Unit Inventory & Inspections</a>@endif
