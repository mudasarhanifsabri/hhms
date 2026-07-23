@php
    $unitNo = $property->name;
    $community = trim(($building->building_name ?? '') . ' ' . ($building->address ?? ''));
    $propertyType = $property->bedrooms ? $property->bedrooms . ' Bedroom' : ($property->category ?? 'Unit');
    $startDate = $document->sent_at ? $document->sent_at->format('d/m/Y') : now()->format('d/m/Y');
    $endDate = $document->expires_at ? $document->expires_at->format('d/m/Y') : now()->addYear()->format('d/m/Y');
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
        .topbar { display: flex; justify-content: space-between; border-bottom: 2px solid #8a6f2b; padding-bottom: 10px; margin-bottom: 18px; }
        .brand { font-weight: 700; font-size: 18px; letter-spacing: 2px; }
        .sub { color: #8a6f2b; font-size: 10px; letter-spacing: 2px; }
        .ref { text-align: right; color: #5b6b7a; }
        h1 { text-align: center; font-size: 19px; margin: 18px 0; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { border: 1px solid #c9d2d9; padding: 8px; vertical-align: top; }
        th { background: #e5e6e2; text-align: left; }
        .section { background: #f1f2f0; border: 1px solid #c9d2d9; padding: 7px 10px; font-weight: 700; margin-top: 14px; }
        .signature { margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .sigbox { border: 1px solid #c9d2d9; min-height: 120px; padding: 10px; }
        .sigbox img { max-width: 220px; max-height: 80px; display: block; margin-top: 8px; }
        .amount { text-align: right; font-weight: 700; }
        .footer { border-top: 2px solid #8a6f2b; margin-top: 28px; padding-top: 8px; color: #5b6b7a; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div>
            <div class="brand">PATTERN</div>
            <div class="sub">VACATION HOMES RENTAL LLC</div>
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
