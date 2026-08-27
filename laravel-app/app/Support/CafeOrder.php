<?php

namespace App\Support;

class CafeOrder
{
    const TAKEAWAY_FEE = 1000;

    public static function service($value)
    {
        $raw = strtolower(trim((string) $value));
        if (preg_match('/take\s*-?\s*away/', $raw)) {
            return 'Take away';
        }

        return 'Dine in';
    }

    public static function takeawayFee($service)
    {
        return self::service($service) === 'Take away' ? self::TAKEAWAY_FEE : 0;
    }
}
