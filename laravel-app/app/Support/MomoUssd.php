<?php

namespace App\Support;

class MomoUssd
{
    const MERCHANT = '100236';

    public static function amount($amount): int
    {
        return max(0, (int) round((float) $amount));
    }

    public static function code($amount): string
    {
        return '*182*8*1*'.self::MERCHANT.'*'.self::amount($amount).'#';
    }

    public static function telUri($amount): string
    {
        return 'tel:'.str_replace('#', '%23', self::code($amount));
    }

    public static function qrDataUri($amount): string
    {
        return QrPng::dataUri(self::telUri($amount), 'M', 8);
    }

    public static function token($purpose, $id, $amount): string
    {
        return substr(hash_hmac(
            'sha256',
            'momo-ussd|'.$purpose.'|'.$id.'|'.self::amount($amount),
            (string) config('app.key')
        ), 0, 20);
    }

    public static function payUrl($purpose, $id, $amount): string
    {
        $amount = self::amount($amount);

        return url('/pay/momo/'.$purpose.'/'.$id.'/'.$amount.'/'.self::token($purpose, $id, $amount));
    }

    public static function isValid($purpose, $id, $amount, $token): bool
    {
        return hash_equals(self::token($purpose, $id, $amount), (string) $token);
    }

    public static function sendLink($phone, $name, $label, $purpose, $id, $amount)
    {
        if (! trim((string) $phone)) {
            return false;
        }
        $url = self::payUrl($purpose, $id, $amount);
        $msg = 'Hello '.($name ?: 'there').",\n\n"
            .'Please pay '.number_format(self::amount($amount)).' RWF to Welcome 2 Kigali Expats Club'.($label ? ' ('.$label.')' : '').".\n\n"
            ."Tap the link, then tap Pay to dial MoMo:\n".$url."\n\n"
            .'Or dial '.self::code($amount)."\n\n"
            .'Welcome 2 Kigali Expats Club';
        try {
            app(\App\Services\Messaging\NotificationRouter::class)->sendWhatsAppText($phone, $msg);

            return true;
        } catch (\Throwable $e) {
            \Log::warning('[momo-ussd] link send failed: '.$e->getMessage());

            return false;
        }
    }
}
