<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign {{ $document->title }}</title>
    <link href="{{ asset('assets/css/vendor.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet">
    <style>
        body { background: #f4f6f8; }
        .sign-wrap { max-width: 1100px; margin: 24px auto; padding: 0 12px; }
        .document-frame { background: #fff; border: 1px solid #dde3ea; min-height: 560px; padding: 18px; overflow: auto; }
        canvas { width: 100%; height: 180px; border: 1px dashed #98a6ad; background: #fff; touch-action: none; }
    </style>
</head>
<body>
<div class="sign-wrap">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">{{ $document->title }} - {{ $document->reference_no }}</h4>
                </div>
                <div class="card-body">
                    <div class="document-frame">
                        {!! $document->signed_html ?: $document->unsigned_html !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Owner Signature</h4>
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> {{ $document->status_label }}</p>
                    <p><strong>Expires:</strong> {{ $document->expires_at?->format('d M Y') }}</p>

                    @if($document->signed_at)
                        <div class="alert alert-success">Signed on {{ $document->signed_at->format('d M Y H:i') }}</div>
                        @if($document->signed_document_path)
                            <a href="{{ route('owner-documents.pdf', $document->signing_token) }}" class="btn btn-dark w-100" target="_blank">Open Signed PDF</a>
                        @endif
                    @else
                        <form action="{{ route('owner-documents.sign', $document->signing_token) }}" method="POST" id="signatureForm">
                            @csrf
                            <div class="mb-3">
                                <label for="signed_by_name" class="form-label">Your Name</label>
                                <input type="text" id="signed_by_name" name="signed_by_name" class="form-control" value="{{ old('signed_by_name', $document->landlord->name) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Draw Signature</label>
                                <canvas id="signatureCanvas"></canvas>
                                <input type="hidden" name="signature_data" id="signatureData">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light w-50" id="clearSignature">Clear</button>
                                <button type="submit" class="btn btn-primary w-50">Sign</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const canvas = document.getElementById('signatureCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const dataInput = document.getElementById('signatureData');
        let drawing = false;
        let hasInk = false;

        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * window.devicePixelRatio;
            canvas.height = rect.height * window.devicePixelRatio;
            ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#1c2b3a';
        }

        function point(event) {
            const rect = canvas.getBoundingClientRect();
            const source = event.touches ? event.touches[0] : event;
            return { x: source.clientX - rect.left, y: source.clientY - rect.top };
        }

        function start(event) {
            drawing = true;
            hasInk = true;
            const p = point(event);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            event.preventDefault();
        }

        function move(event) {
            if (!drawing) return;
            const p = point(event);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            event.preventDefault();
        }

        function end() {
            drawing = false;
            dataInput.value = canvas.toDataURL('image/png');
        }

        resizeCanvas();
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);
        document.getElementById('clearSignature').addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasInk = false;
            dataInput.value = '';
        });
        document.getElementById('signatureForm').addEventListener('submit', function (event) {
            if (!hasInk || !dataInput.value) {
                event.preventDefault();
                alert('Please draw your signature before signing.');
            }
        });
    }
</script>
</body>
</html>
