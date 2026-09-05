<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#071d3b">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ config('app.name', 'PATTERN') }}</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/pwa-icon-192.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.min.css') }}">
    <style>
        :root{--navy:#071d3b;--navy2:#0d3262;--purple:#6246ea;--gold:#cfa85d;--ink:#10182b;--muted:#69758b;--line:#e4e8f0;--danger:#df3d4f;--success:#168451}*{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Inter,"Segoe UI",Arial,sans-serif;color:var(--ink);background:#eef2f8}body{min-height:100vh;min-height:100dvh}.auth-shell{min-height:100vh;min-height:100dvh;display:grid;grid-template-columns:minmax(330px,480px) minmax(0,1fr);background:#fff}.auth-brand{position:relative;overflow:hidden;padding:48px;display:flex;flex-direction:column;justify-content:space-between;color:#fff;background:linear-gradient(155deg,var(--navy) 0%,#09284f 60%,#174275 100%)}.auth-brand:before,.auth-brand:after{content:"";position:absolute;border-radius:50%;border:1px solid rgba(255,255,255,.09)}.auth-brand:before{width:420px;height:420px;right:-260px;top:-160px}.auth-brand:after{width:520px;height:520px;left:-300px;bottom:-330px}.brand-logo{position:relative;z-index:1;width:min(310px,90%);filter:invert(1);mix-blend-mode:screen}.brand-copy{position:relative;z-index:1}.brand-copy h2{font-size:34px;line-height:1.12;margin:0 0 14px}.brand-copy p{max-width:330px;margin:0;color:#d9e2ef;line-height:1.65;font-size:15px}.role-row{position:relative;z-index:1;display:flex;gap:8px;flex-wrap:wrap}.role-row span{padding:7px 11px;border:1px solid rgba(255,255,255,.18);border-radius:99px;color:#dce6f4;font-size:11px}.auth-main{display:flex;align-items:center;justify-content:center;padding:38px}.auth-card{width:min(440px,100%)}.mobile-mark{display:none;text-align:center}.mobile-mark img{width:230px;max-width:76%}.eyebrow{color:var(--purple);font-size:11px;font-weight:800;letter-spacing:1.3px;text-transform:uppercase;margin-bottom:9px}.auth-card h1{font-size:31px;line-height:1.15;margin:0 0 9px}.lead{color:var(--muted);font-size:14px;line-height:1.6;margin:0 0 27px}.status{border-radius:12px;background:#e9f8ef;color:var(--success);padding:12px 14px;font-size:13px;margin-bottom:17px}.field{margin-bottom:17px}.field-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px}.field label{font-size:13px;font-weight:700}.field-link,.back-link{color:var(--purple);font-size:12px;font-weight:700;text-decoration:none}.control-wrap{position:relative}.control{width:100%;height:52px;border:1px solid var(--line);border-radius:12px;padding:0 46px 0 15px;background:#f9fafc;color:var(--ink);font-size:15px;outline:none;transition:.2s}.control:focus{background:#fff;border-color:var(--purple);box-shadow:0 0 0 4px rgba(98,70,234,.1)}.control[readonly]{color:#647087;background:#f1f3f7}.control-icon{position:absolute;right:15px;top:50%;transform:translateY(-50%);color:#7a8598;border:0;background:transparent;padding:4px;font-size:19px;cursor:pointer}.error{list-style:none;padding:0;margin:6px 0 0;color:var(--danger);font-size:12px}.remember{display:flex;align-items:center;gap:9px;color:#4d586c;font-size:13px;margin:4px 0 21px}.remember input{width:18px;height:18px;accent-color:var(--purple)}.primary{width:100%;height:53px;border:0;border-radius:12px;background:linear-gradient(135deg,var(--navy2),var(--purple));color:#fff;font-size:14px;font-weight:800;box-shadow:0 10px 24px rgba(41,42,125,.2);cursor:pointer}.primary:active{transform:translateY(1px)}.secure-note{display:flex;justify-content:center;align-items:center;gap:6px;color:#8993a4;font-size:11px;margin-top:20px}.page-back{display:inline-flex;align-items:center;gap:6px;margin-top:22px}.password-meter{display:flex;gap:5px;margin:8px 0 0}.password-meter span{height:3px;flex:1;border-radius:9px;background:#e3e6ed}.auth-footer{color:#9aa3b1;font-size:11px;text-align:center;margin-top:34px}
        @media(max-width:760px){body{background:#fff}.auth-shell{display:block;min-height:100dvh}.auth-brand{display:none}.auth-main{min-height:100dvh;align-items:flex-start;padding:max(28px,env(safe-area-inset-top)) 22px max(24px,env(safe-area-inset-bottom))}.auth-card{margin:auto}.mobile-mark{display:block;margin:8px 0 43px}.eyebrow{text-align:left}.auth-card h1{font-size:27px}.lead{margin-bottom:25px}.control,.primary{height:54px}.auth-footer{margin-top:30px}}@media(max-width:380px){.auth-main{padding-left:17px;padding-right:17px}.mobile-mark{margin-bottom:34px}.auth-card h1{font-size:25px}}
    </style>
</head>
<body>
<main class="auth-shell">
    <aside class="auth-brand">
        <img class="brand-logo" src="{{ asset('assets/images/pattern-bilingual-logo.png') }}" alt="PATTERN Vacation Homes Rental">
        <div class="brand-copy"><h2>Your stay.<br>Your property.<br>One secure app.</h2><p>Access bookings, statements, inspections, maintenance and documents from anywhere.</p></div>
        <div class="role-row"><span>Owner Portal</span><span>Guest App</span><span>Maintenance App</span></div>
    </aside>
    <section class="auth-main"><div class="auth-card">
        <div class="mobile-mark"><img src="{{ asset('assets/images/pattern-bilingual-logo.png') }}" alt="PATTERN Vacation Homes Rental"></div>
        @yield('content')
        <div class="auth-footer">Secure access by PATTERN Vacation Homes Rental</div>
    </div></section>
</main>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function(button){button.addEventListener('click',function(){var input=document.getElementById(button.dataset.passwordToggle);if(!input)return;var show=input.type==='password';input.type=show?'text':'password';button.setAttribute('aria-label',show?'Hide password':'Show password');button.innerHTML=show?'&#128584;':'&#128065;';});});
</script>
</body></html>
