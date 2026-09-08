<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MembershipIdScan
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

    public static function complete($token, array $data)
    {
        $row = [
            'status' => 'ready',
            'full_name' => isset($data['full_name']) ? $data['full_name'] : '',
            'id_number' => isset($data['id_number']) ? $data['id_number'] : '',
            'id_type' => isset($data['id_type']) ? $data['id_type'] : '',
            'date_of_birth' => isset($data['date_of_birth']) ? $data['date_of_birth'] : '',
            'expires_on' => isset($data['expires_on']) ? $data['expires_on'] : '',
            'nationality' => isset($data['nationality']) ? $data['nationality'] : '',
        ];
        self::put($token, $row);
    }

    public static function url($token)
    {
        return url('/membership/id-scan/'.$token);
    }

    protected static function put($token, array $row)
    {
        Cache::put(self::key($token), $row, self::TTL_MINUTES);
    }

    protected static function key($token)
    {
        return 'membership_id_scan_'.$token;
    }
}
