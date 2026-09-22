<?php

namespace App\Support;

use App\BeyondUser;
use App\Customer;
use App\MembershipApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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
            if (! empty($source->id) && Schema::hasTable('customers')) {
                try {
                    $linked = Customer::where('user_id', $source->id)->first();
                    if ($linked && ! empty($linked->preferred_locale)) {
                        return self::normalize($linked->preferred_locale);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $phone = $source->phone_number ?? $source->phone ?? null;
            $email = $source->email ?? null;
            if ($phone || $email) {
                $matched = self::findByContact($phone, $email);
                if ($matched && $matched !== $source && ! empty($matched->preferred_locale)) {
                    return self::normalize($matched->preferred_locale);
                }
            }

            return self::DEFAULT;
        }

        return self::normalize($source);
    }

    /**
     * Front-page language switch: cookie + session + member records.
     */
    public static function persistChoice($locale)
    {
        $locale = self::applyCookie($locale, true);
        self::rememberOnAccounts($locale);

        return $locale;
    }

    /**
     * After member login: keep an explicit homepage choice, otherwise restore
     * the language stored on their account.
     */
    public static function syncAfterLogin($user = null, $beyond = null)
    {
        $user = $user ?: self::currentWebUser();
        $beyond = $beyond ?: self::currentBeyondUser();
        $explicit = (bool) session('language_explicit');
        $cookie = self::cookieOrSession();
        $stored = self::storedFor($user, $beyond);

        if ($explicit || ! $stored) {
            $locale = self::normalize($cookie ?: $stored ?: self::DEFAULT);
            self::rememberOnAccounts($locale, $user, $beyond);

            return self::applyCookie($locale, $explicit);
        }

        return self::applyCookie($stored, false);
    }

    public static function applyCookie($locale, $markExplicit = false)
    {
        $locale = self::normalize($locale);
        if (! headers_sent()) {
            setcookie('language', $locale, time() + (86400 * 365), '/');
        }
        $_COOKIE['language'] = $locale;
        try {
            session(['language' => $locale]);
            if ($markExplicit) {
                session(['language_explicit' => true]);
            }
        } catch (\Throwable $e) {
            // session may be unavailable in CLI
        }
        app()->setLocale($locale);
        if (class_exists(\Carbon\Carbon::class) && $locale !== 'rw') {
            \Carbon\Carbon::setLocale($locale);
        }

        return $locale;
    }

    /**
     * Run WhatsApp/email copy in the recipient's language, then restore.
     */
    public static function using($locale, callable $callback)
    {
        $locale = self::normalize($locale);
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return WhatsAppMessage::withLocale($locale, $callback);
        } finally {
            app()->setLocale($previous ?: self::DEFAULT);
        }
    }

    public static function forContact($phone = null, $email = null)
    {
        $record = self::findByContact($phone, $email);

        return $record ? self::from($record) : self::current();
    }

    public static function rememberOnAccounts($locale, $user = null, $beyond = null)
    {
        $locale = self::normalize($locale);
        $user = $user ?: self::currentWebUser();
        $beyond = $beyond ?: self::currentBeyondUser();

        $emails = [];
        $phones = [];
        $userIds = [];

        if ($user) {
            if (! empty($user->email)) {
                $emails[] = trim((string) $user->email);
            }
            if (! empty($user->phone)) {
                $phones[] = $user->phone;
            }
            if (! empty($user->id)) {
                $userIds[] = $user->id;
            }
        }

        if ($beyond) {
            if (! empty($beyond->email)) {
                $emails[] = trim((string) $beyond->email);
            }
            $beyondPhone = $beyond->phone ?? null;
            if (! $beyondPhone) {
                try {
                    $beyondPhone = optional($beyond->profile)->phone;
                } catch (\Throwable $e) {
                    $beyondPhone = null;
                }
            }
            if ($beyondPhone) {
                $phones[] = $beyondPhone;
            }
            if (SchemaColumns::has('be_users', 'preferred_locale')) {
                try {
                    $beyond->preferred_locale = $locale;
                    $beyond->save();
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        $emails = array_values(array_unique(array_filter($emails)));
        $phones = array_values(array_unique(array_filter($phones)));

        if (SchemaColumns::has('customers', 'preferred_locale') && ($userIds || $emails || $phones)) {
            try {
                $q = Customer::query()->where(function ($query) use ($userIds, $emails, $phones) {
                    if ($userIds) {
                        $query->orWhereIn('user_id', $userIds);
                    }
                    foreach ($emails as $email) {
                        $query->orWhere('email', $email);
                    }
                    foreach ($phones as $phone) {
                        $digits = self::lastDigits($phone);
                        if ($digits !== '') {
                            $query->orWhereRaw(
                                "REPLACE(REPLACE(REPLACE(COALESCE(phone_number,''), '+', ''), ' ', ''), '-', '') LIKE ?",
                                ['%'.$digits.'%']
                            );
                        }
                    }
                });
                $q->update(['preferred_locale' => $locale]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (SchemaColumns::has('membership_applications', 'preferred_locale') && ($emails || $phones)) {
            try {
                $q = MembershipApplication::query()->where(function ($query) use ($emails, $phones) {
                    foreach ($emails as $email) {
                        $query->orWhere('email', $email);
                    }
                    foreach ($phones as $phone) {
                        $digits = self::lastDigits($phone);
                        if ($digits !== '') {
                            $query->orWhereRaw(
                                "REPLACE(REPLACE(REPLACE(COALESCE(phone,''), '+', ''), ' ', ''), '-', '') LIKE ?",
                                ['%'.$digits.'%']
                            );
                        }
                    }
                });
                $q->update(['preferred_locale' => $locale]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $locale;
    }

    protected static function storedFor($user, $beyond)
    {
        if ($beyond && ! empty($beyond->preferred_locale)) {
            return self::normalize($beyond->preferred_locale);
        }

        $customer = self::findCustomerFor($user, $beyond);
        if ($customer && ! empty($customer->preferred_locale)) {
            return self::normalize($customer->preferred_locale);
        }

        if ($beyond && Schema::hasTable('membership_applications')) {
            try {
                $app = self::findApplicationFor($beyond);
                if ($app && ! empty($app->preferred_locale)) {
                    return self::normalize($app->preferred_locale);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return null;
    }

    protected static function findCustomerFor($user, $beyond)
    {
        if (! Schema::hasTable('customers')) {
            return null;
        }
        if ($user && ! empty($user->id)) {
            $byUser = Customer::where('user_id', $user->id)->first();
            if ($byUser) {
                return $byUser;
            }
        }

        $phone = null;
        $email = null;
        if ($beyond) {
            $phone = $beyond->phone ?: optional($beyond->profile)->phone;
            $email = $beyond->email ?? null;
        } elseif ($user) {
            $phone = $user->phone ?? null;
            $email = $user->email ?? null;
        }

        return self::findByContact($phone, $email);
    }

    protected static function findApplicationFor($beyond)
    {
        if (! $beyond || ! Schema::hasTable('membership_applications')) {
            return null;
        }
        if (! empty($beyond->email)) {
            $row = MembershipApplication::where('email', $beyond->email)->orderBy('id', 'desc')->first();
            if ($row) {
                return $row;
            }
        }
        $phone = $beyond->phone ?: optional($beyond->profile)->phone;
        $digits = self::lastDigits($phone);
        if ($digits === '') {
            return null;
        }

        return MembershipApplication::whereRaw(
            "REPLACE(REPLACE(REPLACE(COALESCE(phone,''), '+', ''), ' ', ''), '-', '') LIKE ?",
            ['%'.$digits.'%']
        )->orderBy('id', 'desc')->first();
    }

    public static function findByContact($phone = null, $email = null)
    {
        $email = trim((string) $email);
        $digits = self::lastDigits($phone);

        if (Schema::hasTable('customers')) {
            if ($email !== '') {
                $row = Customer::where('email', $email)->first();
                if ($row) {
                    return $row;
                }
            }
            if ($digits !== '') {
                $row = Customer::whereRaw(
                    "REPLACE(REPLACE(REPLACE(COALESCE(phone_number,''), '+', ''), ' ', ''), '-', '') LIKE ?",
                    ['%'.$digits.'%']
                )->first();
                if ($row) {
                    return $row;
                }
            }
        }

        if (Schema::hasTable('membership_applications')) {
            if ($email !== '') {
                $row = MembershipApplication::where('email', $email)->orderBy('id', 'desc')->first();
                if ($row) {
                    return $row;
                }
            }
            if ($digits !== '') {
                $row = MembershipApplication::whereRaw(
                    "REPLACE(REPLACE(REPLACE(COALESCE(phone,''), '+', ''), ' ', ''), '-', '') LIKE ?",
                    ['%'.$digits.'%']
                )->orderBy('id', 'desc')->first();
                if ($row) {
                    return $row;
                }
            }
        }

        if (Schema::hasTable('be_users') && SchemaColumns::has('be_users', 'preferred_locale')) {
            if ($email !== '') {
                $row = BeyondUser::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
                if ($row) {
                    return $row;
                }
            }
            if ($digits !== '') {
                $row = BeyondUser::whereRaw(
                    "REPLACE(REPLACE(REPLACE(COALESCE(phone,''), '+', ''), ' ', ''), '-', '') LIKE ?",
                    ['%'.$digits.'%']
                )->first();
                if ($row) {
                    return $row;
                }
            }
        }

        return null;
    }

    public static function cookieOrSession()
    {
        if (! empty($_COOKIE['language'])) {
            return self::normalize($_COOKIE['language']);
        }
        try {
            $fromSession = session('language');
            if ($fromSession) {
                return self::normalize($fromSession);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    protected static function lastDigits($phone, $n = 9)
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return $digits !== '' ? substr($digits, -$n) : '';
    }

    protected static function currentWebUser()
    {
        try {
            return Auth::guard('web')->user();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function currentBeyondUser()
    {
        try {
            return Auth::guard('beyond')->user();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
