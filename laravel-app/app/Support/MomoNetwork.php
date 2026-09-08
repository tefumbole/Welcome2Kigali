<?php

namespace App\Support;

/**
 * Normalize MSISDN and map MTN / Airtel / Orange to PawaPay provider codes.
 */
class MomoNetwork
{
    public static function normalize($phone, $defaultCountry = '250')
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return '';
        }
        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9 && ($digits[0] === '7' || $digits[0] === '6')) {
            $cc = $digits[0] === '7' ? '250' : $defaultCountry;
            $digits = $cc.$digits;
        }
        if (strlen($digits) === 10 && $digits[0] === '0') {
            $digits = $defaultCountry.substr($digits, 1);
        }

        return $digits;
    }

    public static function currencyForPhone($msisdn)
    {
        if (strpos((string) $msisdn, '237') === 0) {
            return 'XAF';
        }

        return 'RWF';
    }

    public static function countryForPhone($msisdn)
    {
        if (strpos((string) $msisdn, '237') === 0) {
            return 'CMR';
        }

        return 'RWA';
    }

    /**
     * @param  string  $hint  mtn|airtel|orange|momo|auto
     */
    public static function provider($msisdn, $hint = 'auto')
    {
        $hint = strtolower(trim((string) $hint));
        $country = self::countryForPhone($msisdn);

        if (in_array($hint, ['mtn', 'momo', 'mtn_momo'], true)) {
            return $country === 'CMR' ? 'MTN_MOMO_CMR' : 'MTN_MOMO_RWA';
        }
        if (in_array($hint, ['airtel', 'airtel_money'], true)) {
            return 'AIRTEL_RWA';
        }
        if (in_array($hint, ['orange', 'om'], true)) {
            return $country === 'CMR' ? 'ORANGE_CMR' : 'AIRTEL_RWA';
        }

        return self::detectFromPrefix($msisdn);
    }

    public static function detectFromPrefix($msisdn)
    {
        $msisdn = (string) $msisdn;
        $national = $msisdn;
        if (strpos($msisdn, '250') === 0) {
            $national = substr($msisdn, 3);
            $p2 = substr($national, 0, 2);
            if (in_array($p2, ['78', '79'], true)) {
                return 'MTN_MOMO_RWA';
            }
            if (in_array($p2, ['72', '73'], true)) {
                return 'AIRTEL_RWA';
            }

            return 'MTN_MOMO_RWA';
        }
        if (strpos($msisdn, '237') === 0) {
            $national = substr($msisdn, 3);
            $p2 = substr($national, 0, 2);
            $p3 = substr($national, 0, 3);
            if (in_array($p2, ['69'], true) || in_array($p3, ['655', '656', '657', '658', '659'], true)) {
                return 'ORANGE_CMR';
            }

            return 'MTN_MOMO_CMR';
        }

        return 'MTN_MOMO_RWA';
    }

    public static function payingMethodLabel($provider)
    {
        if (strpos((string) $provider, 'ORANGE') !== false) {
            return 'Orange Money';
        }
        if (strpos((string) $provider, 'AIRTEL') !== false) {
            return 'Airtel Money';
        }

        return 'MTN MoMo';
    }
}
