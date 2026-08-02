<?php

namespace App\Http\Controllers\admin\tasks;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\Property;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = $this->taskQuery($request)->paginate($request->input('per_page', 10))->withQueryString();
        $totalTasks = BookingTask::count();
        $openTasks = BookingTask::whereIn('status', ['new', 'open', 'assigned'])->count();
        $inProgressTasks = BookingTask::where('status', 'in_progress')->count();
        $completedTasks = BookingTask::whereIn('status', ['completed', 'closed'])->count();
        $overdueTasks = BookingTask::whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
        $maintainers = User::where('role', 'maintainer')->orderBy('name')->get();
        $properties = Property::with('building')->orderBy('name')->get();
        $bookings = Booking::with('property.building')->latest()->limit(100)->get();

        return view('admin.tasks.index', compact('tasks', 'totalTasks', 'openTasks', 'inProgressTasks', 'completedTasks', 'overdueTasks', 'maintainers', 'properties', 'bookings'));
    }

    public function grid(Request $request)
    {
        $tasks = $this->taskQuery($request)->paginate($request->input('per_page', 12))->withQueryString();

        return view('admin.tasks.grid', compact('tasks'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            '_create_task' => 'nullable',
            'booking_id' => 'nullable|exists:bookings,id',
            'property_id' => 'required_without:booking_id|nullable|exists:properties,id',
            'assigned_to' => 'nullable|exists:users,id',
            'type' => 'required|in:' . implode(',', array_keys(BookingTask::TYPES)),
            'priority' => 'required|in:' . implode(',', array_keys(BookingTask::PRIORITIES)),
            'due_date' => 'nullable|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'pictures.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $booking = ! empty($validatedData['booking_id'])
            ? Booking::find($validatedData['booking_id'])
            : null;
        $propertyId = $booking?->property_id ?: $validatedData['property_id'];
        $status = ! empty($validatedData['assigned_to']) ? 'assigned' : 'open';

        $task = BookingTask::create([
            'task_number' => $this->nextTaskNumber(),
            'booking_id' => $booking?->id,
            'property_id' => $propertyId,
            'assigned_to' => $validatedData['assigned_to'] ?? null,
            'created_by' => auth()->id(),
            'type' => $validatedData['type'],
            'category' => $validatedData['type'],
            'priority' => $validatedData['priority'],
            'due_date' => $validatedData['due_date'] ?? null,
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? null,
            'pictures' => $this->uploadOptimizedFiles($request, 'pictures', 'booking_task_attachments'),
            'status' => $status,
            'progress' => $status === 'assigned' ? 10 : 0,
        ]);

        $task->activities()->create([
            'user_id' => auth()->id(),
            'action' => 'Created',
            'comment' => 'Task created by admin.',
        ]);

        if ($task->assigned_to) {
            $task->activities()->create([
                'user_id' => auth()->id(),
                'action' => 'Assigned',
                'comment' => 'Task assigned while creating.',
            ]);
        }

        $this->updatePropertyStatusForTask($task);

        return redirect()->route('admin.task.show', $task->id)->with('success', 'Task created successfully.');
    }

    public function show(BookingTask $task)
    {
        $task->load([
            'booking.property.building',
            'property.building',
            'assignedUser',
            'createdBy',
            'remarks.user',
            'activities.user',
            'costItems',
        ]);
        $maintainers = User::where('role', 'maintainer')->orderBy('name')->get();

        return view('admin.tasks.show', compact('task', 'maintainers'));
    }

    public function update(Request $request, BookingTask $task)
    {
        $validatedData = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:' . implode(',', array_keys(BookingTask::PRIORITIES)),
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $status = $task->status;
        if (! empty($validatedData['assigned_to']) && in_array($task->status, ['new', 'open'], true)) {
            $status = 'assigned';
        }

        $task->update([
            ...$validatedData,
            'status' => $status,
            'progress' => $validatedData['progress'] ?? $task->progress,
        ]);

        $task->activities()->create([
            'user_id' => auth()->id(),
            'action' => 'Assigned',
            'comment' => 'Task assignment or tracking fields were updated.',
        ]);

        return back()->with('success', 'Task updated successfully.');
    }

    private function taskQuery(Request $request)
    {
        $query = BookingTask::with(['booking.property.building', 'property.building', 'assignedUser', 'createdBy']);
        $filter = $request->input('status');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($inner) use ($search) {
                $inner->where('task_number', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        $query = match ($filter) {
            'pending' => $query->whereIn('status', ['new', 'open', 'assigned']),
            'accepted' => $query->where('status', 'accepted'),
            'in_progress' => $query->where('status', 'in_progress'),
            'completed' => $query->whereIn('status', ['completed', 'closed']),
            'overdue' => $query->whereNotIn('status', ['completed', 'closed', 'cancelled'])->whereDate('due_date', '<', now()->toDateString()),
            'cancelled' => $query->where('status', 'cancelled'),
            default => $query,
        };

        return $query
            ->orderByRaw("FIELD(status, 'assigned', 'new', 'open', 'accepted', 'in_progress', 'waiting_approval', 'completed', 'closed', 'cancelled')")
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->latest();
    }

    private function nextTaskNumber(): string
    {
        do {
            $number = 'TSK-' . now()->format('ymd') . '-' . strtoupper(substr((string) str()->uuid(), 0, 4));
        } while (BookingTask::where('task_number', $number)->exists());

        return $number;
    }

    private function updatePropertyStatusForTask(BookingTask $task): void
    {
        if (! $task->property_id || ! in_array($task->type, ['cleaning', 'maintenance'], true)) {
            return;
        }

        Property::whereKey($task->property_id)->update([
            'status' => $task->type === 'cleaning' ? 'under_cleaning' : 'under_maintenance',
        ]);
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

        $destination = public_path($folder);
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

                $filename = uniqid() . '.webp';
                imagewebp($image, $destination . DIRECTORY_SEPARATOR . $filename, 78);
                imagedestroy($image);

                return $folder . '/' . $filename;
            }
        }

        $filename = uniqid() . '.' . $extension;
        $file->move($destination, $filename);

        return $folder . '/' . $filename;
    }
}
