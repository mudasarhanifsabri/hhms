<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $modules = ['dashboard', 'units', 'owners', 'tenants', 'bookings', 'tasks', 'inspections', 'accounting', 'agents', 'maintainers', 'administration'];
        $permissions = collect($modules)->flatMap(fn ($module) => [
            Permission::firstOrCreate(['name' => $module.'.view', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => $module.'.manage', 'guard_name' => 'web']),
        ]);
        Role::firstOrCreate(['name' => 'Backend IT', 'guard_name' => 'web'])->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', 'Backend IT')->where('guard_name', 'web')->first();
        if ($role && ! $role->users()->exists()) $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
