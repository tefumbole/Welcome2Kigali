<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MembershipFaceScan
{
    const TTL_MINUTES = 45;

    public static function makeToken()
    {
        return Str::random(40);
    }

    public static function touch($token)
    {
        $row = self::get($token);
        if (! is_array($row)) {
            $row = ['status' => 'waiting'];
        }
        self::put($token, $row);

        return $row;
    }

    public static function get($token)
    {
        $token = preg_replace('/[^A-Za-z0-9]/', '', (string) $token);
        if (strlen($token) < 16) {
            return null;
        }

        return Cache::get(self::key($token));
    }

    public static function complete($token, $image)
    {
        $image = (string) $image;
        if (strpos($image, 'data:image/jpeg;base64,') !== 0 || strlen($image) > 400000) {
            return false;
        }
        self::put($token, [
            'status' => 'ready',
            'image' => $image,
            'live' => 1,
        ]);

        return true;
    }

    public static function url($token)
    {
        return url('/membership/face-scan/'.$token);
    }

    protected static function put($token, array $row)
    {
        Cache::put(self::key($token), $row, self::TTL_MINUTES);
    }

    protected static function key($token)
    {
        return 'membership_face_scan_'.$token;
    }
}
