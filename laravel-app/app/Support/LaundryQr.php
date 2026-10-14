<?php

namespace App\Support;

class LaundryQr
{
    public static function scanUrl($token)
    {
        return url('/laundry/scan/'.ltrim((string) $token, '/'));
    }

    public static function dataUri($token, $size = 280)
    {
        return OnlineInvitationQr::dataUri(self::scanUrl($token), $size);
    }

    public static function pngBinary($token, $size = 280)
    {
        return OnlineInvitationQr::pngBinary(self::scanUrl($token), $size);
    }
}
