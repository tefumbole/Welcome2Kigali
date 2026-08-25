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
        $fallback = url('public/branding/w2k-logo.png');

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
}
