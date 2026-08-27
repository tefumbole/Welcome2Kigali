<?php

namespace App\Support;

use App\GeneralSetting;

class SiteBrand
{
    /**
     * Public URL for the logo uploaded in Settings → General.
     * Falls back to the Welcome 2 Kigali branding asset when none is set.
     */
    public static function logoUrl($generalSetting = null)
    {
        $setting = $generalSetting ?: GeneralSetting::latest()->first();
        $fallback = url('public/branding/w2k-logo.png').'?v=4';

        if ($setting && ! empty($setting->site_logo)) {
            $filename = basename((string) $setting->site_logo);
            $path = base_path('public/logo/'.$filename);
            if (is_file($path) && filesize($path) > 0 && filesize($path) <= 800000) {
                return url('public/logo/'.$filename);
            }
        }

        $brandPath = base_path('public/branding/w2k-logo.png');
        if (is_file($brandPath)) {
            return $fallback;
        }

        $markPath = base_path('public/branding/w2k-mark.png');
        if (is_file($markPath)) {
            return url('public/branding/w2k-mark.png');
        }

        return $fallback;
    }

    public static function landingUrl()
    {
        $fallback = url('public/branding/w2k-landing.png').'?v=7';
        $custom = SiteContent::image('home.hero_image', '');
        if ($custom === '' || strpos($custom, 'w2k-logo') !== false || strpos($custom, 'beyond-hero') !== false) {
            return $fallback;
        }

        return $custom;
    }

    public static function markUrl()
    {
        $path = base_path('public/branding/w2k-mark.png');
        if (is_file($path)) {
            return url('public/branding/w2k-mark.png');
        }

        return self::logoUrl();
    }

    public static function siteTitle($generalSetting = null)
    {
        $setting = $generalSetting ?: GeneralSetting::latest()->first();

        return ($setting && ! empty($setting->site_title))
            ? $setting->site_title
            : 'Welcome 2 Kigali Expats Club';
    }

    public static function email()
    {
        $val = SiteContent::text('contact.email', 'info@welcome2kigali.net');
        if ($val === '' || $val === 'hello@welcome2kigali.com' || $val === 'info@beyondtechworld.com') {
            return 'info@welcome2kigali.net';
        }

        return $val;
    }

    public static function websiteLabel()
    {
        $val = SiteContent::text('contact.website', 'www.welcome2kigali.net');
        if ($val === '' || strpos($val, 'welcome2kigali.com') !== false || strpos($val, 'beyondtechworld') !== false) {
            return 'www.welcome2kigali.net';
        }

        return preg_replace('#^https?://#', '', $val);
    }

    public static function phone()
    {
        $val = trim((string) SiteContent::text('contact.phone', ''));
        if ($val === ''
            || preg_match('/675\s*321\s*739|675321739/i', $val)
            || preg_match('/^\+?237/i', $val)
            || preg_match('/7437\s*010300|447437010300/i', $val)
            || preg_match('/^\+?44/i', $val)) {
            return '+250 793 761 617';
        }

        return $val;
    }

    /** Digits only for https://wa.me/250793761617 */
    public static function phoneWhatsAppDigits()
    {
        $digits = preg_replace('/\D/', '', self::phone());
        if (preg_match('/^0(7\d{8})$/', $digits, $m)) {
            return '250'.$m[1];
        }
        if (preg_match('/^7\d{8}$/', $digits)) {
            return '250'.$digits;
        }

        return $digits;
    }

    public static function phoneWhatsAppUrl($text = null)
    {
        $url = 'https://wa.me/'.self::phoneWhatsAppDigits();
        if ($text !== null && $text !== '') {
            $url .= '?text='.rawurlencode($text);
        }

        return $url;
    }

    public static function mapsLat()
    {
        return '-1.935777';
    }

    public static function mapsLng()
    {
        return '30.110374';
    }

    public static function mapsUrl()
    {
        return 'https://maps.google.com/?q='.self::mapsLat().','.self::mapsLng();
    }

    public static function mapsEmbedUrl()
    {
        return 'https://maps.google.com/maps?q='.self::mapsLat().','.self::mapsLng().'&z=16&output=embed';
    }

    public static function officeName()
    {
        $val = trim((string) SiteContent::text('contact.office_name', 'Welcome 2 Kigali Expats Club'));
        if ($val === '' || stripos($val, 'beyond') !== false || stripos($val, 'norrsken') !== false) {
            return 'Welcome 2 Kigali Expats Club';
        }

        return $val;
    }

    public static function address()
    {
        $lines = self::officeLines();

        return $lines ? implode(', ', $lines) : 'Kigali, Rwanda';
    }

    /**
     * Public office lines (never Norrsken / Beyond leftovers).
     *
     * @return array<int, string>
     */
    public static function officeLines()
    {
        $line1 = trim((string) SiteContent::text('contact.office_line1', 'Kigali'));
        $line2 = trim((string) SiteContent::text('contact.office_line2', 'Rwanda'));
        if ($line1 === '' || stripos($line1, 'norrsken') !== false || stripos($line1, 'beyond') !== false) {
            $line1 = 'Kigali';
        }
        if ($line2 === '' || stripos($line2, 'norrsken') !== false) {
            $line2 = 'Rwanda';
        }
        if (strcasecmp($line1, 'Kigali') === 0 && strcasecmp($line2, 'Rwanda') === 0) {
            return ['Kigali, Rwanda'];
        }

        return array_values(array_filter([$line1, $line2]));
    }
}
