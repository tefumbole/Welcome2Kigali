<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddMembershipsDeletePermission extends Migration
{
    public function up()
    {
        $names = ['memberships.delete', 'courses.delete'];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roles = Role::whereIn('name', ['Admin', 'admin', 'Owner', 'Super Admin', 'System'])->get();
        if ($roles->isEmpty()) {
            $roles = Role::whereIn('id', [1, 2, 4])->get();
        }
        foreach ($roles as $role) {
            foreach ($names as $name) {
                try {
                    $role->givePermissionTo($name);
                } catch (\Exception $e) {
                }
            }
        }
    }

    public function down()
    {
    }
}
