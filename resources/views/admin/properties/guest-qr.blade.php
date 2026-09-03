<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Guest App QR — {{ $property->name }}</title>
<style>body{font:18px system-ui,sans-serif;color:#102b4d;background:#f3f5fa;margin:0;padding:30px}.poster{max-width:600px;margin:auto;background:white;border:1px solid #ddd;border-radius:20px;padding:32px;text-align:center}.qr svg{max-width:100%;height:auto}a{color:#154ec1;overflow-wrap:anywhere}button{padding:12px 24px;margin:12px;font:inherit;cursor:pointer}.hint{font-size:14px;color:#667085}@media print{body{background:white;padding:0}.no-print{display:none}.poster{border:0}}</style></head><body>
<main class="poster"><h1>Welcome to Your Guest App</h1><h2>{{ $property->building?->building_name }} — {{ $property->name }}</h2>
<div class="qr">{!! $qr !!}</div><h2>Scan to sign in or activate</h2>
<p>Report maintenance problems, track request status, and manage your stay.</p>
<p>First visit? Have your booking reference and booking email ready.</p>
<p><a href="{{ $url }}">{{ $url }}</a></p><p class="hint">For emergencies, contact emergency services and management directly.</p>
<div class="no-print"><button onclick="window.print()">Print / Save as PDF</button><p class="hint">Place this code inside the unit. Account access still requires login or the email password setup link.</p><a href="{{ route('admin.property.show', $property) }}">Back to Unit</a></div>
</main></body></html>
