@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Add Cost', 'back' => route('maintainer.task.show', $task->id)])

    <div class="pwa-content">
        @if ($errors->any())
            <div class="pwa-alert">
                <i class="ri-error-warning-line"></i>
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        <form action="{{ route('maintainer.task.cost.store', $task->id) }}" method="POST" class="pwa-form">
            @csrf
            <div class="pwa-segment">
                <label><input type="radio" name="type" value="labor" @checked(old('type', 'labor') === 'labor')> <span>Labor</span></label>
                <label><input type="radio" name="type" value="material" @checked(old('type') === 'material')> <span>Material</span></label>
                <label><input type="radio" name="type" value="other" @checked(old('type') === 'other')> <span>Other</span></label>
            </div>
            <div class="pwa-field" data-cost-field="labor"><label>Worker Name <b>*</b></label><input type="text" name="worker" value="{{ old('worker') }}" placeholder="Ramesh" required></div>
            <div class="pwa-field" data-cost-field="labor material other"><label>Description <b>*</b></label><input type="text" name="label" value="{{ old('label') }}" placeholder="AC Technician / Paint / Transport" required></div>
            <div class="pwa-field" data-cost-field="labor"><label>Labor Hours <b>*</b></label><input type="number" step="0.01" min="0.01" inputmode="decimal" name="hours" value="{{ old('hours') }}" placeholder="2.5" required></div>
            <div class="pwa-field" data-cost-field="labor"><label>Labor Rate (AED) <b>*</b></label><input type="number" step="0.01" min="0.01" inputmode="decimal" name="rate" value="{{ old('rate') }}" placeholder="60.00" required></div>
            <div class="pwa-field" data-cost-field="material"><label>Material Quantity <b>*</b></label><input type="number" step="0.01" min="0.01" inputmode="decimal" name="quantity" value="{{ old('quantity') }}" placeholder="1" required></div>
            <div class="pwa-field" data-cost-field="material"><label>Material Unit Price <b>*</b></label><input type="number" step="0.01" min="0.01" inputmode="decimal" name="unit_price" value="{{ old('unit_price') }}" placeholder="0.00" required></div>
            <div class="pwa-field" data-cost-field="other"><label>Other Amount <b>*</b></label><input type="number" step="0.01" min="0.01" inputmode="decimal" name="amount" value="{{ old('amount') }}" placeholder="0.00" required></div>
            <button class="pwa-primary-button purple" type="submit" data-cost-submit>Save Labor</button>
        </form>

        <section class="pwa-section">
            <h3>Cost List</h3>
            @forelse($task->costItems as $item)
                <div class="pwa-cost-row">
                    <div><strong>{{ $item->label }}</strong><small>{{ ucfirst($item->type) }} • AED {{ number_format((float) $item->amount, 2) }}</small></div>
                    <span>AED {{ number_format((float) $item->amount, 2) }}</span>
                </div>
            @empty
                <p>No costs added yet.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
