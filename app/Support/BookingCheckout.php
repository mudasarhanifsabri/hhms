<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingCheckout
{
    public static function reverse(Booking $booking, string $reason): void
    {
        DB::transaction(function () use ($booking, $reason) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($booking->status !== 'checked_out' || ! $booking->checked_out_at) {
                throw ValidationException::withMessages(['checkout' => 'This booking is not checked out.']);
            }
            if (Booking::where('property_id', $booking->property_id)->whereKeyNot($booking->id)->where('status', 'checked_in')->exists()) {
                throw ValidationException::withMessages(['checkout' => 'Another guest is checked into this unit. Review occupancy before reversing.']);
            }
            $tasks = $booking->tasks()->where('description', 'Auto created from booking check out.')
                ->where('created_at', '>=', $booking->checked_out_at)->lockForUpdate()->get();
            foreach ($tasks as $task) {
                if (! in_array($task->status, ['new', 'cancelled']) || $task->progress > 0 || $task->accepted_at || $task->started_at || $task->completed_at
                    || $task->assigned_to || $task->total_cost > 0 || $task->costItems()->exists() || $task->remarks()->exists() || $task->inspection()->exists()
                    || $task->pictures || $task->final_images || $task->invoice_attachment || $task->receipt_attachment) {
                    throw ValidationException::withMessages(['checkout' => 'Checkout task '.$task->task_number.' has activity. Review it before reversing checkout. No records were changed.']);
                }
            }
            foreach ($tasks as $task) {
                $task->update(['status' => 'cancelled']);
                $task->activities()->create(['user_id' => auth()->id(), 'action' => 'Cancelled', 'comment' => 'Accidental checkout reversed: '.$reason]);
            }
            $booking->update(['status' => $booking->checked_in_at ? 'checked_in' : 'confirmed', 'checked_out_at' => null]);
            $booking->property?->update(['status' => 'rented']);
            $booking->histories()->create(['title' => 'Checkout Reversed', 'description' => $reason.' Unstarted checkout tasks cancelled; invoices and payments unchanged.']);
        });
    }
}
