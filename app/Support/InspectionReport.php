<?php

namespace App\Support;

use App\Models\BookingInspection;
use Illuminate\Support\Facades\Storage;

class InspectionReport
{
    public static function path(BookingInspection|string $inspection): string
    {
        $id = $inspection instanceof BookingInspection ? $inspection->getKey() : $inspection;

        return 'inspection-reports/'.$id.'.pdf';
    }

    public static function generate(BookingInspection $inspection, ?callable $progress = null): string
    {
        $inspection->load(['booking.property.building', 'property.building', 'submittedBy', 'task', 'items']);
        $progress?->__invoke(8, 'Loading inspection details');

        $comparison = self::comparisonFor($inspection);
        $photos = $inspection->items->sum(fn ($item) => count((array) $item->pictures));
        $processed = 0;
        $photoGroups = $inspection->items
            ->filter(fn ($item) => count((array) $item->pictures))
            ->map(function ($item) use ($photos, &$processed, $progress) {
                $thumbnails = collect($item->pictures)->map(function ($path) use ($photos, &$processed, $progress) {
                    $thumbnail = InspectionPhotos::cachedThumbnail($path);
                    $processed++;
                    $percent = 10 + (int) floor(($processed / max(1, $photos)) * 70);
                    $progress?->__invoke($percent, "Optimizing photo {$processed} of {$photos}");

                    return $thumbnail;
                })->all();

                return [
                    'area' => $item->area,
                    'item' => $item->item,
                    'condition' => $item->condition,
                    'comment' => $item->comment,
                    'photos' => $thumbnails,
                ];
            });

        $progress?->__invoke(84, 'Building PDF pages');
        $html = view('admin.inspections.pdf.report', compact('inspection', 'comparison', 'photoGroups'))->render();
        $pdf = PdfRenderer::output($html);
        $progress?->__invoke(96, 'Saving report');
        Storage::disk('local')->put(self::path($inspection), $pdf);

        return self::path($inspection);
    }

    private static function comparisonFor(BookingInspection $inspection): array
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

        if (! $other) return ['other' => null, 'changed' => collect()];

        $currentItems = $inspection->items->keyBy(fn ($item) => $item->area.'|'.$item->item);
        $changed = $other->items->map(function ($item) use ($currentItems, $inspection) {
            $match = $currentItems->get($item->area.'|'.$item->item);
            if (! $match || $match->condition === $item->condition) return null;

            return [
                'area' => $item->area,
                'item' => $item->item,
                'check_in' => $inspection->type === 'check_in' ? $match->condition : $item->condition,
                'check_out' => $inspection->type === 'check_out' ? $match->condition : $item->condition,
                'comment' => $match->comment ?: $item->comment,
            ];
        })->filter()->values();

        return ['other' => $other, 'changed' => $changed];
    }
}
