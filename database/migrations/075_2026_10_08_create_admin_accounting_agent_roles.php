<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $allModules = ['dashboard', 'units', 'owners', 'tenants', 'bookings', 'tasks', 'inspections', 'accounting', 'agents', 'maintainers', 'administration'];
        $permissions = fn (array $modules, bool $manage = true) => collect($modules)->flatMap(function ($module) use ($manage) {
            $items = [Permission::firstOrCreate(['name' => $module.'.view', 'guard_name' => 'web'])];
            if ($manage) $items[] = Permission::firstOrCreate(['name' => $module.'.manage', 'guard_name' => 'web']);
            return $items;
        });

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])->syncPermissions($permissions($allModules));
        Role::firstOrCreate(['name' => 'Accounting', 'guard_name' => 'web'])->syncPermissions($permissions(['dashboard', 'bookings', 'accounting']));
        Role::firstOrCreate(['name' => 'Agent', 'guard_name' => 'web'])->syncPermissions(
            $permissions(['dashboard', 'bookings', 'agents'])->merge($permissions(['units', 'tenants'], false))
        );
        $legacyManager = Role::where('name', 'Manager')->where('guard_name', 'web')->first();
        if ($legacyManager && ! $legacyManager->users()->exists()) $legacyManager->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['Admin', 'Accounting', 'Agent'] as $name) {
            $role = Role::where('name', $name)->where('guard_name', 'web')->first();
            if ($role && ! $role->users()->exists()) $role->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
