@extends('layouts.tenant-pwa', ['title' => 'Review Inspection'])

@section('content')
<div class="tenant-screen">
    @include('tenant.partials.header', ['title' => 'Review Inspection', 'back' => route('tenant.inspection.areas', $inspection->id)])
    <main class="tenant-content">
        <section class="tenant-summary-grid">
            <div><strong>{{ $inspection->total_items }}</strong><span>Total Items</span></div>
            <div class="good"><strong>{{ $inspection->good_items }}</strong><span>Good</span></div>
            <div class="issue"><strong>{{ $inspection->issue_items }}</strong><span>Issues</span></div>
        </section>
        <section class="tenant-section">
            <h3>Issues Found</h3>
            @forelse($inspection->items->where('condition', 'issue') as $item)
                <div class="tenant-issue-row">
                    <div><strong>{{ $item->area }} - {{ $item->item }}</strong><p>{{ $item->comment ?: 'Marked as issue.' }}</p></div>
                    <span>{{ count((array) $item->pictures) }}</span>
                </div>
            @empty
                <p>No issues marked.</p>
            @endforelse
        </section>
        <a href="{{ route('tenant.inspection.notes', $inspection->id) }}" class="tenant-primary">Add Notes</a>
    </main>
</div>
@endsection
