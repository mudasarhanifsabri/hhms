<section class="pwa-section"><h3>Task history</h3>
@if($task->completion_notes)<p>{{ $task->completion_notes }}</p>@endif
<div class="pwa-timeline">@forelse($task->activities as $activity)<div class="pwa-timeline-item"><span class="pwa-timeline-dot"><i class="ri-checkbox-circle-line"></i></span><div><strong>{{ $activity->action }}</strong><p>{{ $activity->comment }}</p><small>{{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at?->format('d M Y H:i') }}</small></div></div>@empty<p>No history recorded.</p>@endforelse</div></section>
@if($task->inspection?->status === 'submitted')
<details class="pwa-section"><summary>View submitted inspection · {{ $task->inspection->inspection_number }}</summary><p>{{ $task->inspection->notes }}</p>
@foreach($task->inspection->items->groupBy('area') as $area=>$items)<h4>{{ $area }}</h4>@foreach($items as $item)<div class="pwa-inspection-item"><strong>{{ $item->item }}</strong> · {{ strtoupper($item->condition) }}<p>{{ $item->comment }}</p><div class="d-flex flex-wrap gap-2">@foreach((array)$item->pictures as $picture)<a href="{{ \App\Support\MediaStorage::url($picture) }}" target="_blank" rel="noopener"><img src="{{ \App\Support\MediaStorage::url($picture) }}" alt="{{ $item->item }} evidence" width="72" height="72" style="object-fit:cover;border-radius:8px"></a>@endforeach</div></div>@endforeach @endforeach
</details>
@elseif($task->isInspectionTask())
<p class="pwa-section">No submitted inspection is attached. Ask the office for a new inspection request if this task was completed by mistake.</p>
@endif
@if($task->costItems->isNotEmpty())<details class="pwa-section"><summary>Recorded costs · AED {{ number_format($task->total_cost,2) }}</summary>@foreach($task->costItems as $cost)<p>{{ $cost->label }} — AED {{ number_format($cost->amount,2) }}</p>@endforeach</details>@endif
