@php
    $unitNo = $property->name;
    $community = trim(($building->building_name ?? '') . ' ' . ($building->address ?? ''));
    $propertyType = $property->bedrooms ? $property->bedrooms . ' Bedroom' : ($property->category ?? 'Unit');
    $startDate = $document->sent_at ? $document->sent_at->format('d/m/Y') : now()->format('d/m/Y');
    $endDate = $document->expires_at ? $document->expires_at->format('d/m/Y') : now()->addYear()->format('d/m/Y');
    $logoPath = public_path('assets/images/pattern-contract-logo.png');
    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    $managementTerms = [
        [
            'title' => 'Definition of Terms and Conditions',
            'items' => [
                'Company means Pattern Vacation Homes Rental LLC, holder of Trade License No. 1123804, registered and operating in Dubai, United Arab Emirates.',
                'Owner means the individual or entity holding legal title to the property described in this contract.',
                'Property means the unit described in the property details section and operated as a holiday home under applicable Dubai rules.',
                'Effective Date means the sending date of this agreement unless another date is confirmed in writing by both parties.',
            ],
        ],
        [
            'title' => 'Obligations of the Owner',
            'items' => [
                'The Owner authorizes the Company to manage, market, lease, and operate the Property as a holiday home.',
                'The Owner shall hand over the Property vacant, accessible, furnished where agreed, and ready for holiday home operation.',
                'The Owner shall provide all ownership, identity, permit, utility, community, access, and bank documents required for operation.',
                'The Owner shall keep ownership and utility records valid and notify the Company of any legal, building, or authority restriction affecting the Property.',
                'The Owner shall bear bank charges, currency exchange differences, utility dues, service charges, authority penalties, or owner-side expenses unless agreed otherwise in writing.',
            ],
        ],
        [
            'title' => 'Obligations of the Company',
            'items' => [
                'The Company shall manage the Property for short-term rental operations, guest coordination, listing support, housekeeping coordination, and revenue reporting.',
                'The Company shall coordinate check-in, check-out, guest communication, cleaning, and maintenance follow-up through its authorized staff and systems.',
                'The Company shall maintain owner statements showing booking income, deductions, expenses, management fees, and payout balances.',
                'The Company may coordinate urgent maintenance without prior approval where delay may affect guest safety, property condition, or active bookings.',
                'Maintenance charges may be processed without prior owner approval when the estimated cost does not exceed AED 500, unless the parties agree to a different approval limit.',
            ],
        ],
        [
            'title' => 'Financial Obligations',
            'items' => [
                'The Owner shall pay the agreed management fee to the Company from rental income or as otherwise agreed in writing.',
                'Furniture, startup, DTCM, licensing, compliance, photography, onboarding, and other setup fees shall be charged as stated in this agreement or approved separately.',
                'VAT shall be applied where required by UAE law and shown separately in the financial section of this agreement or related invoices.',
                'Net owner payout shall be calculated after deducting approved expenses, management fee, refunds, penalties, payment gateway charges, and other agreed dues.',
            ],
        ],
        [
            'title' => 'Owner Use, Bookings, and Operations',
            'items' => [
                'Owner personal use of the Property must be requested in advance and is subject to confirmed guest bookings and operational availability.',
                'Existing confirmed bookings must be honored unless cancellation is approved by the Company and any penalties are settled.',
                'The Company may block dates required for maintenance, cleaning, inspection, authority compliance, or operational readiness.',
            ],
        ],
        [
            'title' => 'Termination and Renewal',
            'items' => [
                'The initial term of this agreement is twelve months from the Effective Date unless renewed, extended, or terminated in writing.',
                'Either party may terminate this agreement by giving thirty days written notice to the other party.',
                'Before termination, both parties shall settle outstanding bookings, owner payouts, expenses, guest liabilities, penalties, and authority-related obligations.',
                'This agreement may be renewed by written confirmation, updated contract, or continued operation accepted by both parties.',
            ],
        ],
        [
            'title' => 'Applicable Law and Jurisdiction',
            'items' => [
                'This agreement shall be governed by the laws of Dubai and the federal laws of the United Arab Emirates.',
                'Any dispute shall first be handled through good-faith discussion between the parties. If unresolved, it shall be referred to the competent courts or authorities in Dubai.',
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }} - {{ $document->reference_no }}</title>
    <style>
        @page { size: A4; margin: 22px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1c2b3a; font-size: 12px; line-height: 1.55; }
        .page { max-width: 900px; margin: 0 auto; background: #fff; }
        .topbar { display: table; width: 100%; border-bottom: 2px solid #8a6f2b; padding-bottom: 10px; margin-bottom: 18px; }
        .brand-wrap { display: table-cell; width: 58%; vertical-align: middle; }
        .brand-logo { width: 270px; max-height: 70px; object-fit: contain; }
        .brand { font-weight: 700; font-size: 18px; letter-spacing: 2px; }
        .sub { color: #8a6f2b; font-size: 10px; letter-spacing: 2px; }
        .ref { display: table-cell; width: 42%; vertical-align: middle; text-align: right; color: #5b6b7a; }
        h1 { text-align: center; font-size: 19px; margin: 18px 0; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { border: 1px solid #c9d2d9; padding: 8px; vertical-align: top; }
        th { background: #e5e6e2; text-align: left; }
        .section { background: #f1f2f0; border: 1px solid #c9d2d9; padding: 7px 10px; font-weight: 700; margin-top: 14px; }
        .signature { margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .sigbox { border: 1px solid #c9d2d9; min-height: 120px; padding: 10px; }
        .sigbox img { max-width: 220px; max-height: 80px; display: block; margin-top: 8px; }
        .amount { text-align: right; font-weight: 700; }
        .terms { margin-top: 14px; }
        .term-title { background: #f1f2f0; border: 1px solid #c9d2d9; padding: 7px 10px; font-weight: 700; page-break-after: avoid; }
        .term-list { margin: 0 0 10px; padding: 8px 14px 8px 28px; border: 1px solid #c9d2d9; border-top: 0; }
        .term-list li { margin-bottom: 5px; }
        .page-break { page-break-before: always; }
        .footer { border-top: 2px solid #8a6f2b; margin-top: 28px; padding-top: 8px; color: #5b6b7a; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div class="brand-wrap">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" class="brand-logo" alt="Pattern Vacation Homes Rental">
            @else
                <div class="brand">PATTERN</div>
                <div class="sub">VACATION HOMES RENTAL LLC</div>
            @endif
        </div>
        <div class="ref">
            <div>Ref No. {{ $document->reference_no }}</div>
            <div>Date: {{ $startDate }}</div>
            <div>Valid Until: {{ $endDate }}</div>
        </div>
    </div>

    <h1>{{ $document->title }}</h1>

    @if($document->type === 'noc')
        <p>I, <strong>{{ $landlord->name }}</strong>, holding EID/Passport No. <strong>{{ $landlord->eid_passport_no ?? 'N/A' }}</strong>, being the rightful owner of the apartment listed below, confirm that I have no objection to Pattern Vacation Homes Rental LLC, holder of Trade License No. 1123804, to manage and operate my property on my behalf.</p>
        <p>This includes short-term rental management, guest handling, cleaning, maintenance coordination, leasing, hospitality services, and registration requirements.</p>
    @elseif($document->type === 'management_letter')
        <p>In my capacity as the owner of the property mentioned below, I authorize Pattern Vacation Homes Rental LLC License No. 1123804 to manage the property in accordance with Dubai Tourism and Commerce Marketing requirements.</p>
        <p>Authorization period: <strong>{{ $startDate }}</strong> until <strong>{{ $endDate }}</strong>.</p>
    @else
        <p>This contract constitutes an agreement to operate the property as a holiday home between Pattern Vacation Homes Rental LLC and the owner named below.</p>
        <p>The initial term of this agreement is twelve months from the sending date, unless renewed or terminated according to the agreed conditions.</p>
    @endif

    <div class="section">Owner Details</div>
    <table>
        <tr><th>Name</th><td>{{ $landlord->name }}</td><th>Email</th><td>{{ $landlord->email }}</td></tr>
        <tr><th>Phone</th><td>{{ $landlord->phone ?? 'N/A' }}</td><th>EID/Passport</th><td>{{ $landlord->eid_passport_no ?? 'N/A' }}</td></tr>
        <tr><th>Address</th><td colspan="3">{{ $landlord->address ?? 'N/A' }}</td></tr>
    </table>

    <div class="section">Property Details</div>
    <table>
        <tr><th>Property Name</th><td>{{ $property->name }}</td><th>Unit Type</th><td>{{ $propertyType }}</td></tr>
        <tr><th>Unit No.</th><td>{{ $unitNo }}</td><th>Floor No.</th><td>{{ $property->floor ?? 'N/A' }}</td></tr>
        <tr><th>Community</th><td>{{ $community ?: 'N/A' }}</td><th>DEWA Account No.</th><td>{{ $property->electricity_account_no ?? 'N/A' }}</td></tr>
        <tr><th>DTCM Permit No.</th><td>{{ $property->dtcm_permit_no ?? 'N/A' }}</td><th>DTCM Expiry</th><td>{{ $property->dtcm_permit_expiry?->format('d/m/Y') ?? 'N/A' }}</td></tr>
    </table>

    @if($document->type === 'management_contract')
        <div class="section">Furniture, Startup / DTCM Fee and VAT</div>
        <table>
            <tr><td>Furniture Supply & Installation</td><td class="amount">{{ number_format((float) $document->furniture_amount, 2) }} AED</td></tr>
            <tr><td>Startup / DTCM Fee</td><td class="amount">{{ number_format((float) $document->startup_dtcm_fee, 2) }} AED</td></tr>
            <tr><td>VAT 5% on Furniture Only</td><td class="amount">{{ number_format((float) $document->vat_amount, 2) }} AED</td></tr>
            <tr><th>Grand Total</th><th class="amount">{{ number_format((float) $document->total_amount, 2) }} AED</th></tr>
            <tr><td>Management Fee</td><td class="amount">{{ $property->management_fee ? number_format((float) $property->management_fee, 2) . ' AED' : 'As agreed' }}</td></tr>
        </table>

        <div class="terms">
            <div class="section">Terms and Conditions</div>
            @foreach($managementTerms as $term)
                <div class="term-title">{{ $term['title'] }}</div>
                <ol class="term-list">
                    @foreach($term['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            @endforeach
        </div>
    @endif

    <div class="signature">
        <div class="sigbox">
            <strong>Pattern Vacation Homes Rental LLC</strong>
            <p>Authorized Signature</p>
            <p>Signature on file</p>
        </div>
        <div class="sigbox">
            <strong>The Owner</strong>
            <p>Name: {{ $signedByName ?: $landlord->name }}</p>
            <p>Date: {{ $document->signed_at?->format('d/m/Y H:i') ?? 'Pending signature' }}</p>
            @if($signatureData)
                <img src="{{ $signatureData }}" alt="Owner Signature">
            @else
                <p>Signature pending</p>
            @endif
        </div>
    </div>

    <div class="footer">
        Office 413, P.O. Box 1327, Al Attar Business Centre, Al-Barsha, Dubai, UAE | customerservice@pattern.ae | www.pattern.ae
    </div>
</div>
</body>
</html>
