<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\admin\bookings\BookingController;
use App\Http\Controllers\admin\bookings\BookingCorrectionController;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Models\FinancialApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\MediaStorage;
use Illuminate\Support\Facades\Storage;

class FinancialApprovalController extends Controller
{
    private function authorizeReviewer(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Accounting', 'Super Administrator']), 403,
            'Only an Admin, Accounting user or Super Administrator can review financial requests.');
    }

    public function index(Request $request)
    {
        $this->authorizeReviewer();
        $status = $request->validate(['status' => 'nullable|in:pending,approved,rejected'])['status'] ?? 'pending';
        $requests = FinancialApprovalRequest::with(['booking.property', 'invoice', 'payment', 'requester', 'reviewer'])
            ->where('status', $status)->latest('requested_at')->paginate(30)->withQueryString();
        return view('admin.financial-approvals.index', compact('requests', 'status'));
    }

    public function proof(FinancialApprovalRequest $approval)
    {
        $this->authorizeReviewer();
        abort_unless($approval->proof_path, 404);
        return Storage::disk(MediaStorage::disk())->response(MediaStorage::path($approval->proof_path));
    }

    public function approve(Request $request, FinancialApprovalRequest $approval)
    {
        $this->authorizeReviewer();
        $data = $request->validate(['review_notes' => 'nullable|string|max:2000']);
        if ((string) $approval->requested_by === (string) auth()->id()) {
            throw ValidationException::withMessages(['approval' => 'You cannot approve your own request. A different Admin or Accounting user must review it.']);
        }

        DB::transaction(function () use ($approval, $data) {
            $approval = FinancialApprovalRequest::whereKey($approval->id)->lockForUpdate()->firstOrFail();
            if ($approval->status !== 'pending') throw ValidationException::withMessages(['approval' => 'This request has already been reviewed.']);

            $execution = Request::create('/', 'POST', $approval->payload ?? []);
            $execution->setUserResolver(fn () => auth()->user());
            $execution->setLaravelSession(request()->session());
            $execution->attributes->set('financial_approval_execution', true);
            $execution->attributes->set('approval_proof_path', $approval->proof_path);
            $execution->attributes->set('financial_approval_id', $approval->id);
            // Correction authorization reads the active HTTP request; mark this independently reviewed execution as trusted.
            request()->attributes->set('financial_approval_execution', true);

            match ($approval->type) {
                'payment_create' => app(BookingController::class)->recordInvoicePayment($execution, BookingInvoice::findOrFail($approval->booking_invoice_id)),
                'combined_payment_create' => app(BookingController::class)->recordCombinedPayment($execution, Booking::findOrFail($approval->booking_id)),
                'invoice_edit' => app(BookingCorrectionController::class)->invoice($execution, BookingInvoice::findOrFail($approval->booking_invoice_id)),
                'payment_edit' => app(BookingCorrectionController::class)->paymentDetails($execution, BookingInvoicePayment::findOrFail($approval->booking_invoice_payment_id)),
                'payment_reverse' => app(BookingCorrectionController::class)->reversePayment($execution, BookingInvoicePayment::findOrFail($approval->booking_invoice_payment_id)),
                default => throw ValidationException::withMessages(['approval' => 'Unsupported request type.']),
            };

            $approval->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_notes' => $data['review_notes'] ?? null]);
        });

        return back()->with('success', 'Financial request approved and posted successfully.');
    }

    public function reject(Request $request, FinancialApprovalRequest $approval)
    {
        $this->authorizeReviewer();
        $data = $request->validate(['review_notes' => 'required|string|min:5|max:2000']);
        if ((string) $approval->requested_by === (string) auth()->id()) {
            throw ValidationException::withMessages(['approval' => 'You cannot review your own request.']);
        }
        $updated = FinancialApprovalRequest::whereKey($approval->id)->where('status', 'pending')->update([
            'status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_notes' => $data['review_notes'], 'updated_at' => now(),
        ]);
        if (! $updated) throw ValidationException::withMessages(['approval' => 'This request has already been reviewed.']);
        $approval->booking?->histories()->create(['title' => 'Financial Request Rejected', 'description' => 'Request '.$approval->approval_no.' rejected by '.auth()->user()->name.'. Reason: '.$data['review_notes']]);
        return back()->with('success', 'Request rejected. No financial records were changed.');
    }
}
