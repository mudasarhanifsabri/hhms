<?php

namespace App\Http\Controllers\Maintainers;

use App\Http\Controllers\Controller;
use App\Models\BookingTask;
use App\Models\Expense;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskExpenseController extends Controller
{
    public function store(Request $request, BookingTask $task)
    {
        abort_unless($task->assigned_to && (string) $task->assigned_to === (string) auth()->id(), 403);
        $data = $request->validate(['submission_id' => 'required|uuid', 'expense_date' => 'required|date', 'supplier' => 'required|string|max:200', 'amount' => 'required|numeric|min:0.01|max:999999|decimal:0,2', 'payment_status' => 'required|in:unpaid,paid_by_staff', 'description' => 'required|string|min:5|max:700', 'invoice' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240', 'receipt' => 'nullable|required_if:payment_status,paid_by_staff|file|mimes:pdf,jpg,jpeg,png,webp|max:10240']);

        return DB::transaction(function () use ($task, $request, $data) {
            $task = BookingTask::whereKey($task->id)->lockForUpdate()->firstOrFail();
            abort_unless((string) $task->assigned_to === (string) auth()->id(), 403);
            if (Expense::where('staff_submission_id', $data['submission_id'])->exists()) {
                return back()->with('success', 'Request already received.');
            }
            Expense::create(['booking_task_id' => $task->id, 'staff_submission_id' => $data['submission_id'], 'staff_payment_status' => $data['payment_status'],
                'expense_no' => 'EXP-STAFF-'.Str::upper(Str::random(12)), 'expense_date' => $data['expense_date'], 'category' => 'maintenance', 'supplier' => $data['supplier'],
                'property_id' => $task->property_id ?: $task->booking?->property_id, 'booking_id' => $task->booking_id, 'responsibility' => 'company', 'owner_billable' => false,
                'net_amount' => $data['amount'], 'vat_rate' => 0, 'vat_amount' => 0, 'gross_amount' => $data['amount'], 'approval_status' => 'draft', 'created_by' => auth()->id(),
                'invoice_path' => MediaStorage::store($request->file('invoice'), 'staff_expense_invoices'), 'receipt_path' => $request->hasFile('receipt') ? MediaStorage::store($request->file('receipt'), 'staff_expense_receipts') : null,
                'description' => ($data['payment_status'] === 'paid_by_staff' ? 'STAFF PAID — reimbursement requested' : 'UNPAID — office payment requested').' / '.auth()->user()->name.' / '.$task->task_number.' / '.$data['description'].' [Office: verify tax, responsibility and payment account before posting.]']);
            $task->activities()->create(['user_id' => auth()->id(), 'action' => 'Expense requested', 'comment' => 'AED '.$data['amount'].' — '.$data['payment_status'].'; awaiting office review, not posted.']);

            return back()->with('success', 'Expense request sent to the office. No bank balance or owner statement has changed.');
        });
    }
}
