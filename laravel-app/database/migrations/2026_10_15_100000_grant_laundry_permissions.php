<?php

use App\Support\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantLaundryPermissions extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $names = [
            'laundry_module',
            'laundry.received',
            'laundry.cleaning',
            'laundry.ready',
            'laundry.collect',
        ];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        try {
            StaffAccess::grantAllToSuperRoles();
        } catch (\Throwable $e) {
        }

        $roles = Role::query()->get();
        foreach ($roles as $role) {
            $roleName = strtolower(trim((string) $role->name));
            $isAdmin = in_array($roleName, ['admin', 'owner', 'super admin', 'super_admin', 'system'], true)
                || (int) $role->id === 4;
            if (! $isAdmin) {
                continue;
            }
            foreach ($names as $name) {
                try {
                    if (! $role->hasPermissionTo($name)) {
                        $role->givePermissionTo($name);
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down()
    {
        // Keep laundry permissions on admin roles.
    }
}
