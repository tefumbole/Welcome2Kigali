<?php

namespace App\Support;

use App\Membership;

class MembershipQr
{
    public static function token(Membership $membership)
    {
        return $membership->qr_token;
    }

    public static function verifyUrl(Membership $membership)
    {
        return url('/membership/verify/'.$membership->qr_token);
    }

    public static function renewUrl(Membership $membership)
    {
        return url('/membership/renew/'.$membership->renew_token);
    }

    public static function findByScan($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('#/membership/verify/([A-Za-z0-9]+)#', $raw, $m)) {
            return Membership::where('qr_token', $m[1])->first();
        }
        if (preg_match('/^[A-Za-z0-9]{16,64}$/', $raw)) {
            return Membership::where('qr_token', $raw)->orWhere('renew_token', $raw)->first();
        }

        return Membership::where('number', $raw)->first();
    }
}
