<nav class="nav nav-underline gap-3 mb-3" aria-label="Booking navigation">
    <a class="nav-link {{ request()->routeIs('admin.booking.show')?'active':'' }}" href="{{ route('admin.booking.show',$booking) }}">Overview</a>
    <a class="nav-link" href="{{ route('admin.booking.show',$booking) }}#bookingInvoices">Invoices</a>
    <a class="nav-link {{ request()->routeIs('admin.booking.deposit-wallet')?'active':'' }}" href="{{ route('admin.booking.deposit-wallet',$booking) }}">Security Deposit</a>
    <a class="nav-link {{ request()->routeIs('admin.booking.history')?'active':'' }}" href="{{ route('admin.booking.history',$booking) }}">History & Corrections</a>
</nav>
