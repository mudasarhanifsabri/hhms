@php
    $logo = public_path('assets/images/logo-dark.png');
    $ownerSigned = $cancellation->owner_signature;
    $companySigned = $cancellation->company_signature;
@endphp
<div class="cancellation-document">
    <div class="head">
        @if(file_exists($logo))
            <img src="{{ $logo }}" alt="Pattern Vacation Homes Rental">
        @else
            <strong>PATTERN VACATION HOMES RENTAL</strong>
        @endif
        <h1>Management Agreement Cancellation</h1>
        <h2>and Owner No Claims Confirmation</h2>
    </div>
    <table class="meta">
        <tr><th>Reference</th><td>{{ $cancellation->reference_no }}</td></tr>
        <tr><th>Date</th><td>{{ $cancellation->letter_date->format('d F Y') }}</td></tr>
        <tr><th>To</th><td>{{ $cancellation->owner_name }}</td></tr>
        <tr><th>Property</th><td>{{ $cancellation->property_description }}</td></tr>
        <tr><th>Effective cancellation date</th><td>{{ $cancellation->effective_date->format('d F Y') }}</td></tr>
    </table>
    <p>Dear Owner,</p>
    <p>This letter confirms the cancellation of the property management agreement between Pattern Vacation Homes Rental and yourself concerning the above property, effective on the cancellation date stated above.</p>
    <p>From the effective cancellation date, Pattern Vacation Homes Rental will cease managing and operating the property.</p>
    <div class="section">Owner's Confirmation</div>
    <p>I, <strong>{{ $cancellation->owner_name }}</strong>, confirm my agreement to the above cancellation. I acknowledge that all amounts due to me from Pattern Vacation Homes Rental in connection with the property management agreement have been fully settled.</p>
    <p>I confirm that I have no outstanding financial or other claims, demands, or requests against Pattern Vacation Homes Rental arising from the management of the above property up to the effective cancellation date, and I release the company from those claims.</p>
    <p>I have read and voluntarily accepted this confirmation.</p>
    <table class="sig"><tr>
        <td><strong>For Pattern Vacation Homes Rental</strong><div class="line">@if($companySigned)<img src="{{ $companySigned }}" class="sig-img" alt="Company signature">@endif</div><strong>{{ $cancellation->company_signed_name ?: $cancellation->company_signer_name }}</strong><br><span class="muted">{{ $cancellation->company_signed_at?->timezone('Asia/Dubai')->format('d M Y, h:i A').' GST' ?? 'Awaiting signature' }}</span></td>
        <td class="gap"></td>
        <td><strong>Property Owner</strong><div class="line">@if($ownerSigned)<img src="{{ $ownerSigned }}" class="sig-img" alt="Owner signature">@endif</div><strong>{{ $cancellation->owner_signed_name ?: $cancellation->owner_name }}</strong><br><span class="muted">{{ $cancellation->owner_signed_at?->timezone('Asia/Dubai')->format('d M Y, h:i A').' GST' ?? 'Awaiting signature' }}</span></td>
    </tr></table>
    <div class="foot">Electronically signed document · {{ $cancellation->reference_no }}@if($cancellation->document_hash) · SHA-256 {{ $cancellation->document_hash }}@endif</div>
</div>
