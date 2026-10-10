<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\EmailDelivery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailDeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $deliveries = EmailDelivery::with(['landlord', 'property.building', 'creator'])
            ->when(in_array($status, ['queued', 'sent', 'failed'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('subject', 'like', "%{$search}%")->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('recipients', 'like', "%{$search}%")
                    ->orWhereHas('landlord', fn ($owner) => $owner->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('property', fn ($property) => $property->where('name', 'like', "%{$search}%"));
            }))->latest()->paginate(25)->withQueryString();

        return view('admin.email-deliveries.index', compact('deliveries', 'search', 'status'));
    }
}
