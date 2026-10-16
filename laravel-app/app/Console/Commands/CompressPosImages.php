<?php

namespace App\Console\Commands;

use App\Support\PosProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompressPosImages extends Command
{
    protected $signature = 'images:compress-pos';
    protected $description = 'Shrink catalog images to 240px icons under 45KB and write POS thumbs';

    public function handle()
    {
        $icons = PosProductImage::seedLaundryIcons();
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'image')) {
            foreach ($icons as $code => $meta) {
                DB::table('products')
                    ->where('code', $code)
                    ->where(function ($q) {
                        $q->whereNull('image')
                            ->orWhere('image', '')
                            ->orWhere('image', 'zummXD2dvAtI.png');
                    })
                    ->update(['image' => $meta['file']]);
            }
        }

        $placeholder = public_path('images/product/zummXD2dvAtI.png');
        if (is_file($placeholder) && filesize($placeholder) > PosProductImage::MAX_BYTES && function_exists('imagecreatefrompng')) {
            $src = @imagecreatefrompng($placeholder);
            if ($src) {
                $ow = imagesx($src);
                $oh = imagesy($src);
                $edge = 160;
                $scale = ($ow > $edge || $oh > $edge) ? $edge / max($ow, $oh) : 1;
                $nw = max(1, (int) round($ow * $scale));
                $nh = max(1, (int) round($oh * $scale));
                $dst = imagecreatetruecolor($nw, $nh);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $clear = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $clear);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $ow, $oh);
                imagepng($dst, $placeholder, 9);
                imagedestroy($dst);
                imagedestroy($src);
                @chmod($placeholder, 0644);
            }
        }

        $roots = [
            public_path('images/product'),
            public_path('images/category'),
            public_path('images/brand'),
        ];
        $compressed = 0;
        $thumbs = 0;
        $renames = [];
        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }
            $files = glob($root.DIRECTORY_SEPARATOR.'*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE) ?: [];
            foreach ($files as $file) {
                if (! is_file($file)) {
                    continue;
                }
                $name = basename($file);
                if (stripos($name, 'zummXD2dvAtI') !== false) {
                    continue;
                }
                $renamed = PosProductImage::ensureJpegName($file);
                if ($renamed && $renamed !== $name) {
                    $renames[$name] = $renamed;
                    $file = $root.DIRECTORY_SEPARATOR.$renamed;
                    $oldThumb = public_path('images/product/thumbs/'.$name);
                    $newThumb = public_path('images/product/thumbs/'.$renamed);
                    if (is_file($oldThumb) && ! is_file($newThumb)) {
                        @rename($oldThumb, $newThumb);
                    }
                }
                $edge = PosProductImage::ORIGINAL_EDGE;
                if (PosProductImage::optimizeFile($file, $edge)) {
                    $compressed++;
                }
                if (strpos($root, DIRECTORY_SEPARATOR.'product') !== false && PosProductImage::writeThumb($file, basename($file))) {
                    $thumbs++;
                }
            }
        }
        foreach ($renames as $old => $new) {
            if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'image')) {
                DB::table('categories')->where('image', $old)->update(['image' => $new]);
            }
            if (Schema::hasTable('brands') && Schema::hasColumn('brands', 'image')) {
                DB::table('brands')->where('image', $old)->update(['image' => $new]);
            }
            if (Schema::hasTable('products') && Schema::hasColumn('products', 'image')) {
                $rows = DB::table('products')->where('image', 'like', '%'.$old.'%')->get(['id', 'image']);
                foreach ($rows as $row) {
                    $parts = array_map('trim', explode(',', (string) $row->image));
                    $changed = false;
                    foreach ($parts as $i => $part) {
                        if ($part === $old) {
                            $parts[$i] = $new;
                            $changed = true;
                        }
                    }
                    if ($changed) {
                        DB::table('products')->where('id', $row->id)->update(['image' => implode(',', $parts)]);
                    }
                }
            }
        }
        $this->info("Compressed {$compressed} originals and wrote {$thumbs} thumbs.");

        return 0;
    }
}
