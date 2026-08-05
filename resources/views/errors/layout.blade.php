@php
    $statusCode = $statusCode ?? 500;
    $title = $title ?? 'Something went wrong';
    $message = $message ?? 'There is an issue. Please contact your IT expert.';
    $reference = now()->format('Ymd-His');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $statusCode }} - {{ config('app.name', 'Holiday Homes Management System') }}</title>
    <style>
        :root {
            --ink: #111827;
            --muted: #667085;
            --line: #e5e7eb;
            --primary: #5b3df5;
            --soft: #f6f8fb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--soft);
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            padding: 24px;
        }
        .error-shell {
            width: min(760px, 100%);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 24px 60px rgba(16, 24, 40, .10);
            overflow: hidden;
        }
        .error-top {
            padding: 28px 32px;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand { font-weight: 800; font-size: 18px; }
        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 58px;
            height: 36px;
            border-radius: 999px;
            background: #eef2ff;
            color: var(--primary);
            font-weight: 800;
        }
        .error-body { padding: 42px 32px 34px; }
        h1 {
            margin: 0 0 12px;
            font-size: clamp(28px, 5vw, 44px);
            line-height: 1.05;
        }
        p {
            margin: 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }
        .btn {
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 11px 16px;
            color: var(--ink);
            text-decoration: none;
            font-weight: 700;
            background: #fff;
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .error-foot {
            padding: 18px 32px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 13px;
            background: #fbfcff;
        }
    </style>
</head>
<body>
    <main class="error-shell">
        <div class="error-top">
            <div class="brand">{{ config('app.name', 'Holiday Homes Management System') }}</div>
            <div class="status">{{ $statusCode }}</div>
        </div>
        <section class="error-body">
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/') }}">Go Home</a>
                <a class="btn" href="javascript:history.back()">Go Back</a>
            </div>
        </section>
        <div class="error-foot">
            Reference: {{ $reference }}. Please share this time with your IT support team.
        </div>
    </main>
</body>
</html>
