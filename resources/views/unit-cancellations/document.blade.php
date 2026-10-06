@php
    $forPdf = true;
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:18mm 18mm 16mm}body{font-family:DejaVu Sans,Arial,sans-serif;color:#172033;font-size:11px;line-height:1.65}.head{text-align:center;border-bottom:3px solid #173b6c;padding-bottom:12px;margin-bottom:18px}.head img{width:190px}.head h1{font-size:20px;margin:8px 0 0;color:#173b6c}.head h2{font-size:14px;margin:0;color:#b3873f}.meta{width:100%;border-collapse:collapse;margin-bottom:18px}.meta th,.meta td{border:1px solid #d9e0ea;padding:7px;text-align:left}.meta th{width:29%;background:#f2f5f9;color:#31445f}.section{font-size:13px;font-weight:bold;color:#173b6c;border-bottom:1px solid #b3873f;margin:18px 0 8px;padding-bottom:3px}.sig{width:100%;margin-top:26px;border-collapse:collapse}.sig td{width:48%;vertical-align:top;border:1px solid #d9e0ea;padding:12px}.sig .gap{width:4%;border:0}.sig-img{height:58px;max-width:190px}.line{height:62px;border-bottom:1px solid #6b7280}.muted{color:#657187;font-size:9px}.foot{margin-top:20px;border-top:1px solid #d9e0ea;padding-top:7px;color:#677286;font-size:8px;text-align:center}
</style></head><body>
@include('unit-cancellations.partials.document-content')
</body></html>
