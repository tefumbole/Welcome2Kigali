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

        foreach (['Admin', 'System', 'Owner', 'Super Admin'] as $name) {
            $role = SpatieRole::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if (! $role && in_array($name, ['Admin', 'System'], true)) {
                $role = SpatieRole::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'is_active') && empty($role->is_active)) {
                    DB::table('roles')->where('id', $role->id)->update(['is_active' => 1]);
                }
            }
            if ($role) {
                $role->syncPermissions($all);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
