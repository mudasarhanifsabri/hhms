<?php

namespace Tests\Feature;

use App\Models\BookingInspection;
use App\Models\Property;
use App\Models\User;
use App\Support\InspectionPhotos;
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionPhotoPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_s3_photos_are_embedded_in_pdf(): void
    {
        config(['hhms.media_disk' => 's3']);
        Storage::fake('s3');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['name' => '502', 'landlord_id' => $owner->id]);
        $inspection = BookingInspection::create(['property_id' => $unit->id, 'inspection_number' => 'INSP-PHOTO-QA', 'type' => 'routine', 'status' => 'submitted', 'submitted_at' => now()]);
        $path = MediaStorage::store(UploadedFile::fake()->image('evidence.jpg', 800, 500), 'booking_inspection_pictures');
        $this->assertStringStartsWith('data:image/jpeg;base64,', InspectionPhotos::thumbnail($path));
        $this->assertNull(InspectionPhotos::thumbnail('missing.jpg'));
        $this->assertNull(InspectionPhotos::thumbnail('../secret'));
        $inspection->items()->create(['area' => 'Living Room', 'item' => 'Dining chair', 'condition' => 'issue', 'comment' => 'Loose leg - review repair.', 'pictures' => [$path, $path, $path], 'sort_order' => 1]);
        $response = $this->actingAs($admin)->get(route('admin.inspection.pdf', $inspection))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('/Subtype /Image', $response->getContent());
        if ($path = getenv('INSPECTION_PDF_QA')) {
            file_put_contents($path, $response->getContent());
        }
    }
}
