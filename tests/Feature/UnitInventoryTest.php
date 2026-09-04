<?php

namespace Tests\Feature;

use App\Models\BookingInspection;
use App\Models\BookingTask;
use App\Models\Property;
use App\Models\UnitInventoryItem;
use App\Models\User;
use App\Support\UnitInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnitInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function setupInventory(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'maintainer', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => '502']);
        $item = UnitInventoryItem::create(['property_id' => $property->id, 'room' => 'Kitchen', 'name' => 'Glass', 'required' => 6, 'present' => 6, 'damaged' => 0, 'replacement_cost' => 20])->fresh();
        $this->actingAs($admin)->post(route('admin.inspection.request'), ['property_id' => $property->id, 'assigned_to' => $employee->id, 'inspection_type' => 'routine', 'due_date' => today()->toDateString(), 'description' => 'Please count apartment stock'])->assertSessionHasNoErrors()->assertRedirect();
        $task = BookingTask::firstOrFail();
        $this->assertSame((string) $employee->id, $task->assigned_to);

        return compact('admin', 'employee', 'owner', 'property', 'item', 'task');
    }

    public function test_office_request_mobile_counts_and_approval(): void
    {
        extract($this->setupInventory());
        $this->get(route('admin.inventory.index'))->assertOk()->assertSee('Glass');
        $this->actingAs($employee)->get(route('maintainer.task.index', ['inspections_only' => 1]))->assertOk()->assertSee($task->title);
        $this->get(route('maintainer.task.inspection.form', $task))->assertOk()->assertSee('Inventory counts');
        $inspection = $task->inspection->fresh();
        $payload = ['items' => $inspection->items->mapWithKeys(fn ($i) => [$i->id => ['condition' => 'good']])->all(), 'inventory' => [$item->id => ['found' => 5, 'damaged' => 1, 'notes' => 'One chipped glass']]];
        $this->post(route('maintainer.task.inspection.submit', $task), $payload)->assertSessionHasNoErrors();
        $this->assertSame(6, $item->fresh()->present);
        $this->post(route('maintainer.task.inspection.submit', $task), $payload)->assertStatus(422);
        $this->post(route('admin.inventory.approve', $inspection), ['notes' => 'Checked evidence'])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.inspection.show', $inspection))->assertOk()->assertSee('No baseline');
        $this->post(route('admin.inventory.approve', $inspection), ['notes' => 'Checked evidence'])->assertSessionHasNoErrors();
        $this->assertSame(5, $item->fresh()->present);
        $this->assertSame(1, $item->fresh()->damaged);
        $this->post(route('admin.inventory.approve', $inspection), ['notes' => 'Checked evidence'])->assertSessionHasErrors('inventory');
        $this->assertSame(1, DB::table('unit_inventory_movements')->count());
    }

    public function test_only_assignee_can_inspect_and_invalid_counts_are_atomic(): void
    {
        extract($this->setupInventory());
        $other = User::factory()->create(['role' => 'maintainer']);
        $this->actingAs($other)->get(route('maintainer.task.inspection.form', $task))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.inventory.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('maintainer.task.inspection.form', $task))->assertOk();
        $inspection = $task->inspection->fresh();
        $payload = ['items' => $inspection->items->mapWithKeys(fn ($i) => [$i->id => ['condition' => 'good']])->all(), 'inventory' => [$item->id => ['found' => 1, 'damaged' => 2]]];
        $this->post(route('maintainer.task.inspection.submit', $task), $payload)->assertSessionHasErrors();
        $this->assertSame('draft', $inspection->fresh()->status);
        $this->assertSame(6, $item->fresh()->present);
    }

    public function test_stock_change_blocks_stale_approval_and_transfers_conserve_stock(): void
    {
        extract($this->setupInventory());
        $inspection = $task->inspection;
        UnitInventory::snapshot($inspection);
        UnitInventory::submit($inspection, [$item->id => ['found' => 5, 'damaged' => 0]]);
        $target = Property::create(['landlord_id' => $owner->id, 'name' => '503']);
        $this->post(route('admin.inventory.move', $item), ['type' => 'transfer', 'quantity' => 2, 'target_property_id' => $target->id, 'reason' => 'Transfer spare stock'])->assertSessionHasNoErrors();
        $this->assertSame(6, (int) UnitInventoryItem::sum('present'));
        $this->post(route('admin.inventory.approve', $inspection), ['notes' => 'Approve old count'])->assertSessionHasErrors('inventory');
        $this->assertSame(4, $item->fresh()->present);
        $this->post(route('admin.inventory.move', $item), ['type' => 'dispose', 'quantity' => 1, 'reason' => 'No damaged stock'])->assertSessionHasErrors('quantity');
        $this->assertSame(4, $item->fresh()->present);
    }

    public function test_templates_do_not_copy_stock_or_overwrite_requirements(): void
    {
        extract($this->setupInventory());
        $this->post(route('admin.inventory.template'), ['property_id' => $property->id, 'action' => 'save', 'name' => 'Custom 1 BHK'])->assertRedirect()->assertSessionHasNoErrors();
        $template = DB::table('unit_inventory_templates')->where('name', 'Custom 1 BHK')->first();
        $target = Property::create(['landlord_id' => $owner->id, 'name' => '504']);
        $data = ['property_id' => $target->id, 'action' => 'apply', 'template_id' => $template->id];
        $this->post(route('admin.inventory.template'), $data)->assertSessionHasNoErrors();
        $copy = UnitInventoryItem::where('property_id', $target->id)->firstOrFail();
        $this->assertSame(0, $copy->present);
        $this->assertSame(6, $copy->required);
        $copy->update(['required' => 10]);
        $this->post(route('admin.inventory.template'), $data)->assertSessionHasNoErrors();
        $this->assertSame(10, $copy->fresh()->required);
    }

    public function test_damage_estimate_uses_approved_same_booking_baseline(): void
    {
        extract($this->setupInventory());
        $booking = \App\Models\Booking::create(['property_id' => $property->id, 'booking_reference' => 'BK-INVENTORY', 'invoice_number' => 'INV-INVENTORY', 'guest_name' => 'Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '123', 'guest_passport_id_no' => 'PASS', 'check_in' => '2026-09-01', 'check_out' => '2026-09-30', 'rent_amount' => 100, 'total_amount' => 100, 'status' => 'confirmed']);
        $checkin = $task->inspection;
        $checkin->update(['booking_id' => $booking->id, 'type' => 'check_in']);
        UnitInventory::snapshot($checkin);
        UnitInventory::submit($checkin, [$item->id => ['found' => 5, 'damaged' => 1]]);
        UnitInventory::approve($checkin, 'Approved initial count');
        $checkout = BookingInspection::create(['booking_id' => $booking->id, 'property_id' => $property->id, 'inspection_number' => 'CHK-OUT-INV', 'type' => 'check_out', 'status' => 'draft']);
        UnitInventory::snapshot($checkout);
        UnitInventory::submit($checkout, [$item->id => ['found' => 4, 'damaged' => 2]]);
        UnitInventory::approve($checkout, 'Reviewed checkout evidence', true);
        $row = json_decode(DB::table('unit_inventory_reviews')->where('inspection_id', $checkout->id)->value('rows'), true)[0];
        $this->assertSame(1, $row['new_missing']);
        $this->assertSame(1, $row['new_damaged']);
        $this->assertEquals(40, $row['estimate']);
        $this->assertSame(1, BookingTask::where('category', 'inventory')->count());
        \Illuminate\Support\Facades\Storage::fake('public');
        \App\Models\BookingDepositEntry::create(['booking_id' => $booking->id, 'kind' => 'received', 'amount' => 100, 'entry_date' => today(), 'submission_id' => 'test-held']);
        $this->get(route('admin.inspection.show', $checkout))->assertOk()->assertSee('Assess damage');
        $payload = ['reason' => 'Reviewed guest responsibility', 'charges' => [$item->id => ['amount' => 25, 'reason' => 'Repair cost lower than replacement']]];
        $this->post(route('admin.inventory.assess', $checkout), $payload)->assertSessionHasErrors('charges');
        $payload['charges'][$item->id]['evidence'] = \Illuminate\Http\UploadedFile::fake()->create('damage.pdf', 10, 'application/pdf');
        $this->post(route('admin.inventory.assess', $checkout), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $proposal = \App\Models\BookingDepositRefund::firstOrFail();
        $this->assertSame('pending', $proposal->status);
        $this->assertEquals(25, $proposal->deduction_amount);
        $this->assertEquals(40, $proposal->deductions[0]['estimated_amount']);
        $this->assertEquals(100, \App\Support\DepositWallet::totals($booking)['held']);
        $this->post(route('admin.inventory.assess', $checkout), $payload)->assertSessionHasErrors('charges');
        $this->assertSame(1, \App\Models\BookingDepositRefund::count());
    }

    public function test_compact_screens_templates_and_replacement(): void
    {
        extract($this->setupInventory());
        foreach (['apartments', 'unit', 'templates', 'history'] as $screen) {
            $this->get(route('admin.inventory.index', ['screen' => $screen]))->assertOk();
        }
        $this->assertSame(3, DB::table('unit_inventory_templates')->count());
        $item->update(['damaged' => 2]);
        $this->post(route('admin.inventory.move', $item), ['type' => 'replace', 'quantity' => 1, 'reason' => 'Replace broken glass'])->assertSessionHasNoErrors();
        $this->assertSame(6, $item->fresh()->present);
        $this->assertSame(1, $item->fresh()->damaged);
        $this->assertSame(2, DB::table('unit_inventory_movements')->count());
        $this->post(route('admin.inventory.move', $item), ['type' => 'replace', 'quantity' => 2, 'reason' => 'Too many replacements'])->assertSessionHasErrors();
        $this->assertSame(2,DB::table('unit_inventory_movements')->count());
    }
}
