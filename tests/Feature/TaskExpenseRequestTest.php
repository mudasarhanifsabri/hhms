<?php

namespace Tests\Feature;

use App\Models\BookingTask;
use App\Models\Expense;
use App\Models\Property;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskExpenseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_request_is_draft_s3_uploaded_and_not_a_payment(): void
    {
        config(['hhms.media_disk' => 's3', 'hhms.s3_prefix' => 'HHMS', 'filesystems.disks.s3.bucket' => 'test-bucket']);
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(fn ($path, $expiry, $options) => 'https://test-bucket.example/'.$path.'?signed=test');
        $staff = User::factory()->create(['role' => 'maintainer']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => '502']);
        $task = BookingTask::create(['property_id' => $property->id, 'assigned_to' => $staff->id, 'task_number' => 'TSK-EXP', 'type' => 'maintenance', 'priority' => 'medium', 'title' => 'Repair', 'status' => 'completed']);
        $data = ['submission_id' => (string) Str::uuid(), 'expense_date' => today()->toDateString(), 'supplier' => 'Repair supplier', 'amount' => 150, 'payment_status' => 'unpaid', 'description' => 'Replace door handle', 'invoice' => UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf')];
        $this->actingAs($staff)->post(route('maintainer.task.expense-request', $task), $data)->assertRedirect()->assertSessionHasNoErrors();
        $expense = Expense::firstOrFail();
        $this->assertSame('draft', $expense->approval_status);
        $this->assertNull($expense->accounting_entry_id);
        $this->assertNull($expense->paid_from_account_id);
        Storage::disk('s3')->assertExists(MediaStorage::path($expense->invoice_path));
        $this->assertStringContainsString('https://test-bucket.example/HHMS/', MediaStorage::url($expense->invoice_path));
        $this->post(route('maintainer.task.expense-request', $task), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, Expense::count());
        $data['submission_id'] = (string) Str::uuid();
        $data['payment_status'] = 'paid_by_staff';
        $this->post(route('maintainer.task.expense-request', $task), $data)->assertSessionHasErrors('receipt');
        $other = User::factory()->create(['role' => 'maintainer']);
        $this->actingAs($other)->post(route('maintainer.task.expense-request', $task), $data)->assertForbidden();
    }
}
