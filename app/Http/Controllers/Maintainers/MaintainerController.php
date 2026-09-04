<?php

namespace App\Http\Controllers\Maintainers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\BookingTask;
use App\Models\BookingTaskCostItem;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintainerController extends Controller
{
    public function dashboard()
    {
        return redirect()->route('maintainer.task.index');
    }

    public function profile()
    {
        $user = Auth::user();
        $stats = [
            'total' => $this->assignedTaskQuery()->count(),
            'completed' => $this->assignedTaskQuery()->whereIn('status', ['completed', 'closed'])->count(),
            'in_progress' => $this->assignedTaskQuery()->where('status', 'in_progress')->count(),
        ];

        return view('maintainer.profile', compact('user', 'stats'));
    }

    public function notifications()
    {
        $tasks = $this->assignedTaskQuery()
            ->whereIn('status', ['new', 'open', 'assigned'])
            ->latest()
            ->take(30)
            ->get();

        return view('maintainer.notifications', compact('tasks'));
    }

    public function tasks(Request $request)
    {
        $baseQuery = $this->assignedTaskQuery();
        if ($request->boolean('inspections_only')) {
            $baseQuery->whereIn('type', ['inspection', 'checkout_inspection']);
        }
        $statsQuery = clone $baseQuery;
        $tasks = $this->filterTasks($baseQuery, $request)->paginate($request->input('per_page', 10))->withQueryString();
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $statsQuery)->whereIn('status', ['completed', 'closed'])->count(),
            'overdue' => (clone $statsQuery)
                ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];

        return view('maintainer.tasks.index', compact('tasks', 'stats'));
    }

    public function taskGrid(Request $request)
    {
        $tasks = $this->assignedTaskQuery()->paginate($request->input('per_page', 12));

        return view('maintainer.tasks.grid', compact('tasks'));
    }

    public function liveTasks()
    {
        $tasks = $this->assignedTaskQuery()
            ->whereIn('status', ['new', 'open', 'assigned'])
            ->take(10)
            ->get()
            ->map(function (BookingTask $task) {
                return [
                    'id' => $task->id,
                    'number' => $task->task_display_number,
                    'title' => $task->title,
                    'property' => $task->booking?->property?->building?->name ?? $task->property?->building?->name ?? 'Property',
                    'unit' => $task->booking?->property?->name ?? $task->property?->name ?? 'Unit',
                    'priority' => $task->priority,
                    'priority_label' => $task->priority_label,
                    'status_label' => $task->status_label,
                    'due_date' => $task->due_date?->format('d M, Y') ?? '-',
                    'url' => route('maintainer.task.show', $task->id),
                    'updated_at' => $task->updated_at?->timestamp,
                ];
            });

        return response()->json(['tasks' => $tasks]);
    }

    public function showTask(BookingTask $task)
    {
        $this->authorizeAssignedTask($task);

        $task->load([
            'booking.property.building',
            'property.building',
            'assignedUser',
            'createdBy',
            'remarks.user',
            'activities.user',
            'costItems',
            'inspection.items',
        ]);

        return view('maintainer.tasks.show', compact('task'));
    }

    public function acceptForm(BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        $task->load(['booking.property.building', 'createdBy']);

        return view('maintainer.tasks.accept', compact('task'));
    }

    public function acceptTask(Request $request, BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        $validatedData = $request->validate([
            'expected_completion_date' => 'required|date|after_or_equal:today',
            'initial_remark' => 'nullable|string|max:2000',
            'pictures.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
        ]);

        $task->update([
            'status' => 'accepted',
            'progress' => 25,
            'accepted_at' => now(),
            'expected_completion_date' => $validatedData['expected_completion_date'],
        ]);

        if (! empty($validatedData['initial_remark']) || $request->hasFile('pictures')) {
            $task->remarks()->create([
                'user_id' => Auth::id(),
                'remark' => $validatedData['initial_remark'] ?: 'Task accepted.',
                'pictures' => $this->uploadOptimizedFiles($request, 'pictures', 'booking_task_remark_pictures'),
                'status_update' => 'accepted',
            ]);
        }

        $this->recordActivity($task, 'Accepted', $validatedData['initial_remark'] ?? null, $request);

        return redirect()->route('maintainer.task.show', $task->id)->with('success', 'Task accepted.');
    }

    public function remarkForm(BookingTask $task)
    {
        $this->authorizeAssignedTask($task);

        return view('maintainer.tasks.remark', compact('task'));
    }

    public function timeline(BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        $task->load(['activities.user', 'remarks.user']);

        return view('maintainer.tasks.timeline', compact('task'));
    }

    public function costForm(BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        $task->load('costItems');

        return view('maintainer.tasks.cost', compact('task'));
    }

    public function addCost(Request $request, BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        $validatedData = $request->validate([
            'type' => 'required|in:labor,material,other',
            'label' => 'required|string|max:255',
            'worker' => 'required_if:type,labor|nullable|string|max:255',
            'hours' => 'required_if:type,labor|nullable|numeric|min:0.01',
            'rate' => 'required_if:type,labor|nullable|numeric|min:0.01',
            'quantity' => 'required_if:type,material|nullable|numeric|min:0.01',
            'unit_price' => 'required_if:type,material|nullable|numeric|min:0.01',
            'amount' => 'required_if:type,other|nullable|numeric|min:0.01',
        ]);

        $amount = match ($validatedData['type']) {
            'labor' => (float) ($validatedData['hours'] ?? 0) * (float) ($validatedData['rate'] ?? 0),
            'material' => (float) ($validatedData['quantity'] ?? 0) * (float) ($validatedData['unit_price'] ?? 0),
            default => (float) ($validatedData['amount'] ?? 0),
        };

        $this->createCostItem($task, [
            'type' => $validatedData['type'],
            'label' => $validatedData['label'],
            'worker' => $validatedData['worker'] ?? null,
            'hours' => $validatedData['hours'] ?? null,
            'rate' => $validatedData['rate'] ?? null,
            'quantity' => $validatedData['quantity'] ?? null,
            'unit_price' => $validatedData['unit_price'] ?? null,
            'amount' => $amount,
        ]);
        $task->recalculateCosts();

        return redirect()->route('maintainer.task.show', $task->id)->with('success', 'Cost added.');
    }

    public function inspectionForm(BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        abort_unless($task->isInspectionTask(), 404);

        $task->load(['booking.property.building', 'property.building', 'inspection.items']);
        $inspection = $this->ensureInspectionForTask($task);

        abort_unless($inspection->status === 'draft' && !in_array($task->status, ['completed', 'closed', 'cancelled']), 422, 'This inspection is closed.');
        $inventoryRows = \App\Support\UnitInventory::snapshot($inspection);
        return view('maintainer.tasks.inspection', compact('task', 'inspection', 'inventoryRows'));
    }

    public function submitInspection(Request $request, BookingTask $task)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $task) {
        $task = BookingTask::whereKey($task->id)->lockForUpdate()->firstOrFail();
        $this->authorizeAssignedTask($task);
        abort_unless($task->isInspectionTask(), 404);

        $inspection = $this->ensureInspectionForTask($task);
        abort_unless($inspection->status === 'draft' && !in_array($task->status, ['completed', 'closed', 'cancelled']), 422, 'This inspection is closed.');
        $validatedData = $request->validate([
            'items' => 'required|array',
            'items.*.condition' => 'required|in:good,issue,na',
            'items.*.comment' => 'nullable|string|max:1000',
            'pictures' => 'nullable|array',
            'pictures.*' => 'nullable|array|max:5',
            'pictures.*.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'notes' => 'nullable|string|max:3000',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
        ]);

        if (array_diff($inspection->items->pluck('id')->all(), array_keys($validatedData['items']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Complete every inspection item before submitting.']);
        }
        \App\Support\UnitInventory::submit($inspection, (array) $request->input('inventory', []));
        foreach ($validatedData['items'] as $itemId => $payload) {
            $item = $inspection->items()->whereKey($itemId)->first();
            if (! $item) {
                continue;
            }

            $pictures = (array) $item->pictures;
            if ($request->hasFile("pictures.$itemId")) {
                foreach ((array) $request->file("pictures.$itemId") as $file) {
                    $pictures[] = $this->uploadOptimizedFile($file, 'booking_inspection_pictures');
                }
            }

            $item->update([
                'condition' => $payload['condition'],
                'comment' => $payload['comment'] ?? null,
                'pictures' => $pictures,
            ]);
        }

        $this->recalculateInspection($inspection);
        $inspection->update([
            'notes' => $validatedData['notes'] ?? $inspection->notes,
            'status' => 'submitted',
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
        ]);

        $task->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
            'completion_notes' => $inspection->inspection_number . ' submitted with ' . $inspection->issue_items . ' issue(s).',
        ]);
        $this->recordActivity($task, 'Inspection Submitted', $task->completion_notes, $request);

        return redirect()->route('maintainer.task.index')->with('success', 'Inspection submitted and task completed.');
        });
    }

    public function completeForm(BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        if ($task->isInspectionTask()) return redirect()->route('maintainer.task.inspection.form', $task);
        $task->load('costItems');

        return view('maintainer.tasks.complete', compact('task'));
    }

    public function startTask(Request $request, BookingTask $task)
    {
        $this->authorizeAssignedTask($task);

        $task->update([
            'status' => 'in_progress',
            'progress' => 50,
            'started_at' => now(),
        ]);

        $this->recordActivity($task, 'Started', $request->input('comment'), $request);

        return redirect()->route('maintainer.task.show', $task->id)->with('success', 'Task started.');
    }

    public function completeTask(Request $request, BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        if ($task->isInspectionTask()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['inspection' => 'Submit the inspection checklist to complete this task.']);
        }
        $validatedData = $request->validate([
            'completion_notes' => 'required|string|max:3000',
            'final_remark' => 'required|string|max:2000',
            'completion_date' => 'required|date',
            'final_images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'labor_worker' => 'nullable|string|max:255',
            'labor_hours' => 'nullable|numeric|min:0',
            'labor_rate' => 'nullable|numeric|min:0',
            'material_item' => 'nullable|string|max:255',
            'material_quantity' => 'nullable|numeric|min:0',
            'material_unit_price' => 'nullable|numeric|min:0',
            'transportation' => 'nullable|numeric|min:0',
            'equipment' => 'nullable|numeric|min:0',
            'miscellaneous' => 'nullable|numeric|min:0',
            'invoice_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'receipt_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'warranty_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
        ]);

        $this->storeCostItems($task, $validatedData);
        $task->recalculateCosts();

        $payload = [
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => $validatedData['completion_date'] . ' ' . now()->format('H:i:s'),
            'completion_notes' => $validatedData['completion_notes'],
            'final_images' => $this->uploadOptimizedFiles($request, 'final_images', 'booking_task_final_pictures'),
        ];

        foreach (['invoice_attachment' => 'Invoice Uploaded', 'receipt_attachment' => 'Receipt Uploaded', 'warranty_attachment' => 'Warranty Uploaded'] as $field => $action) {
            if ($request->hasFile($field)) {
                $payload[$field] = $this->uploadOptimizedFile($request->file($field), 'booking_task_attachments');
                $this->recordActivity($task, $action, null, $request);
            }
        }

        $task->update($payload);
        $this->updatePropertyStatusAfterTaskCompletion($task->fresh(['property']));
        $task->remarks()->create([
            'user_id' => Auth::id(),
            'remark' => $validatedData['final_remark'],
            'pictures' => $payload['final_images'],
            'status_update' => 'completed',
        ]);

        $this->recordActivity($task, 'Completed', $validatedData['completion_notes'], $request);

        return redirect()->route('maintainer.task.index')->with('success', 'Task completed.');
    }

    public function addRemark(Request $request, BookingTask $task)
    {
        $this->authorizeAssignedTask($task);
        if ($task->isInspectionTask() && $request->input('status_update') === 'completed') {
            throw \Illuminate\Validation\ValidationException::withMessages(['inspection' => 'Submit the inspection checklist to complete this task.']);
        }

        $validatedData = $request->validate([
            'remark' => 'required|string|max:2000',
            'status_update' => 'nullable|in:accepted,in_progress,waiting_approval,completed',
            'pictures.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
        ]);

        $task->remarks()->create([
            'user_id' => Auth::id(),
            'remark' => $validatedData['remark'],
            'pictures' => $this->uploadOptimizedFiles($request, 'pictures', 'booking_task_remark_pictures'),
            'status_update' => $validatedData['status_update'] ?? null,
        ]);

        if (! empty($validatedData['status_update'])) {
            $task->update([
                'status' => $validatedData['status_update'],
                'progress' => match ($validatedData['status_update']) {
                    'accepted' => 25,
                    'in_progress' => 50,
                    'waiting_approval' => 80,
                    'completed' => 100,
                    default => $task->progress,
                },
            ]);
        }

        $this->recordActivity($task, 'Remark Added', $validatedData['remark'], $request);

        return redirect()->route('maintainer.task.show', $task->id)->with('success', 'Task remark added.');
    }

    private function assignedTaskQuery()
    {
        return BookingTask::with(['booking.property.building', 'assignedUser', 'remarks.user'])
            ->where('assigned_to', Auth::id())
            ->orderByRaw("CASE status WHEN 'assigned' THEN 1 WHEN 'new' THEN 2 WHEN 'open' THEN 3 WHEN 'accepted' THEN 4 WHEN 'in_progress' THEN 5 WHEN 'waiting_approval' THEN 6 WHEN 'completed' THEN 7 WHEN 'closed' THEN 8 WHEN 'cancelled' THEN 9 ELSE 0 END")
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->latest();
    }

    private function filterTasks($query, Request $request)
    {
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%' . $search . '%')
                    ->orWhere('task_number', 'like', '%' . $search . '%');
            });
        }

        return match ($request->input('status')) {
            'assigned' => $query->whereIn('status', ['new', 'open', 'assigned']),
            'in_progress' => $query->where('status', 'in_progress'),
            'completed' => $query->whereIn('status', ['completed', 'closed']),
            default => $query,
        };
    }

    private function authorizeAssignedTask(BookingTask $task): void
    {
        abort_unless($task->assigned_to !== null && (string) $task->assigned_to === (string) Auth::id(), 403);
    }

    private function recordActivity(BookingTask $task, string $action, ?string $comment, Request $request): void
    {
        $task->activities()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'comment' => $comment,
            'gps_latitude' => $request->input('gps_latitude'),
            'gps_longitude' => $request->input('gps_longitude'),
        ]);
    }

    private function storeCostItems(BookingTask $task, array $data): void
    {
        $laborAmount = (float) ($data['labor_hours'] ?? 0) * (float) ($data['labor_rate'] ?? 0);
        if ($laborAmount > 0) {
            $this->createCostItem($task, [
                'type' => 'labor',
                'label' => 'Labor',
                'worker' => $data['labor_worker'] ?? null,
                'hours' => $data['labor_hours'] ?? null,
                'rate' => $data['labor_rate'] ?? null,
                'amount' => $laborAmount,
            ]);
        }

        $materialAmount = (float) ($data['material_quantity'] ?? 0) * (float) ($data['material_unit_price'] ?? 0);
        if ($materialAmount > 0) {
            $this->createCostItem($task, [
                'type' => 'material',
                'label' => $data['material_item'] ?? 'Material',
                'quantity' => $data['material_quantity'] ?? null,
                'unit_price' => $data['material_unit_price'] ?? null,
                'amount' => $materialAmount,
            ]);
        }

        foreach (['transportation' => 'Transportation', 'equipment' => 'Equipment', 'miscellaneous' => 'Miscellaneous'] as $field => $label) {
            $amount = (float) ($data[$field] ?? 0);
            if ($amount > 0) {
                $this->createCostItem($task, [
                    'type' => 'other',
                    'label' => $label,
                    'amount' => $amount,
                ]);
            }
        }
    }

    private function createCostItem(BookingTask $task, array $data): BookingTaskCostItem
    {
        $item = $task->costItems()->create($data);
        $this->recordActivity($task, ucfirst($data['type']) . ' Added', $data['label'] . ' - AED ' . number_format((float) $data['amount'], 2), request());

        return $item;
    }

    private function ensureInspectionForTask(BookingTask $task): BookingInspection
    {
        $task->loadMissing(['booking.property', 'property', 'inspection.items']);
        $property = $task->booking?->property ?: $task->property;

        $inspection = $task->inspection ?: BookingInspection::create([
            'booking_id' => $task->booking_id,
            'property_id' => $property?->id,
            'booking_task_id' => $task->id,
            'inspection_number' => $this->nextInspectionNumber(),
            'type' => $task->type === 'checkout_inspection' ? 'check_out' : 'routine',
            'status' => 'draft',
            'selected_areas' => array_keys($this->inspectionTemplateForTask($task)),
        ]);

        if (empty($inspection->selected_areas)) {
            $inspection->update(['selected_areas' => array_keys($this->inspectionTemplateForTask($task))]);
        }

        if ($inspection->items()->count() === 0) {
            $sort = 1;
            foreach ($this->inspectionTemplateForTask($task) as $area => $items) {
                foreach ($items as $item) {
                    $inspection->items()->create([
                        'area' => $area,
                        'item' => $item,
                        'condition' => 'na',
                        'sort_order' => $sort++,
                    ]);
                }
            }
            $this->recalculateInspection($inspection);
        }

        return $inspection->fresh(['items', 'booking.property.building', 'property.building']);
    }

    private function inspectionTemplateForTask(BookingTask $task): array
    {
        $property = $task->booking?->property ?: $task->property;
        $bedrooms = max(0, (int) ($property?->bedrooms ?? 0));
        $bathrooms = max(1, (int) ($property?->bathrooms ?? 1));
        $areas = [
            'Living Room' => ['Floor/Walls', 'Sofa/Chairs', 'TV/Remote', 'Lights', 'AC/Cooling'],
            'Kitchen' => ['Refrigerator', 'Microwave', 'Cooker/Oven', 'Sink/Tap', 'Cabinets'],
        ];

        if ($bedrooms === 0) {
            $areas['Studio Sleeping Area'] = ['Bed/Mattress', 'Wardrobe', 'Bed Linen', 'Lights', 'AC/Cooling'];
        } else {
            for ($i = 1; $i <= $bedrooms; $i++) {
                $areas['Bedroom ' . $i] = ['Bed/Mattress', 'Wardrobe', 'Bed Linen', 'Lights', 'AC/Cooling'];
            }
        }

        for ($i = 1; $i <= $bathrooms; $i++) {
            $areas['Bathroom ' . $i] = ['Shower', 'Toilet', 'Wash Basin', 'Mirror', 'Water Pressure'];
        }

        $featureText = strtolower(json_encode([
            $property?->amenities,
            $property?->additional_features,
            $property?->description,
            $property?->category,
        ]));

        if (str_contains($featureText, 'balcony')) {
            $areas['Balcony'] = ['Door/Lock', 'Floor', 'Furniture', 'Railing', 'Lights'];
        }

        $areas['Extra Items'] = ['WiFi Router', 'Smart Lock/Keys', 'Iron/Hair Dryer', 'Safety Equipment'];

        return $areas;
    }

    private function recalculateInspection(BookingInspection $inspection): void
    {
        $items = $inspection->items;
        $inspection->update([
            'total_items' => $items->count(),
            'good_items' => $items->where('condition', 'good')->count(),
            'issue_items' => $items->where('condition', 'issue')->count(),
            'na_items' => $items->where('condition', 'na')->count(),
        ]);
    }

    private function nextInspectionNumber(): string
    {
        do {
            $number = 'INSP-' . now()->format('Ymd') . '-' . strtoupper(substr((string) str()->uuid(), 0, 4));
        } while (BookingInspection::where('inspection_number', $number)->exists());

        return $number;
    }

    private function updatePropertyStatusAfterTaskCompletion(BookingTask $task): void
    {
        if (! in_array($task->type, ['cleaning', 'maintenance'], true) || ! $task->property) {
            return;
        }

        $openCleaning = BookingTask::where('property_id', $task->property_id)
            ->where('type', 'cleaning')
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->exists();

        $openMaintenance = BookingTask::where('property_id', $task->property_id)
            ->where('type', 'maintenance')
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->exists();

        $hasBookedStay = Booking::where('property_id', $task->property_id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_out', '>=', now()->toDateString())
            ->exists();

        $status = match (true) {
            $openCleaning => 'under_cleaning',
            $openMaintenance => 'under_maintenance',
            $hasBookedStay => 'booked',
            default => 'available',
        };

        $task->property->update(['status' => $status]);
    }

    private function uploadOptimizedFiles(Request $request, string $field, string $folder): array
    {
        if (! $request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ((array) $request->file($field) as $file) {
            $paths[] = $this->uploadOptimizedFile($file, $folder);
        }

        return $paths;
    }

    private function uploadOptimizedFile($file, string $folder): string
    {
        if (MediaStorage::disk() !== 'public') {
            return MediaStorage::store($file, $folder);
        }

        $datedFolder = MediaStorage::datedFolder($folder);
        $destination = public_path($datedFolder);
        if (! file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagewebp')) {
            $image = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                'png' => @imagecreatefrompng($file->getRealPath()),
                'webp' => @imagecreatefromwebp($file->getRealPath()),
                default => null,
            };

            if ($image) {
                $width = imagesx($image);
                $height = imagesy($image);
                $maxWidth = 1280;
                if ($width > $maxWidth) {
                    $newWidth = $maxWidth;
                    $newHeight = (int) round($height * ($maxWidth / $width));
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resized;
                }

                $filename = MediaStorage::trackedFilename($file, 'webp');
                imagewebp($image, $destination . DIRECTORY_SEPARATOR . $filename, 78);
                imagedestroy($image);

                return $datedFolder . '/' . $filename;
            }
        }

        $filename = MediaStorage::trackedFilename($file, $extension);
        $file->move($destination, $filename);

        return $datedFolder . '/' . $filename;
    }
}
