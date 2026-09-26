<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlController extends Controller
{
    public const MODULES = [
        'dashboard' => ['Dashboard', 'ri-dashboard-line'], 'units' => ['Units & Buildings', 'ri-building-line'],
        'owners' => ['Owners / Landlords', 'ri-user-star-line'], 'tenants' => ['Tenants / Guests', 'ri-user-line'],
        'bookings' => ['Bookings & Invoices', 'ri-calendar-check-line'], 'tasks' => ['Tasks', 'ri-task-line'],
        'inspections' => ['Inspections & Inventory', 'ri-search-eye-line'], 'accounting' => ['Accounting', 'ri-calculator-line'],
        'agents' => ['Agents', 'ri-team-line'], 'maintainers' => ['Maintainers', 'ri-tools-line'],
        'administration' => ['Administration & Settings', 'ri-shield-keyhole-line'],
    ];

    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();
        $staff = User::where('role', 'admin')->with('roles')->orderBy('name')->get();

        return view('admin.access-control.index', ['roles' => $roles, 'staff' => $staff, 'modules' => self::MODULES]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($this->permissionNames($data));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', $role->name.' role created.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'Super Administrator', 422, 'The protected Super Administrator role cannot be changed.');
        $data = $this->validateRole($request, $role);
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($this->permissionNames($data));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', $role->name.' permissions updated.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        abort_if($role->name === 'Super Administrator', 422, 'The protected Super Administrator role cannot be deleted.');
        abort_if($role->users()->exists(), 422, 'Move staff out of this role before deleting it.');
        $name = $role->name;
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', $name.' role deleted.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50', 'password' => ['required', 'confirmed', Password::defaults()],
            'role_id' => 'required|exists:roles,id',
        ]);
        $role = Role::findOrFail($data['role_id']);
        DB::transaction(function () use ($data, $role) {
            $user = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null,
                'password' => $data['password'], 'role' => 'admin', 'is_active' => true]);
            // The User model grants new admin accounts the protected role as a
            // safe default. Replace that default with the explicitly selected
            // staff role from this form.
            $user->syncRoles([$role]);
        });

        return back()->with('success', 'Staff account created and assigned to '.$role->name.'.');
    }

    public function updateUser(Request $request, User $staff): RedirectResponse
    {
        $user = $staff;
        abort_unless($user->role === 'admin', 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:50',
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'required|boolean',
        ]);
        abort_if($user->is($request->user()) && ! $data['is_active'], 422, 'You cannot disable your own account.');
        $role = Role::findOrFail($data['role_id']);
        $this->guardLastSuperAdmin($user, $role->name === 'Super Administrator' && (bool) $data['is_active']);
        DB::transaction(function () use ($user, $data, $role) {
            $attributes = [
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ];
            if (filled($data['password'] ?? null)) $attributes['password'] = $data['password'];
            $user->forceFill($attributes)->save();
            $user->syncRoles([$role]);
        });

        return back()->with('success', $user->name.' access updated.');
    }

    public function destroyUser(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'admin', 404);
        abort_if($staff->is($request->user()), 422, 'You cannot delete your own account.');
        $this->guardLastSuperAdmin($staff, false);
        $name = $staff->name;

        DB::transaction(function () use ($staff) {
            $staff->syncRoles([]);
            $staff->delete();
        });

        return back()->with('success', $name.' was removed from staff access.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => 'nullable|array', 'permissions.*' => ['string', Rule::in(array_keys(self::MODULES))],
            'manage' => 'nullable|array', 'manage.*' => ['string', Rule::in(array_keys(self::MODULES))],
        ]);
    }

    private function permissionNames(array $data): array
    {
        $view = collect($data['permissions'] ?? []);
        $manage = collect($data['manage'] ?? []);
        return $view->merge($manage)->unique()->flatMap(fn ($module) => [$module.'.view'])
            ->merge($manage->map(fn ($module) => $module.'.manage'))->unique()->values()->all();
    }

    private function guardLastSuperAdmin(User $user, bool $willRemainSuper): void
    {
        if (! $user->hasRole('Super Administrator') || $willRemainSuper) return;
        abort_if(User::where('role', 'admin')->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'Super Administrator'))->count() <= 1,
            422, 'At least one active Super Administrator is required.');
    }
}
