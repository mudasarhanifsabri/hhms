<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_approval_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 40);
            $table->string('status', 20)->default('pending')->index();
            $table->uuid('booking_id')->nullable()->index();
            $table->uuid('booking_invoice_id')->nullable()->index();
            $table->uuid('booking_invoice_payment_id')->nullable()->index();
            $table->json('payload');
            $table->json('before_snapshot')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('request_reason')->nullable();
            $table->uuid('requested_by')->index();
            $table->timestamp('requested_at');
            $table->uuid('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        $permissions = collect(['dashboard', 'bookings', 'accounting'])->flatMap(fn ($module) => [
            Permission::firstOrCreate(['name' => $module.'.view', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => $module.'.manage', 'guard_name' => 'web']),
        ]);
        Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web'])->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_approval_requests');
        $role = Role::where('name', 'Manager')->where('guard_name', 'web')->first();
        if ($role && ! $role->users()->exists()) $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
