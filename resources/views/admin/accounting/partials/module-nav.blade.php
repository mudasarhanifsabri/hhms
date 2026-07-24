@once
    @push('styles')
        <style>
            .accounting-tabbar{display:flex;gap:.5rem;overflow:auto;padding-bottom:.35rem}
            .accounting-tabbar .btn{white-space:nowrap}
            .finance-card{border:1px solid #e9edf3;box-shadow:0 8px 24px rgba(15,23,42,.04)}
            .finance-metric{font-size:1.35rem;font-weight:700;color:#111827}
            .utility-cell{min-width:150px}
            .a4-pdf{font-family:DejaVu Sans,Arial,sans-serif;color:#111827;font-size:12px}
            .a4-pdf table{width:100%;border-collapse:collapse}
            .a4-pdf th,.a4-pdf td{border:1px solid #d8dee9;padding:7px}
            .a4-pdf th{background:#f3f6fb}
        </style>
    @endpush
@endonce

@php
    $tabs = [
        ['label' => 'Dashboard', 'route' => 'admin.accounting.dashboard', 'icon' => 'ri-dashboard-3-line'],
        ['label' => 'Chart of Accounts', 'route' => 'admin.accounting.chart-of-accounts', 'icon' => 'ri-node-tree'],
        ['label' => 'Bank & Cash', 'route' => 'admin.accounting.bank-accounts', 'icon' => 'ri-bank-card-line'],
        ['label' => 'Vendors', 'route' => 'admin.accounting.vendors', 'icon' => 'ri-store-2-line'],
        ['label' => 'Ledger', 'route' => 'admin.accounting.ledger', 'icon' => 'ri-book-2-line'],
        ['label' => 'Expenses', 'route' => 'admin.accounting.expenses', 'icon' => 'ri-receipt-line'],
        ['label' => 'Utilities', 'route' => 'admin.accounting.utilities', 'icon' => 'ri-flashlight-line'],
        ['label' => 'VAT', 'route' => 'admin.accounting.vat', 'icon' => 'ri-percent-line'],
        ['label' => 'Owner Statements', 'route' => 'admin.accounting.owner-statements', 'icon' => 'ri-file-list-3-line'],
        ['label' => 'Booking Invoices', 'route' => 'admin.accounting.booking-invoices', 'icon' => 'ri-bill-line'],
        ['label' => 'Reports', 'route' => 'admin.accounting.reports', 'icon' => 'ri-bar-chart-box-line'],
    ];
@endphp

<div class="accounting-tabbar mb-3">
    @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}" class="btn btn-sm {{ request()->routeIs($tab['route']) ? 'btn-primary' : 'btn-soft-primary' }}">
            <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
        </a>
    @endforeach
</div>
