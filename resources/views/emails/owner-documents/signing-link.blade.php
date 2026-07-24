<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.55">
    <h2 style="margin:0 0 12px">Signature Required</h2>
    <p>Please review and sign the following document:</p>
    <p><strong>{{ $document->title }}</strong><br>Reference: {{ $document->reference_no }}</p>
    <p>
        <a href="{{ $signingUrl }}" style="display:inline-block;background:#0f3b66;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px">
            Open Signing Page
        </a>
    </p>
    <p>
        Preview PDF: <a href="{{ $pdfUrl }}">{{ $pdfUrl }}</a>
    </p>
    <p style="font-size:12px;color:#6b7280">Pattern Vacation Homes Rental LLC</p>
</div>
