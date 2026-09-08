<?php

namespace App\Support;

/**
 * Local-only auth shortcuts. Never active when APP_ENV is not "local".
 */
class LocalDevAuth
{
    public static function skipStaffOtp()
    {
        if (config('app.env') === 'local') {
            return true;
        }

        return filter_var(env('BEYOND_SKIP_OTP', false), FILTER_VALIDATE_BOOLEAN);
    }
}
