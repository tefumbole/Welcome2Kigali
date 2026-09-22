<?php

use App\Support\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class GrantSuperadminAllPermissions extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        try {
            StaffAccess::grantAllToSuperRoles();
        } catch (\Throwable $e) {
            // Non-fatal: Role Permission UI still works via isSuperAdmin().
        }
    }

    public function down()
    {
        // Keep full Admin/System permissions; do not strip them on rollback.
    }
}
