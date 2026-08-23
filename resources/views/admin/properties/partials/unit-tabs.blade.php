<div class="card mb-3"><div class="card-body py-2"><ul class="nav nav-pills gap-2">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.show') ? 'active' : '' }}" href="{{ route('admin.property.show',$property) }}"><i class="ri-home-4-line me-1"></i>Unit Details</a></li>
    @if(Route::has('admin.property.document-wallet.index'))<li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.document-wallet.*') ? 'active' : '' }}" href="{{ route('admin.property.document-wallet.index',$property) }}"><i class="ri-folder-shield-2-line me-1"></i>Document Wallet</a></li>@endif
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.property.owner-documents.*') ? 'active' : '' }}" href="{{ route('admin.property.owner-documents.index',$property) }}"><i class="ri-file-sign-line me-1"></i>Agreement Signing</a></li>
</ul></div></div>
