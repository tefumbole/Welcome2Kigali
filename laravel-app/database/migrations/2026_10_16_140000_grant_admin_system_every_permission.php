<?php

use App\Support\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class GrantAdminSystemEveryPermission extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        StaffAccess::grantAllToSuperRoles();
    }

    public function down()
    {
        // Keep full Admin and System access.
    }
}
