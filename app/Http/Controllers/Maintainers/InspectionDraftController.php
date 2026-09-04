<?php

namespace App\Http\Controllers\Maintainers;

use App\Http\Controllers\Controller;
use App\Models\BookingInspection;
use App\Models\BookingTask;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectionDraftController extends Controller
{
    private function locked(BookingTask|BookingInspection $task): BookingInspection
    {
        if ($task instanceof BookingInspection) {
            $inspection = BookingInspection::whereKey($task->id)->lockForUpdate()->firstOrFail();
            abort_unless($inspection->booking && (string) $inspection->booking->tenant_id === (string) auth()->id(), 403);
            abort_unless($inspection->status === 'draft', 409, 'Inspection is already submitted.');

            return $inspection;
        }
        $task = BookingTask::whereKey($task->id)->lockForUpdate()->firstOrFail();
        abort_unless($task->assigned_to && (string) $task->assigned_to === (string) auth()->id(), 403);
        abort_unless($task->isInspectionTask() && ! in_array($task->status, ['completed', 'closed', 'cancelled']), 409, 'Inspection is closed.');
        $inspection = $task->inspection()->lockForUpdate()->firstOrFail();
        abort_unless($inspection->status === 'draft', 409, 'Inspection is already submitted.');

        return $inspection;
    }

    public function save(Request $request, BookingTask $task)
    {
        return $this->saveDraft($request, $task);
    }

    public function tenantSave(Request $request, BookingInspection $inspection)
    {
        return $this->saveDraft($request, $inspection);
    }

    public function tenantPhoto(Request $request, BookingInspection $inspection)
    {
        return $this->savePhoto($request, $inspection);
    }

    private function saveDraft(Request $request, BookingTask|BookingInspection $task)
    {
        $data = $request->validate(['revision' => 'required|integer|min:0', 'items' => 'nullable|array', 'items.*.condition' => 'nullable|in:good,issue,na', 'items.*.comment' => 'nullable|string|max:1000', 'inventory' => 'nullable|array', 'inventory.*.found' => 'nullable|integer|min:0|max:100000', 'inventory.*.damaged' => 'nullable|integer|min:0|max:100000', 'inventory.*.notes' => 'nullable|string|max:1000', 'notes' => 'nullable|string|max:3000', 'step' => 'nullable|integer|min:0|max:1000']);

        return DB::transaction(function () use ($task, $data) {
            $inspection = $this->locked($task);
            abort_unless((int) $inspection->draft_revision === (int) $data['revision'], 409, 'A newer draft exists. Reload before editing.');
            $allowed = $inspection->items()->pluck('id')->all();
            abort_if(array_diff(array_keys($data['items'] ?? []), $allowed), 422, 'Unknown inspection item.');
            unset($data['revision']);
            if ($task instanceof BookingInspection) {
                $previous = json_decode($inspection->draft_payload ?? '{}', true) ?: [];
                $data['items'] = array_replace($previous['items'] ?? [], $data['items'] ?? []);
            }
            $revision = (int) $inspection->draft_revision + 1;
            $inspection->forceFill(['draft_payload' => json_encode($data), 'draft_revision' => $revision])->save();

            return response()->json(['revision' => $revision, 'saved_at' => now()->toIso8601String()]);
        });
    }

    public function photo(Request $request, BookingTask $task)
    {
        return $this->savePhoto($request, $task);
    }

    private function savePhoto(Request $request, BookingTask|BookingInspection $task)
    {
        $data = $request->validate(['upload_id' => 'required|uuid', 'item_id' => 'required|uuid', 'photo' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120']);

        return DB::transaction(function () use ($task, $request, $data) {
            $inspection = $this->locked($task);
            $item = $inspection->items()->whereKey($data['item_id'])->firstOrFail();
            $token = DB::table('inspection_upload_tokens')->where('id', $data['upload_id'])->first();
            if ($token) {
                abort_unless($token->inspection_id === $inspection->id && $token->item_id === $item->id, 409);

                return response()->json(['path' => $token->path, 'url' => MediaStorage::url($token->path)]);
            }
            abort_if(count((array) $item->pictures) >= 5, 422, 'Maximum 5 photos per item.');
            $path = MediaStorage::store($request->file('photo'), 'booking_inspection_pictures');
            $item->update(['pictures' => array_merge((array) $item->pictures, [$path])]);
            DB::table('inspection_upload_tokens')->insert(['id' => $data['upload_id'], 'inspection_id' => $inspection->id, 'item_id' => $item->id, 'path' => $path, 'created_at' => now(), 'updated_at' => now()]);

            return response()->json(['path' => $path, 'url' => MediaStorage::url($path)]);
        });
    }
}
