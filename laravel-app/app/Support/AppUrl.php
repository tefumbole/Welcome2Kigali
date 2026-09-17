<?php

namespace App\Support;

/**
 * Build public absolute URLs that WhatsApp and nginx can follow.
 *
 * Trailing dots on the host (welcome2kigali.net.) make WhatsApp autolink
 * https://welcome2kigali.net./path — nginx then misses server_name and
 * redirects to the homepage, dropping the path.
 */
final class AppUrl
{
    public static function root()
    {
        $raw = trim((string) config('app.url', env('APP_URL', '')));
        if ($raw === '') {
            return '';
        }
        $parts = parse_url($raw);
        if (! is_array($parts) || empty($parts['host'])) {
            return rtrim($raw, './');
        }
        $scheme = ! empty($parts['scheme']) ? $parts['scheme'] : 'https';
        $host = rtrim((string) $parts['host'], '.');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

        return $scheme.'://'.$host.$port.$path;
    }

    public static function to($path = '/')
    {
        $path = '/'.ltrim((string) $path, '/');
        $root = self::root();
        if ($root === '') {
            return url($path);
        }

        return $root.$path;
    }

    public static function absolute($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $url;
        }
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return self::to($url);
        }
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return $url;
        }
        $scheme = ! empty($parts['scheme']) ? $parts['scheme'] : 'https';
        $host = rtrim((string) $parts['host'], '.');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.'://'.$host.$port.$path.$query.$fragment;
    }
}
