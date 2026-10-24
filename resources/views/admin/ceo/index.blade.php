@extends('layouts.app')

@section('content')
<style>
.ceo-portal{--ink:#24221f;--gold:#a8702a;--paper:#fff;--soft:#f7f4ef;--line:#ded8cf}.ceo-hero{background:linear-gradient(135deg,#171d25,#283747);border-radius:18px;color:#fff;padding:28px 30px;position:relative;overflow:hidden}.ceo-hero:after{content:"";position:absolute;width:260px;height:260px;border:55px solid rgba(255,255,255,.05);border-radius:50%;right:-70px;top:-100px}.ceo-kicker{color:#e0ad62;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.ceo-hero h1{font-size:34px;margin:7px 0}.ceo-hero p{color:#c9d0d7;margin:0}.ceo-actions{display:flex;gap:8px;position:absolute;right:28px;bottom:28px;z-index:2}.ceo-actions .btn{border-radius:10px}.ceo-filter{align-items:end;background:#fff;border:1px solid var(--line);border-radius:14px;display:flex;gap:12px;margin:16px 0;padding:14px}.ceo-filter label{font-size:12px;font-weight:700}.ceo-filter input{min-width:180px}.ceo-summary{display:grid;gap:14px;grid-template-columns:repeat(4,1fr);margin-bottom:18px}.ceo-summary article{background:#fff;border:1px solid var(--line);border-radius:14px;padding:17px}.ceo-summary small{color:#79736b;display:block}.ceo-summary strong{color:var(--ink);display:block;font-size:23px;margin-top:5px}.ceo-summary .positive{color:#168260}.ceo-summary .negative{color:#d34f43}.ceo-grid{display:grid;gap:18px;grid-template-columns:1fr 1fr}.ceo-card{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden}.ceo-card header{align-items:center;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;padding:16px 18px}.ceo-card h2{font-size:18px;margin:0}.ceo-card table{margin:0}.ceo-card th{background:#24221f!important;color:#fff!important;font-size:12px;text-transform:uppercase}.ceo-card td,.ceo-card th{padding:12px 14px}.ceo-wide{grid-column:1/-1}.ceo-issue{border-bottom:1px solid var(--line);display:grid;gap:14px;grid-template-columns:1.2fr 1.2fr .7fr .9fr;padding:16px 18px}.ceo-issue:last-child{border:0}.ceo-issue h3{font-size:15px;margin:0 0 4px}.ceo-issue p{color:#69635c;font-size:13px;margin:0}.ceo-label{color:#8b8175;font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.ceo-decision{background:var(--soft);border-radius:10px;padding:10px}.ceo-decision textarea{font-size:13px;min-height:72px}.ceo-empty{color:#877f75;padding:30px;text-align:center}.ceo-history{display:flex;gap:8px;overflow:auto}.ceo-history a{background:#f0ece6;border-radius:99px;color:#625b52;padding:6px 10px;text-decoration:none;white-space:nowrap}.ceo-history a.active{background:#24221f;color:#fff}@media(max-width:900px){.ceo-actions{margin-top:18px;position:static}.ceo-summary{grid-template-columns:1fr 1fr}.ceo-grid{grid-template-columns:1fr}.ceo-wide{grid-column:auto}.ceo-issue{grid-template-columns:1fr}.ceo-filter{align-items:stretch;flex-direction:column}}@media print{.main-nav,.topbar,.footer,.ceo-actions,.ceo-filter,.no-print{display:none!important}.page-content{margin:0!important}.container-fluid{padding:0!important}.ceo-hero{border-radius:0}.ceo-card,.ceo-summary article{break-inside:avoid}.ceo-portal{font-size:11px}}
</style>
<div class="ceo-portal">
    <section class="ceo-hero">
        <div class="ceo-kicker">Weekly Management Review</div>
        <h1>CEO overview</h1>
        <p>Week ending {{ $weekEnding->format('d F Y') }} · Prepared from live RMS records</p>
        <div class="ceo-actions"><button class="btn btn-light" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print / PDF</button><button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#issueModal"><i class="ri-add-line me-1"></i>Add issue</button></div>
    </section>

    <form class="ceo-filter no-print" method="GET">
        <div><label class="form-label">Week ending (Saturday)</label><input class="form-control" type="date" name="week_ending" value="{{ $weekEnding->toDateString() }}"></div>
        <button class="btn btn-dark">Open review</button>
        <div class="ceo-history ms-auto">
            @foreach($weeks as $pastWeek)<a class="{{ $pastWeek->toDateString()===$weekEnding->toDateString()?'active':'' }}" href="{{ route('admin.ceo.index',['week_ending'=>$pastWeek->toDateString()]) }}">{{ $pastWeek->format('d M') }}</a>@endforeach
        </div>
    </form>

    <div class="ceo-summary">
        <article><small>Operating result · week</small><strong class="{{ $week['result'] >= 0 ? 'positive':'negative' }}">AED {{ number_format($week['result'],2) }}</strong></article>
        <article><small>Cash received · week</small><strong>AED {{ number_format($week['cash'],2) }}</strong></article>
        <article><small>Overdue collections</small><strong class="negative">AED {{ number_format($overdue,2) }}</strong></article>
        <article><small>Occupancy</small><strong>{{ $totalUnits ? round($occupied/$totalUnits*100) : 0 }}%</strong></article>
    </div>

    <div class="ceo-grid">
        <section class="ceo-card"><header><h2>Financial position</h2><span class="ceo-kicker">AED</span></header><table class="table"><thead><tr><th>Metric</th><th>Week</th><th>Month to date</th></tr></thead><tbody>
            <tr><td>Income</td><td>{{ number_format($week['income'],2) }}</td><td>{{ number_format($month['income'],2) }}</td></tr>
            <tr><td>Expenses</td><td>{{ number_format($week['expenses'],2) }}</td><td>{{ number_format($month['expenses'],2) }}</td></tr>
            <tr><td><strong>Operating result</strong></td><td><strong>{{ number_format($week['result'],2) }}</strong></td><td><strong>{{ number_format($month['result'],2) }}</strong></td></tr>
            <tr><td>Cash received</td><td>{{ number_format($week['cash'],2) }}</td><td>{{ number_format($month['cash'],2) }}</td></tr>
            <tr><td>Overdue collections</td><td colspan="2" class="text-danger">{{ number_format($overdue,2) }}</td></tr>
        </tbody></table></section>
        <section class="ceo-card"><header><h2>Apartments and bookings</h2><span class="ceo-kicker">Live</span></header><table class="table"><thead><tr><th>Metric</th><th>Current</th><th>Change / action</th></tr></thead><tbody>
            <tr><td>Occupied / total</td><td><strong>{{ $occupied }} / {{ $totalUnits }}</strong></td><td>{{ $totalUnits ? round($occupied/$totalUnits*100) : 0 }}% occupancy</td></tr>
            <tr><td>Vacant and ready</td><td><strong>{{ $vacantReady }}</strong></td><td>Available now</td></tr>
            <tr><td>Leases ending in 30 days</td><td><strong>{{ $endingSoon }}</strong></td><td>Follow up / renew</td></tr>
            <tr><td>Bookings signed this week</td><td><strong>{{ $signed }}</strong></td><td>{{ $weekStart->format('d M') }}–{{ $weekEnding->format('d M') }}</td></tr>
            <tr><td>New / lost apartments</td><td><strong>{{ $newUnits }} / {{ $lostUnits }}</strong></td><td>Net {{ $newUnits-$lostUnits >= 0 ? '+':'' }}{{ $newUnits-$lostUnits }}</td></tr>
        </tbody></table></section>

        <section class="ceo-card ceo-wide"><header><h2>Issues and CEO decisions</h2><span class="badge bg-dark">{{ $issues->where('status','open')->count() }} open</span></header>
            @forelse($issues as $issue)
            <div class="ceo-issue"><div><div class="ceo-label">Issue and impact</div><h3>{{ $issue->issue }}</h3><p>{{ $issue->impact ?: 'No impact details added.' }}</p></div><div><div class="ceo-label">Team recommendation</div><p>{{ $issue->recommendation ?: 'Awaiting recommendation.' }}</p></div><div><div class="ceo-label">Owner / due date</div><h3>{{ $issue->owner_name ?: 'Unassigned' }}</h3><p>{{ $issue->due_date?->format('d M Y') ?: 'No due date' }}</p></div><div class="ceo-decision"><div class="ceo-label">CEO decision</div>
                @if($issue->decision)<p><strong>{{ ucfirst($issue->status) }}</strong><br>{{ $issue->decision }}</p>@else<form method="POST" action="{{ route('admin.ceo.issues.decide',$issue) }}">@csrf @method('PUT')<textarea class="form-control mb-2" name="decision" placeholder="Record decision…" required></textarea><input type="hidden" name="status" value="decided"><button class="btn btn-sm btn-dark w-100">Save decision</button></form>@endif
            </div></div>
            @empty<div class="ceo-empty">No issues recorded for this week. Add only items that need a decision.</div>@endforelse
        </section>
    </div>
</div>

<div class="modal fade" id="issueModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('admin.ceo.issues.store') }}">@csrf<input type="hidden" name="week_ending" value="{{ $weekEnding->toDateString() }}"><div class="modal-header"><div><h5 class="modal-title">Add management issue</h5><small class="text-muted">Week ending {{ $weekEnding->format('d M Y') }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-12"><label class="form-label">Issue</label><input class="form-control" name="issue" required></div><div class="col-md-6"><label class="form-label">Impact</label><textarea class="form-control" name="impact" rows="4"></textarea></div><div class="col-md-6"><label class="form-label">Team recommendation</label><textarea class="form-control" name="recommendation" rows="4"></textarea></div><div class="col-md-6"><label class="form-label">Action owner</label><input class="form-control" name="owner_name"></div><div class="col-md-6"><label class="form-label">Due date</label><input class="form-control" type="date" name="due_date"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-dark">Add to review</button></div></form></div></div>
@endsection
