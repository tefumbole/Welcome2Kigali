<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffAccess
{
    /**
     * Admin / Owner (or equivalently named roles).
     * Beyond uses role_id 1–2; Welcome 2 Kigali may assign Admin a later id.
     */
    public static function canManageSite($user = null)
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return false;
        }
        if (in_array((int) $user->role_id, [1, 2], true)) {
            return true;
        }

        $name = strtolower(trim((string) DB::table('roles')->where('id', $user->role_id)->value('name')));

        return in_array($name, ['admin', 'owner', 'super admin', 'super_admin'], true);
    }
}
