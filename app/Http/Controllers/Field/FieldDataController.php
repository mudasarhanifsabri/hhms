<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Maintainers\MaintainerController;
use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\BookingTask;
use App\Models\Property;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\UnitInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class FieldDataController extends Controller
{
    public function bootstrap(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'maintainer'], true), 403);

        return response()->json(['user' => $user->only(['id', 'name', 'email', 'phone', 'role'])]);
    }

    public function tasks(Request $request)
    {
        $tasks = BookingTask::with(['booking.property.building', 'property.building', 'inspection'])
            ->where('assigned_to', $request->user()->id)
            ->when($request->boolean('inspections_only'), fn ($query) => $query->whereIn('type', ['inspection', 'checkout_inspection']))
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->latest()
            ->limit(100)
            ->get();

        return response()->json(['tasks' => $tasks->map(fn ($task) => $this->taskData($task))->values()]);
    }

    public function task(Request $request, BookingTask $task)
    {
        abort_unless((string) $task->assigned_to === (string) $request->user()->id, 403);
        $task->load(['booking.property.building', 'property.building', 'inspection', 'createdBy', 'activities.user', 'remarks.user', 'costItems']);

        return response()->json([
            'task' => $this->taskData($task) + [
                'description' => $task->description,
                'created_by' => $task->createdBy?->name,
                'activities' => $task->activities->map(fn ($a) => ['action' => $a->action, 'comment' => $a->comment, 'at' => $a->created_at?->toIso8601String(), 'by' => $a->user?->name]),
                'remarks' => $task->remarks->map(fn ($r) => ['remark' => $r->remark, 'at' => $r->created_at?->toIso8601String(), 'by' => $r->user?->name]),
                'costs' => $task->costItems->map(fn ($c) => $c->only(['type', 'label', 'amount', 'quantity', 'unit_price', 'hours', 'rate'])),
            ],
        ]);
    }

    public function inspection(Request $request, BookingTask $task)
    {
        abort_unless((string) $task->assigned_to === (string) $request->user()->id, 403);
        abort_unless($task->isInspectionTask(), 404);
        // Only create a missing draft through the existing maintainer workflow.
        // Completed inspections must remain readable by the app after submit.
        if (! $task->inspection()->exists()) {
            app(MaintainerController::class)->inspectionForm($task);
        }
        $inspection = $task->inspection()->with('items')->firstOrFail();
        $draft = json_decode($inspection->draft_payload ?? '{}', true) ?: [];

        return response()->json([
            'task' => $this->taskData($task->loadMissing(['booking.property.building', 'property.building'])),
            'inspection' => [
                'id' => $inspection->id,
                'number' => $inspection->inspection_number,
                'type' => $inspection->type_label,
                'status' => $inspection->status,
                'revision' => (int) $inspection->draft_revision,
                'draft' => (object) $draft,
                'pdf_url' => $this->inspectionPdfUrl($inspection),
                'items' => $inspection->items->map(fn ($item) => [
                    'id' => $item->id, 'area' => $item->area, 'name' => $item->item,
                    'condition' => data_get($draft, 'items.'.$item->id.'.condition'),
                    'comment' => data_get($draft, 'items.'.$item->id.'.comment', $item->comment),
                    'photos' => collect($item->pictures ?: [])->map(fn ($path) => MediaStorage::url($path))->values(),
                ]),
                'inventory' => collect(UnitInventory::snapshot($inspection))->map(fn ($row) => $row + [
                    'draft_found' => data_get($draft, 'inventory.'.$row['id'].'.found'),
                    'draft_damaged' => data_get($draft, 'inventory.'.$row['id'].'.damaged'),
                    'draft_notes' => data_get($draft, 'inventory.'.$row['id'].'.notes'),
                ]),
            ],
        ]);
    }

    public function inspections()
    {
        $inspections = BookingInspection::with(['booking.property.building', 'property.building', 'submittedBy', 'task'])
            ->latest()->limit(100)->get();

        return response()->json(['inspections' => $inspections->map(fn ($inspection) => $this->inspectionData($inspection))->values()]);
    }

    public function review(BookingInspection $inspection)
    {
        $inspection->load(['booking.property.building', 'property.building', 'submittedBy', 'task', 'items']);
        $review = DB::table('unit_inventory_reviews')->where('inspection_id', $inspection->id)->first();

        return response()->json(['inspection' => $this->inspectionData($inspection) + [
            'notes' => $inspection->notes,
            'items' => $inspection->items->map(fn ($item) => [
                'area' => $item->area, 'name' => $item->item, 'condition' => $item->condition,
                'comment' => $item->comment,
                'photos' => collect($item->pictures ?: [])->map(fn ($path) => MediaStorage::url($path))->values(),
            ]),
            'inventory_status' => $review?->status,
            'inventory' => $review ? json_decode($review->rows, true) : [],
        ]]);
    }

    public function options()
    {
        return response()->json([
            'properties' => Property::with('building')->orderBy('name')->get()->map(fn ($property) => [
                'id' => $property->id, 'name' => $property->name,
                'building' => $property->building?->building_name,
            ]),
            'maintainers' => User::where('role', 'maintainer')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'bookings' => Booking::latest()->limit(500)->get(['id', 'property_id', 'booking_reference', 'guest_name']),
        ]);
    }

    private function taskData(BookingTask $task): array
    {
        $property = $task->booking?->property ?: $task->property;

        return [
            'id' => $task->id,
            'number' => $task->task_display_number,
            'title' => $task->title,
            'type' => $task->type,
            'status' => $task->status,
            'status_label' => $task->status_label,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->toDateString(),
            'property' => $property?->name,
            'building' => $property?->building?->building_name,
            'inspection_id' => $task->inspection?->id,
            'pdf_url' => $task->inspection ? $this->inspectionPdfUrl($task->inspection) : null,
        ];
    }

    private function inspectionData(BookingInspection $inspection): array
    {
        $property = $inspection->booking?->property ?: $inspection->property;

        return [
            'id' => $inspection->id,
            'number' => $inspection->inspection_number,
            'type' => $inspection->type_label,
            'status' => $inspection->status,
            'property' => $property?->name,
            'building' => $property?->building?->building_name,
            'submitted_by' => $inspection->submittedBy?->name,
            'submitted_at' => $inspection->submitted_at?->toIso8601String(),
            'task_id' => $inspection->booking_task_id,
            'total_items' => $inspection->total_items,
            'good_items' => $inspection->good_items,
            'issue_items' => $inspection->issue_items,
            'pdf_url' => $this->inspectionPdfUrl($inspection),
        ];
    }

    private function inspectionPdfUrl(BookingInspection $inspection): ?string
    {
        if ($inspection->status !== 'submitted' && ! $inspection->submitted_at) {
            return null;
        }

        return URL::temporarySignedRoute(
            'inspection.shared-pdf',
            now()->addMinutes(30),
            ['inspection' => $inspection->id],
        );
    }
}
