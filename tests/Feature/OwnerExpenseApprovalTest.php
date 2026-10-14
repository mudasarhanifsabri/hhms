<?php

namespace Tests\Feature;

use App\Mail\OwnerExpenseApprovalMail;
use App\Models\Expense;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerExpenseApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_email_an_expense_approval_request_to_owner(): void
    {
        Mail::fake();
        Storage::fake('public');
        config()->set('hhms.media_disk', 'public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'email' => 'owner-approval@example.com']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Approval Unit 101', 'status' => 'vacant']);
        $expense = $this->expense($owner, $property);

        $this->actingAs($admin)->post(route('admin.accounting.expenses.owner-approval', $expense), [
            'remarks' => 'Air conditioner compressor requires replacement.',
            'deadline' => '2026-10-20',
            'quotation' => UploadedFile::fake()->create('quotation.pdf', 100, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $expense->refresh();
        $this->assertSame('pending', $expense->owner_approval_status);
        $this->assertTrue($expense->owner_billable);
        $this->assertNotNull($expense->owner_approval_sent_at);
        $this->assertNotNull($expense->quotation_path);
        Mail::assertSent(OwnerExpenseApprovalMail::class, fn ($mail) => $mail->hasTo('owner-approval@example.com'));
    }

    public function test_owner_can_approve_and_only_one_decision_is_recorded(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Decision Unit 202', 'status' => 'vacant']);
        $expense = $this->expense($owner, $property, ['owner_approval_status' => 'pending']);

        $this->actingAs($owner)->post(route('landlord.expenses.approval.decide', $expense), [
            'decision' => 'approved',
        ])->assertRedirect()->assertSessionHas('success');
        $this->post(route('landlord.expenses.approval.decide', $expense), [
            'decision' => 'rejected', 'remarks' => 'Changed my mind',
        ])->assertRedirect()->assertSessionHas('warning');

        $expense->refresh();
        $this->assertSame('approved', $expense->owner_approval_status);
        $this->assertSame('pending', $expense->approval_status);
        $this->assertSame(1, $expense->audits()->where('action', 'owner_approved')->count());
    }

    public function test_owner_can_choose_to_arrange_and_pay_the_expense(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Self Pay Unit', 'status' => 'vacant']);
        $expense = $this->expense($owner, $property, ['owner_approval_status' => 'pending', 'owner_billable' => true]);

        $this->actingAs($owner)->post(route('landlord.expenses.approval.decide', $expense), [
            'decision' => 'owner_paying', 'remarks' => 'I will use my own contractor.',
        ])->assertRedirect()->assertSessionHas('success');

        $expense->refresh();
        $this->assertSame('owner_paying', $expense->owner_approval_status);
        $this->assertFalse($expense->owner_billable);
        $this->assertSame('owner', $expense->responsibility);
    }

    private function expense(User $owner, Property $property, array $overrides = []): Expense
    {
        return Expense::create(array_merge([
            'expense_no' => 'EXP-APPROVAL-'.fake()->unique()->numerify('####'),
            'expense_date' => '2026-10-14', 'category' => 'maintenance',
            'property_id' => $property->id, 'landlord_id' => $owner->id,
            'responsibility' => 'company', 'owner_billable' => false,
            'net_amount' => 1000, 'vat_rate' => 5, 'vat_amount' => 50, 'gross_amount' => 1050,
            'approval_status' => 'pending', 'description' => 'AC compressor replacement',
        ], $overrides));
    }
}
