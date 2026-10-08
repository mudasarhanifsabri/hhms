<?php

namespace App\Support;

use App\Models\FinancialApprovalRequest;
use Illuminate\Http\Request;
use App\Models\BookingInvoicePayment;
use Illuminate\Validation\ValidationException;

class FinancialApproval
{
    public static function assertUniqueReference(string $reference): void
    {
        $reference = trim($reference);
        $posted = BookingInvoicePayment::whereNull('reversed_at')->where('reference', $reference)->exists();
        $pending = FinancialApprovalRequest::where('status', 'pending')->whereIn('type', ['payment_create', 'combined_payment_create'])
            ->where('payload->reference', $reference)->exists();
        if ($posted || $pending) {
            throw ValidationException::withMessages(['reference' => 'This bank reference is already used by an active payment or pending approval request.']);
        }
    }

    public static function assertNoPendingPaymentChange(string $paymentId): void
    {
        if (FinancialApprovalRequest::where('status', 'pending')->where('booking_invoice_payment_id', $paymentId)->exists()) {
            throw ValidationException::withMessages(['correction' => 'This payment already has a pending edit or deletion request. Review that request first.']);
        }
    }

    public static function trusted(Request $request): bool
    {
        return (bool) $request->attributes->get('financial_approval_execution');
    }

    public static function submit(string $type, array $payload, array $links = [], ?string $proofPath = null, ?array $before = null): FinancialApprovalRequest
    {
        return FinancialApprovalRequest::create([
            'type' => $type, 'status' => 'pending', 'payload' => $payload, 'proof_path' => $proofPath,
            'before_snapshot' => $before, 'request_reason' => $payload['reason'] ?? $payload['notes'] ?? null,
            'booking_id' => $links['booking_id'] ?? null, 'booking_invoice_id' => $links['booking_invoice_id'] ?? null,
            'booking_invoice_payment_id' => $links['booking_invoice_payment_id'] ?? null,
            'requested_by' => auth()->id(), 'requested_at' => now(),
        ]);
    }
}
