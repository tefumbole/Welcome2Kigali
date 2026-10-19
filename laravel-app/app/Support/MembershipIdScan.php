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

    public static function bindApplication($token, $application)
    {
        $prev = self::get($token);
        if (! is_array($prev)) {
            $prev = ['status' => 'waiting'];
        }
        $prev['application_id'] = $application->id;
        $prev['expected_name'] = (string) $application->full_name;
        if (empty($prev['status'])) {
            $prev['status'] = 'waiting';
        }
        self::put($token, $prev);

        return $prev;
    }

    public static function complete($token, array $data)
    {
        $prev = self::get($token);
        if (! is_array($prev)) {
            $prev = [];
        }
        $row = [
            'status' => 'ready',
            'full_name' => isset($data['full_name']) ? $data['full_name'] : '',
            'id_number' => isset($data['id_number']) ? $data['id_number'] : '',
            'id_type' => isset($data['id_type']) ? $data['id_type'] : '',
            'date_of_birth' => isset($data['date_of_birth']) ? $data['date_of_birth'] : '',
            'issued_on' => isset($data['issued_on']) ? $data['issued_on'] : '',
            'expires_on' => isset($data['expires_on']) ? $data['expires_on'] : '',
            'nationality' => isset($data['nationality']) ? $data['nationality'] : '',
            'application_id' => isset($prev['application_id']) ? $prev['application_id'] : null,
            'expected_name' => isset($prev['expected_name']) ? $prev['expected_name'] : '',
        ];
        if ($row['full_name'] === '' || ! self::isDate($row['issued_on']) || ! self::isDate($row['expires_on'])) {
            return 'incomplete';
        }
        $expected = trim((string) $row['expected_name']);
        if ($expected !== '' && ! self::namesMatch($expected, $row['full_name'])) {
            return 'mismatch';
        }
        self::put($token, $row);
        if (! empty($row['application_id'])) {
            self::writeApplication($row);
        }

        return 'ready';
    }

    public static function namesMatch($expected, $scanned)
    {
        $left = self::nameTokens($expected);
        $right = self::nameTokens($scanned);
        if (! $left || ! $right) {
            return false;
        }
        $short = count($left) <= count($right) ? $left : $right;
        $long = count($left) <= count($right) ? $right : $left;
        $used = [];
        foreach ($short as $token) {
            $found = false;
            foreach ($long as $i => $other) {
                if (isset($used[$i])) {
                    continue;
                }
                if (self::tokenClose($token, $other)) {
                    $used[$i] = true;
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                return false;
            }
        }

        return count($short) >= 2 || (count($short) === 1 && strlen($short[0]) >= 4);
    }

    protected static function isDate($value)
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value);
    }

    protected static function nameTokens($name)
    {
        $name = strtolower(trim((string) $name));
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            if ($ascii !== false) {
                $name = $ascii;
            }
        }
        $name = preg_replace('/[^a-z\s]/', ' ', $name);
        $parts = preg_split('/\s+/', trim((string) $name));
        $out = [];
        foreach ($parts as $part) {
            if (strlen($part) >= 2) {
                $out[] = $part;
            }
        }

        return $out;
    }

    protected static function tokenClose($a, $b)
    {
        if ($a === $b) {
            return true;
        }
        if (strlen($a) < 4 || strlen($b) < 4) {
            return false;
        }

        return levenshtein($a, $b) <= 1;
    }

    protected static function writeApplication(array $row)
    {
        $id = (int) $row['application_id'];
        if ($id < 1) {
            return;
        }
        $payload = \App\Support\SchemaColumns::forTable('membership_applications', [
            'id_type' => $row['id_type'] ?: null,
            'id_number' => $row['id_number'] ?: null,
            'id_expires_on' => $row['expires_on'] ?: null,
            'id_issued_on' => $row['issued_on'] ?: null,
            'nationality' => $row['nationality'] ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        unset($payload['id']);
        if ($payload) {
            \Illuminate\Support\Facades\DB::table('membership_applications')->where('id', $id)->update($payload);
        }
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
