<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingInvoicePayment;
use Throwable;

class AgentCommission
{
    public static function agencyFee(BookingInvoicePayment $payment): float
    {
        $invoice = $payment->invoice;
        if (! $invoice) {
            return round((float) (($payment->allocation ?? [])['agency'] ?? 0), 2);
        }

        try {
            $remaining = InvoiceSettlement::charges($invoice);
            $payments = $invoice->relationLoaded('payments')
                ? $invoice->payments
                : $invoice->payments()->orderBy('payment_date')->orderBy('created_at')->get();
            $payments = $payments->sortBy(fn ($item) => sprintf('%s-%s-%s',
                $item->payment_date?->format('Y-m-d') ?? '',
                $item->created_at?->format('Y-m-d H:i:s.u') ?? '',
                $item->id
            ));

            foreach ($payments as $invoicePayment) {
                $allocation = InvoiceSettlement::allocateAmounts($remaining, min((float) $invoicePayment->amount, array_sum($remaining)));
                if ($invoicePayment->is($payment)) {
                    return round((float) $allocation['agency'], 2);
                }
                foreach ($remaining as $key => $amount) {
                    $remaining[$key] = round($amount - $allocation[$key], 2);
                }
            }
        } catch (Throwable) {
            // Old invoices that cannot reconcile retain their stored, auditable allocation.
        }

        return round((float) (($payment->allocation ?? [])['agency'] ?? 0), 2);
    }

    public static function rate(?Booking $booking, array $allocation = []): float
    {
        if (! $booking?->agent_id) {
            return 0;
        }

        foreach ([
            $allocation['agent_commission_percent'] ?? null,
            $booking->agent_commission_percent,
            $booking->agent?->agent_commission,
        ] as $candidate) {
            $rate = (float) ($candidate ?? 0);
            if ($rate > 0) {
                return min($rate, 100);
            }
        }

        return 0;
    }

    public static function split(float $agencyFee, ?Booking $booking, array $allocation = []): array
    {
        $rate = self::rate($booking, $allocation);
        $agentShare = round(max($agencyFee, 0) * $rate / 100, 2);

        return [
            'rate' => $rate,
            'agent_share' => $agentShare,
            'company_share' => round(max($agencyFee, 0) - $agentShare, 2),
        ];
    }
}
