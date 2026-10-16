<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class StaffAccess
{
    /**
     * Accounts that always have full system access: Admin, System, Owner, Super Admin.
     * Welcome 2 Kigali Admin is role_id 4 (roles 1–2 are internship), so never key off id <= 2.
     */
    public static function isSuperAdmin($user = null)
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return false;
        }

        $roleName = strtolower(trim((string) DB::table('roles')->where('id', $user->role_id)->value('name')));
        if (in_array($roleName, ['admin', 'owner', 'super admin', 'super_admin', 'system'], true)) {
            return true;
        }

        $idents = [
            strtolower(trim((string) ($user->name ?? ''))),
            strtolower(trim((string) ($user->email ?? ''))),
        ];
        if (isset($user->username)) {
            $idents[] = strtolower(trim((string) $user->username));
        }
        foreach ($idents as $ident) {
            if ($ident === 'admin' || $ident === 'system') {
                return true;
            }
            if (strpos($ident, 'admin@') === 0 || strpos($ident, 'system@') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admin / Owner (or equivalently named roles) may manage Site Content and catalog settings.
     */
    public static function canManageSite($user = null)
    {
        return self::isSuperAdmin($user);
    }

    /**
     * Site Content admin (pages, menus, gallery, leaders) — same module as BeyondTechWorld.
     * Super roles always can; other roles need the site_content permission.
     */
    public static function canManageSiteContent($user = null)
    {
        if (self::canManageSite($user)) {
            return true;
        }
        $user = $user ?: Auth::user();
        if (! $user) {
            return false;
        }
        try {
            $role = SpatieRole::find($user->role_id);
            return $role && $role->hasPermissionTo('site_content');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Laundry stage pages. Super roles always can.
     * `laundry_module` unlocks every stage; otherwise the exact stage permission is required.
     */
    public static function canLaundry($permission = 'laundry_module', $user = null)
    {
        if (self::canManageSite($user)) {
            return true;
        }
        $user = $user ?: Auth::user();
        if (! $user) {
            return false;
        }
        try {
            $role = SpatieRole::find($user->role_id);
            if (! $role) {
                return false;
            }
            $wanted = array_values(array_unique(array_filter([
                $permission,
                'laundry_module',
            ])));
            foreach ($wanted as $perm) {
                try {
                    if ($role->hasPermissionTo($perm)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function canAnyLaundry($user = null)
    {
        foreach (['laundry_module', 'laundry.received', 'laundry.cleaning', 'laundry.ready', 'laundry.collect'] as $perm) {
            if (self::canLaundry($perm, $user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attach every permission to Admin and System so menus and Spatie checks pass.
     */
    public static function grantAllToSuperRoles()
    {
        if (! class_exists(SpatieRole::class) || ! class_exists(Permission::class)) {
            return;
        }

        $all = Permission::all();
        if ($all->isEmpty()) {
            return;
        }

        $adminRole = null;
        $systemRole = null;
        foreach (['Admin', 'System', 'Owner', 'Super Admin'] as $name) {
            $role = SpatieRole::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if (! $role && in_array($name, ['Admin', 'System'], true)) {
                $payload = [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'is_active')) {
                    $payload['is_active'] = 1;
                }
                $roleId = DB::table('roles')->insertGetId($payload);
                $role = SpatieRole::find($roleId);
            }
            if ($role) {
                $role->syncPermissions($all);
            }
            if ($role && strtolower($name) === 'admin') {
                $adminRole = $role;
            }
            if ($role && strtolower($name) === 'system') {
                $systemRole = $role;
            }
        }

        if ($adminRole && \Illuminate\Support\Facades\Schema::hasTable('users')) {
            DB::table('users')->where(function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['admin'])
                    ->orWhereRaw('LOWER(email) like ?', ['admin@%']);
            })->update(['role_id' => $adminRole->id]);
        }
        if ($systemRole && \Illuminate\Support\Facades\Schema::hasTable('users')) {
            DB::table('users')->where(function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['system'])
                    ->orWhereRaw('LOWER(email) like ?', ['system@%']);
            })->update(['role_id' => $systemRole->id]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
