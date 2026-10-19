<?php

namespace App\Support;

class SiteI18n
{
    public static function t($group, $english)
    {
        if ($english === null || $english === '') {
            return '';
        }

        $key = $group.'.'.$english;
        $translated = trans($key);

        return $translated === $key ? $english : $translated;
    }

    public static function item($name)
    {
        return self::t('menu.items', $name);
    }

    public static function details($details)
    {
        $plain = html_entity_decode(strip_tags((string) $details), ENT_QUOTES, 'UTF-8');
        $plain = trim(preg_replace('/\s+/', ' ', $plain));

        return $plain === '' ? '' : self::t('menu.details', $plain);
    }

    public static function flavor($flavor)
    {
        return self::t('menu.flavors', $flavor);
    }

    public static function category($name)
    {
        return self::t('menu.categories', $name);
    }

    public static function tagline($name)
    {
        return self::t('menu.taglines', $name);
    }

    public static function bucket($title)
    {
        return self::t('menu.buckets', $title);
    }

    public static function cartName($stored)
    {
        if (strpos((string) $stored, ' — ') !== false) {
            $parts = explode(' — ', $stored, 2);

            return self::item($parts[0]).' — '.self::flavor($parts[1]);
        }

        return self::item($stored);
    }
}
