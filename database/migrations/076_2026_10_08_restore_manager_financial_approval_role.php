<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $role = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $permissions = collect(['dashboard', 'bookings', 'accounting'])->flatMap(fn ($module) => [
            Permission::firstOrCreate(['name' => $module.'.view', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => $module.'.manage', 'guard_name' => 'web']),
        ]);
        $role->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
