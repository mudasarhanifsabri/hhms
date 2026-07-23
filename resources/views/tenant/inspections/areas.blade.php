@extends('layouts.tenant-pwa', ['title' => 'Select Areas'])

@section('content')
<div class="tenant-screen">
    @include('tenant.partials.header', ['title' => 'Select Areas', 'back' => route('tenant.booking.show', $inspection->booking_id)])
    <main class="tenant-content">
        <section class="tenant-section">
            <h3>Select areas to inspect</h3>
            <p>You can inspect all areas or choose specific rooms.</p>
            <form action="{{ route('tenant.inspection.areas.store', $inspection->id) }}" method="POST" class="tenant-form">
                @csrf
                <div class="tenant-area-list">
                    @foreach($areas as $area => $items)
                        <label>
                            <input type="checkbox" name="areas[]" value="{{ $area }}" @checked(in_array($area, $inspection->selected_areas ?: []))>
                            <span>{{ $area }}</span>
                            <em>{{ count($items) }}</em>
                        </label>
                    @endforeach
                </div>
                <button class="tenant-primary" type="submit">Continue</button>
            </form>
        </section>
    </main>
</div>
@endsection
