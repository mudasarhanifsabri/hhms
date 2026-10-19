<?php

namespace App\Support;

use App\Models\Booking;

class AgentCommission
{
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
