<?php

namespace App\Notifications;

use App\Models\UnitDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DtcmPermitExpiring extends Notification
{
    use Queueable;

    public function __construct(private readonly UnitDocument $permit) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $property = $this->permit->property;
        $days = max(0, (int) today()->diffInDays($this->permit->expires_at, false));

        return [
            'title' => 'DTCM permit expires in ' . $days . ' days',
            'message' => ($property?->building?->building_name ? $property->building->building_name . ' · ' : '')
                . ($property?->name ?? 'Unit') . ' requires DTCM permit renewal.',
            'property_id' => $property?->id,
            'unit_document_id' => $this->permit->id,
            'expires_at' => $this->permit->expires_at?->toDateString(),
            'url' => $property ? route('admin.property.document-wallet.index', $property) : route('admin.property.dtcm-permits'),
        ];
    }
}
