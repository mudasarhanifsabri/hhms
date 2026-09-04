@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Inspection', 'back' => route('maintainer.task.show', $task->id)])

    <div class="pwa-content">
        <div class="pwa-detail-head">
            <span class="pwa-chip purple">{{ $inspection->inspection_number }}</span>
            <span class="pwa-status-pill">{{ $inspection->type_label }}</span>
        </div>
        <h2 class="pwa-title">{{ $task->title }}</h2>
        <p class="pwa-subtitle">{{ $inspection->property?->building?->name ?? $task->booking?->property?->building?->name ?? 'Property' }} • {{ $inspection->property?->name ?? $task->booking?->property?->name ?? 'Unit' }}</p>

        <form action="{{ route('maintainer.task.inspection.submit', $task->id) }}" method="POST" enctype="multipart/form-data" class="pwa-form">
            @csrf
            @if(count($inventoryRows))
            <section class="pwa-section"><h3>Inventory counts</h3><p>Count all items present. Damaged is included in Found. Office approval updates stock.</p>
            @foreach($inventoryRows as $row)
            <div class="pwa-inspection-item"><strong>{{ $row['room'] }} — {{ $row['name'] }}</strong><p>Required {{ $row['required'] }} · Previous count {{ $row['before'] }}</p>
                <div class="row g-2"><div class="col-6"><label>Found<input class="form-control" type="number" min="0" max="100000" name="inventory[{{ $row['id'] }}][found]" value="{{ old('inventory.'.$row['id'].'.found') }}" required></label></div>
                <div class="col-6"><label>Damaged<input class="form-control" type="number" min="0" name="inventory[{{ $row['id'] }}][damaged]" value="{{ old('inventory.'.$row['id'].'.damaged') }}" required></label></div></div>
                <label>Evidence / notes<textarea class="form-control" name="inventory[{{ $row['id'] }}][notes]" placeholder="Describe damage; attach photos under the room checklist below">{{ old('inventory.'.$row['id'].'.notes') }}</textarea></label>
            </div>
            @endforeach</section>
            @endif
            @foreach($inspection->items->groupBy('area') as $area => $items)
                <section class="pwa-section pwa-inspection-area">
                    <h3>{{ $area }}</h3>
                    @foreach($items as $item)
                        <div class="pwa-inspection-item">
                            <strong>{{ $item->item }}</strong>
                            <div class="pwa-segment pwa-condition-segment">
                                @foreach(['good' => 'Good', 'issue' => 'Issue', 'na' => 'N/A'] as $key => $label)
                                    <label>
                                        <input type="radio" name="items[{{ $item->id }}][condition]" value="{{ $key }}" @checked($item->condition === $key) required>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="pwa-field">
                                <label>Remark</label>
                                <textarea name="items[{{ $item->id }}][comment]" rows="2" placeholder="Add issue details if needed.">{{ $item->comment }}</textarea>
                            </div>
                            @include('maintainer.partials.photo-picker', [
                                'name' => 'pictures[' . $item->id . '][]',
                                'label' => 'Photos'
                            ])
                        </div>
                    @endforeach
                </section>
            @endforeach

            <div class="pwa-field">
                <label for="notes">Final Notes</label>
                <textarea id="notes" name="notes" rows="4" placeholder="Overall inspection notes.">{{ $inspection->notes }}</textarea>
            </div>

            <button class="pwa-primary-button green" type="submit">Submit Inspection</button>
        </form>
    </div>
</div>
@endsection
