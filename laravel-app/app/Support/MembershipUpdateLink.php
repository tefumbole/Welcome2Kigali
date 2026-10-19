<?php

namespace App\Support;

use App\MembershipApplication;

class MembershipUpdateLink
{
    public static function token(MembershipApplication $application): string
    {
        return substr(hash_hmac(
            'sha256',
            'membership-update|'.$application->id.'|'.$application->reference,
            (string) config('app.key')
        ), 0, 24);
    }

    public static function url(MembershipApplication $application): string
    {
        return url('/membership/update/'.$application->reference.'/'.self::token($application));
    }

    public static function find($reference, $token)
    {
        $application = MembershipApplication::where('reference', (string) $reference)->first();
        if (! $application || ! hash_equals(self::token($application), (string) $token)) {
            return null;
        }

        return $application;
    }
}
