<?php

namespace App\Http\Controllers\admin\inspections;

use App\Http\Controllers\Controller;
use App\Models\BookingInspection;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    public function index(Request $request)
    {
        $inspections = BookingInspection::with(['booking.property.building', 'property.building', 'submittedBy', 'task'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->input('q');
                $query->where(function ($inner) use ($search) {
                    $inner->where('inspection_number', 'like', "%$search%")
                        ->orWhereHas('booking', fn ($booking) => $booking->where('booking_reference', 'like', "%$search%")->orWhere('guest_name', 'like', "%$search%"));
                });
            })
            ->latest()
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return view('admin.inspections.index', compact('inspections'));
    }

    public function show(BookingInspection $inspection)
    {
        $inspection->load(['booking.property.building', 'property.building', 'submittedBy', 'task', 'items']);
        $comparison = $this->comparisonFor($inspection);

        return view('admin.inspections.show', compact('inspection', 'comparison'));
    }

    public function pdf(BookingInspection $inspection)
    {
        $inspection->load(['booking.property.building', 'property.building', 'submittedBy', 'task', 'items']);
        $comparison = $this->comparisonFor($inspection);

        return PdfRenderer::downloadView('admin.inspections.pdf.report', compact('inspection', 'comparison'), $inspection->inspection_number . '.pdf');
    }

    private function comparisonFor(BookingInspection $inspection): array
    {
        if (! $inspection->booking_id || ! in_array($inspection->type, ['check_in', 'check_out'], true)) {
            return ['other' => null, 'changed' => collect()];
        }

        $otherType = $inspection->type === 'check_in' ? 'check_out' : 'check_in';
        $other = BookingInspection::with('items')
            ->where('booking_id', $inspection->booking_id)
            ->where('type', $otherType)
            ->where('status', 'submitted')
            ->first();

        if (! $other) {
            return ['other' => null, 'changed' => collect()];
        }

        $currentItems = $inspection->items->keyBy(fn ($item) => $item->area . '|' . $item->item);
        $changed = $other->items
            ->map(function ($item) use ($currentItems, $inspection) {
                $match = $currentItems->get($item->area . '|' . $item->item);
                if (! $match || $match->condition === $item->condition) {
                    return null;
                }

                return [
                    'area' => $item->area,
                    'item' => $item->item,
                    'check_in' => $inspection->type === 'check_in' ? $match->condition : $item->condition,
                    'check_out' => $inspection->type === 'check_out' ? $match->condition : $item->condition,
                    'comment' => $match->comment ?: $item->comment,
                ];
            })
            ->filter()
            ->values();

        return ['other' => $other, 'changed' => $changed];
    }
}
