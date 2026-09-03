<?php


namespace App\Http\Controllers\Tenants;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\BookingInspectionItem;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function editProfile()
    {
        return view('tenant.profile', ['tenant' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string|max:255', 'phone'=>'required|string|max:50',
            'eid_passport_no'=>'required|string|max:50', 'nationality'=>'required|string|max:100',
            'dob'=>'required|date|before:today', 'address'=>'required|string|max:255',
            'emergency_contact_name'=>'nullable|string|max:255', 'emergency_contact_phone'=>'nullable|string|max:50',
        ]);
        $request->user()->fill($data)->forceFill(['tenant_profile_required'=>false])->save();
        return redirect()->route('tenant.dashboard')->with('success','Profile completed. Welcome to your guest app.');
    }

    public function dashboard()
    {
        $bookings = Booking::with('property.building')
            ->where('tenant_id', Auth::id())
            ->latest()
            ->get();
        $activeBookings = $bookings->whereIn('status', ['confirmed', 'checked_in']);

        return view('tenant.dashboard.index', compact('bookings', 'activeBookings'));
    }

    public function booking(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->load(['property.building', 'inspections.items']);

        return view('tenant.bookings.show', compact('booking'));
    }

    public function startInspection(Booking $booking, string $type)
    {
        $this->authorizeBooking($booking);
        abort_unless(in_array($type, ['check_in', 'check_out'], true), 404);

        $inspection = BookingInspection::firstOrCreate(
            ['booking_id' => $booking->id, 'type' => $type],
            [
                'property_id' => $booking->property_id,
                'submitted_by' => Auth::id(),
                'inspection_number' => $this->nextInspectionNumber(),
                'status' => 'draft',
                'selected_areas' => array_keys($this->inspectionTemplate($booking)),
            ]
        );

        if ($inspection->items()->count() === 0) {
            $sort = 1;
            foreach ($this->inspectionTemplate($booking) as $area => $items) {
                foreach ($items as $item) {
                    $inspection->items()->create([
                        'area' => $area,
                        'item' => $item,
                        'condition' => 'na',
                        'sort_order' => $sort++,
                    ]);
                }
            }
            $this->recalculateInspection($inspection);
        }

        return redirect()->route('tenant.inspection.areas', $inspection->id);
    }

    public function areas(BookingInspection $inspection)
    {
        $this->authorizeInspection($inspection);
        $inspection->load('booking.property');
        $areas = $this->inspectionTemplate($inspection->booking);

        return view('tenant.inspections.areas', compact('inspection', 'areas'));
    }

    public function storeAreas(Request $request, BookingInspection $inspection)
    {
        $this->authorizeInspection($inspection);
        $areas = array_keys($this->inspectionTemplate($inspection->booking));
        $validated = $request->validate([
            'areas' => 'required|array|min:1',
            'areas.*' => 'in:' . implode(',', $areas),
        ]);

        $inspection->update(['selected_areas' => $validated['areas']]);

        return redirect()->route('tenant.inspection.inspect', [$inspection->id, $validated['areas'][0]]);
    }

    public function inspectArea(BookingInspection $inspection, string $area)
    {
        $this->authorizeInspection($inspection);
        $selectedAreas = $inspection->selected_areas ?: [];
        abort_unless(in_array($area, $selectedAreas, true), 404);
        $items = $inspection->items()->where('area', $area)->get();
        $nextArea = $this->nextArea($selectedAreas, $area);

        return view('tenant.inspections.inspect', compact('inspection', 'area', 'items', 'nextArea'));
    }

    public function storeArea(Request $request, BookingInspection $inspection, string $area)
    {
        $this->authorizeInspection($inspection);
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.condition' => 'required|in:good,issue,na',
            'items.*.comment' => 'nullable|string|max:1000',
            'pictures.*.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        foreach ($validated['items'] as $itemId => $payload) {
            $item = $inspection->items()->whereKey($itemId)->first();
            if (! $item) {
                continue;
            }

            $pictures = (array) $item->pictures;
            if ($request->hasFile("pictures.$itemId")) {
                foreach ((array) $request->file("pictures.$itemId") as $file) {
                    $pictures[] = $this->uploadOptimizedFile($file, 'booking_inspection_pictures');
                }
            }

            $item->update([
                'condition' => $payload['condition'],
                'comment' => $payload['comment'] ?? null,
                'pictures' => $pictures,
            ]);
        }

        $this->recalculateInspection($inspection);
        $nextArea = $this->nextArea($inspection->selected_areas ?: [], $area);

        return $nextArea
            ? redirect()->route('tenant.inspection.inspect', [$inspection->id, $nextArea])
            : redirect()->route('tenant.inspection.review', $inspection->id);
    }

    public function reviewInspection(BookingInspection $inspection)
    {
        $this->authorizeInspection($inspection);
        $inspection->load('items', 'booking.property.building');

        return view('tenant.inspections.review', compact('inspection'));
    }

    public function notes(BookingInspection $inspection)
    {
        $this->authorizeInspection($inspection);

        return view('tenant.inspections.notes', compact('inspection'));
    }

    public function submitInspection(Request $request, BookingInspection $inspection)
    {
        $this->authorizeInspection($inspection);
        $validated = $request->validate(['notes' => 'nullable|string|max:3000']);

        $this->recalculateInspection($inspection);
        $inspection->update([
            'notes' => $validated['notes'] ?? $inspection->notes,
            'status' => 'submitted',
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
        ]);

        $inspection->booking->histories()->create([
            'title' => $inspection->type_label . ' Inspection Submitted',
            'description' => $inspection->inspection_number . ' submitted with ' . $inspection->issue_items . ' issue(s).',
        ]);

        return redirect()->route('tenant.inspection.submitted', $inspection->id);
    }

    public function submitted(BookingInspection $inspection)
    {
        $this->authorizeInspection($inspection);

        return view('tenant.inspections.submitted', compact('inspection'));
    }

    private function authorizeBooking(Booking $booking): void
    {
        abort_unless($booking->tenant_id === Auth::id(), 403);
    }

    private function authorizeInspection(BookingInspection $inspection): void
    {
        $inspection->loadMissing('booking');
        $this->authorizeBooking($inspection->booking);
    }

    private function inspectionTemplate(Booking $booking): array
    {
        $property = $booking->property;
        $bedrooms = max(0, (int) ($property?->bedrooms ?? 0));
        $bathrooms = max(1, (int) ($property?->bathrooms ?? 1));
        $areas = [
            'Living Room' => ['Floor/Walls', 'Sofa/Chairs', 'TV/Remote', 'Lights', 'AC/Cooling'],
            'Kitchen' => ['Refrigerator', 'Microwave', 'Cooker/Oven', 'Sink/Tap', 'Cabinets'],
        ];

        if ($bedrooms === 0) {
            $areas['Studio Sleeping Area'] = ['Bed/Mattress', 'Wardrobe', 'Bed Linen', 'Lights', 'AC/Cooling'];
        } else {
            for ($i = 1; $i <= $bedrooms; $i++) {
                $areas['Bedroom ' . $i] = ['Bed/Mattress', 'Wardrobe', 'Bed Linen', 'Lights', 'AC/Cooling'];
            }
        }

        for ($i = 1; $i <= $bathrooms; $i++) {
            $areas['Bathroom ' . $i] = ['Shower', 'Toilet', 'Wash Basin', 'Mirror', 'Water Pressure'];
        }

        $featureText = strtolower(json_encode([
            $property?->amenities,
            $property?->additional_features,
            $property?->description,
            $property?->category,
        ]));
        if (str_contains($featureText, 'balcony')) {
            $areas['Balcony'] = ['Door/Lock', 'Floor', 'Furniture', 'Railing', 'Lights'];
        }

        $areas['Extra Items'] = ['WiFi Router', 'Smart Lock/Keys', 'Iron/Hair Dryer', 'Safety Equipment'];

        return $areas;
    }

    private function nextArea(array $areas, string $current): ?string
    {
        $index = array_search($current, $areas, true);
        return $index === false ? null : ($areas[$index + 1] ?? null);
    }

    private function recalculateInspection(BookingInspection $inspection): void
    {
        $items = $inspection->items;
        $inspection->update([
            'total_items' => $items->count(),
            'good_items' => $items->where('condition', 'good')->count(),
            'issue_items' => $items->where('condition', 'issue')->count(),
            'na_items' => $items->where('condition', 'na')->count(),
        ]);
    }

    private function nextInspectionNumber(): string
    {
        do {
            $number = 'INSP-' . now()->format('Ymd') . '-' . strtoupper(substr((string) Str::uuid(), 0, 4));
        } while (BookingInspection::where('inspection_number', $number)->exists());

        return $number;
    }

    private function uploadOptimizedFile($file, string $folder): string
    {
        if (MediaStorage::disk() !== 'public') {
            return MediaStorage::store($file, $folder);
        }

        $datedFolder = MediaStorage::datedFolder($folder);
        $destination = public_path($datedFolder);
        if (! file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagewebp')) {
            $image = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                'png' => @imagecreatefrompng($file->getRealPath()),
                'webp' => @imagecreatefromwebp($file->getRealPath()),
                default => null,
            };

            if ($image) {
                $width = imagesx($image);
                $height = imagesy($image);
                $maxWidth = 1280;
                if ($width > $maxWidth) {
                    $newWidth = $maxWidth;
                    $newHeight = (int) round($height * ($maxWidth / $width));
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resized;
                }

                $filename = MediaStorage::trackedFilename($file, 'webp');
                imagewebp($image, $destination . DIRECTORY_SEPARATOR . $filename, 78);
                imagedestroy($image);

                return $datedFolder . '/' . $filename;
            }
        }

        $filename = MediaStorage::trackedFilename($file, $extension);
        $file->move($destination, $filename);

        return $datedFolder . '/' . $filename;
    }
}
