<!-- ========== App Menu Start ========== -->
@php($appLogoUrl = \App\Support\MediaStorage::url(config('hhms.logo_path')))
<div class="main-nav">
    <div class="logo-box">
        <a href="{{ route('dashboard') }}" class="logo-dark">
            <img src="{{ $appLogoUrl ?: asset('assets/images/logo-sm.png') }}" class="logo-sm" alt="{{ config('app.name') }}">
            <img src="{{ $appLogoUrl ?: asset('assets/images/logo-dark.png') }}" class="logo-lg" alt="{{ config('app.name') }}">
        </a>

        <a href="{{ route('dashboard') }}" class="logo-light">
            <img src="{{ $appLogoUrl ?: asset('assets/images/logo-sm.png') }}" class="logo-sm" alt="{{ config('app.name') }}">
            <img src="{{ $appLogoUrl ?: asset('assets/images/logo-light.png') }}" class="logo-lg" alt="{{ config('app.name') }}">
        </a>
    </div>

    <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
        <i class="ri-menu-2-line fs-24 button-sm-hover-icon"></i>
    </button>

    <div class="scrollbar" data-simplebar>
        <ul class="navbar-nav" id="navbar-nav">
            <li class="menu-title">Menu</li>
            @php($userRole = auth()->user()?->role)

            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <span class="nav-icon"><i class="ri-dashboard-2-line"></i></span>
                    <span class="nav-text"> Dashboard </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarProperty" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarProperty">
                    <span class="nav-icon"><i class="ri-community-line"></i></span>
                    <span class="nav-text"> Units </span>
                </a>
                <div class="collapse" id="sidebarProperty">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.property.index') }}">Unit List</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.property.grid') }}">Unit Grid</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.property.create') }}">Add Unit</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.building.index') }}">Buildings</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarLandlords" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarLandlords">
                    <span class="nav-icon"><i class="ri-user-star-line"></i></span>
                    <span class="nav-text"> Owners / Landlords </span>
                </a>
                <div class="collapse" id="sidebarLandlords">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.landlord.index') }}">List View</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.landlord.grid') }}">Grid View</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.landlord.create') }}">Add Owner</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarTenants" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTenants">
                    <span class="nav-icon"><i class="ri-contacts-book-3-line"></i></span>
                    <span class="nav-text"> Tenants </span>
                </a>
                <div class="collapse" id="sidebarTenants">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.tenant.index') }}">List View</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.tenant.grid') }}">Grid View</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.tenant.create') }}">Add Tenant</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarBookings" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarBookings">
                    <span class="nav-icon"><i class="ri-calendar-check-line"></i></span>
                    <span class="nav-text"> Bookings </span>
                </a>
                <div class="collapse" id="sidebarBookings">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.booking.index') }}">Booking List</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.booking.grid') }}">Booking Grid</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.booking.create') }}">Create Booking</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarTasks" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTasks">
                    <span class="nav-icon"><i class="ri-task-line"></i></span>
                    <span class="nav-text"> Tasks </span>
                </a>
                <div class="collapse" id="sidebarTasks">
                    <ul class="nav sub-navbar-nav">
                        @if($userRole === 'maintainer')
                            <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('maintainer.task.index') }}">List View</a></li>
                            <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('maintainer.task.grid') }}">Grid View</a></li>
                        @else
                            <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.task.index') }}">List View</a></li>
                            <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.task.grid') }}">Grid View</a></li>
                        @endif
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarInspections" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarInspections">
                    <span class="nav-icon"><i class="ri-survey-line"></i></span>
                    <span class="nav-text"> Inspections </span>
                </a>
                <div class="collapse" id="sidebarInspections">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.inspection.index') }}">Tracking</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarAccounting" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAccounting">
                    <span class="nav-icon"><i class="ri-calculator-line"></i></span>
                    <span class="nav-text"> Accounting </span>
                </a>
                <div class="collapse" id="sidebarAccounting">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.dashboard') }}">Dashboard</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.chart-of-accounts') }}">Chart of Accounts</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.bank-accounts') }}">Bank & Cash</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.vendors') }}">Vendors</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.ledger') }}">Ledger</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.expenses') }}">Expense Management</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.utilities') }}">Utilities</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.vat') }}">VAT Report</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.owner-statements') }}">Owner Statements</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.booking-invoices') }}">Booking Invoices</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.accounting.reports') }}">Reports</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarAgents" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAgents">
                    <span class="nav-icon"><i class="ri-group-line"></i></span>
                    <span class="nav-text"> Agents </span>
                </a>
                <div class="collapse" id="sidebarAgents">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.agent.index') }}">List View</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.agent.grid') }}">Grid View</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.agent.create') }}">Add Agent</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarMaintainers" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarMaintainers">
                    <span class="nav-icon"><i class="ri-user-settings-line"></i></span>
                    <span class="nav-text"> Maintainers </span>
                </a>
                <div class="collapse" id="sidebarMaintainers">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.maintainer.index') }}">List View</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.maintainer.grid') }}">Grid View</a></li>
                        <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('admin.maintainer.create') }}">Add Maintainer</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.settings.edit') }}">
                    <span class="nav-icon"><i class="ri-settings-3-line"></i></span>
                    <span class="nav-text"> Settings </span>
                </a>
            </li>
        </ul>
    </div>
</div>
