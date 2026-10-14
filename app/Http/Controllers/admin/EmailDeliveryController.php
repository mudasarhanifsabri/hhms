<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\EmailDelivery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EmailDeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        // Keep the admin area usable when application code reaches a server before
        // its database migrations have completed. The updater normally creates this
        // table, but a failed/interrupted deployment should not turn this page into
        // an opaque HTTP 500 response.
        if (! Schema::hasTable('email_deliveries')) {
            $deliveries = new LengthAwarePaginator([], 0, 25, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('admin.email-deliveries.index', [
                'deliveries' => $deliveries,
                'search' => $search,
                'status' => $status,
                'statusCounts' => array_fill_keys(['queued', 'sending', 'sent', 'failed'], 0),
                'deliveryLogUnavailable' => true,
            ]);
        }

        $deliveries = EmailDelivery::with(['landlord', 'property.building', 'creator'])
            ->when(in_array($status, ['queued', 'sending', 'sent', 'failed'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('subject', 'like', "%{$search}%")->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('recipients', 'like', "%{$search}%")
                    ->orWhereHas('landlord', fn ($owner) => $owner->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('property', fn ($property) => $property->where('name', 'like', "%{$search}%"));
            }))->latest()->paginate(25)->withQueryString();

        $statusCounts = EmailDelivery::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->whereIn('status', ['queued', 'sending', 'sent', 'failed'])
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return view('admin.email-deliveries.index', compact('deliveries', 'search', 'status', 'statusCounts'));
    }
}
