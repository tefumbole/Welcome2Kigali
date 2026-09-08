<?php

namespace App\Support;

class CafeMenuQr
{
    public static function menuUrl()
    {
        return url('/menu');
    }

    public static function dataUri($size = 720)
    {
        return OnlineInvitationQr::dataUri(self::menuUrl(), (int) $size, 1);
    }

    public static function pngBinary($size = 720)
    {
        return OnlineInvitationQr::pngBinary(self::menuUrl(), (int) $size, 1);
    }
}
