@extends('layouts.tenant-pwa', ['title' => 'Add Notes'])

@section('content')
<div class="tenant-screen">
    @include('tenant.partials.header', ['title' => 'Add Notes', 'back' => route('tenant.inspection.review', $inspection->id)])
    <main class="tenant-content">
        <form action="{{ route('tenant.inspection.submit', $inspection->id) }}" method="POST" class="tenant-form">
            @csrf
            <section class="tenant-section">
                <h3>Add Notes Optional</h3>
                <p>Share any additional comments or special notes.</p>
                <textarea name="notes" rows="7" placeholder="All good overall. Few issues marked for review.">{{ $inspection->notes }}</textarea>
            </section>
            <button class="tenant-primary" type="submit">Submit Inspection</button>
        </form>
    </main>
</div>
@endsection
