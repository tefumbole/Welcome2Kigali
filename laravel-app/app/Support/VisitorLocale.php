<?php

namespace App\Support;

class VisitorLocale
{
    const ALLOWED = ['en', 'fr', 'rw'];
    const DEFAULT = 'en';

    public static function normalize($locale)
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, self::ALLOWED, true) ? $locale : self::DEFAULT;
    }

    public static function current()
    {
        return self::normalize(app()->getLocale());
    }

    public static function from($source)
    {
        if (is_object($source)) {
            foreach (['preferred_locale', 'locale', 'language'] as $attr) {
                $value = isset($source->{$attr}) ? $source->{$attr} : null;
                if ($value) {
                    return self::normalize($value);
                }
            }
            if (isset($source->customer) && is_object($source->customer)) {
                return self::from($source->customer);
            }
            if (method_exists($source, 'relationLoaded') && method_exists($source, 'customer')) {
                try {
                    $customer = $source->customer;
                    if ($customer) {
                        return self::from($customer);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            return self::DEFAULT;
        }

        return self::normalize($source);
    }
}
