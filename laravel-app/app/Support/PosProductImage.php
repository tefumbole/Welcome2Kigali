<?php

namespace App\Support;

class PosProductImage
{
    public static function url($filename)
    {
        $filename = self::firstName($filename);
        $thumb = public_path('images/product/thumbs/'.$filename);
        if (is_file($thumb)) {
            return asset('public/images/product/thumbs/'.$filename);
        }

        return asset('public/images/product/'.$filename);
    }

    public static function firstName($filename)
    {
        $filename = trim((string) $filename);
        if ($filename === '' || strtolower($filename) === 'null') {
            return 'zummXD2dvAtI.png';
        }
        if (strpos($filename, ',') !== false) {
            $filename = trim(explode(',', $filename)[0]);
        }

        return $filename !== '' ? $filename : 'zummXD2dvAtI.png';
    }

    public static function writeThumb($sourcePath, $filename = null)
    {
        if (! is_file($sourcePath)) {
            return false;
        }
        $filename = $filename ?: basename($sourcePath);
        $destDir = public_path('images/product/thumbs');
        if (! is_dir($destDir) && ! @mkdir($destDir, 0755, true) && ! is_dir($destDir)) {
            return false;
        }

        return self::resizeTo($sourcePath, $destDir.DIRECTORY_SEPARATOR.$filename, 480, 68);
    }

    public static function compressOriginal($sourcePath)
    {
        if (! is_file($sourcePath)) {
            return false;
        }
        $size = filesize($sourcePath);
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return false;
        }
        $wide = max((int) $info[0], (int) $info[1]);
        if ($size < 180000 && $wide <= 1200) {
            return false;
        }

        return self::resizeTo($sourcePath, $sourcePath, 1200, 72);
    }

    public static function resizeTo($sourcePath, $destPath, $maxEdge, $quality)
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return false;
        }
        $src = self::loadImage($sourcePath, $info[2]);
        if (! $src) {
            return false;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = 1;
        if ($w > $maxEdge || $h > $maxEdge) {
            $scale = $maxEdge / max($w, $h);
        }
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        if (! $dst) {
            imagedestroy($src);
            return false;
        }
        $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'gif', 'webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        } else {
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $ok = self::saveImage($dst, $destPath, $info[2], $quality);
        imagedestroy($src);
        imagedestroy($dst);
        if ($ok) {
            @chmod($destPath, 0644);
        }

        return $ok;
    }

    protected static function loadImage($path, $type)
    {
        if ($type === IMAGETYPE_JPEG) {
            return @imagecreatefromjpeg($path);
        }
        if ($type === IMAGETYPE_PNG) {
            return @imagecreatefrompng($path);
        }
        if ($type === IMAGETYPE_GIF) {
            return @imagecreatefromgif($path);
        }
        if (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($path);
        }

        return false;
    }

    protected static function saveImage($image, $path, $type, $quality)
    {
        $tmp = $path.'.tmp'.mt_rand(1000, 9999);
        $ok = false;
        if ($type === IMAGETYPE_PNG) {
            $ok = imagepng($image, $tmp, 8);
        } elseif ($type === IMAGETYPE_GIF) {
            $ok = imagegif($image, $tmp);
        } elseif (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagewebp')) {
            $ok = imagewebp($image, $tmp, $quality);
        } else {
            $ok = imagejpeg($image, $tmp, $quality);
        }
        if ($ok && @rename($tmp, $path)) {
            return true;
        }
        @unlink($tmp);

        return false;
    }
}
