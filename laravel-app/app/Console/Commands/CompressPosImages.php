<?php

namespace App\Console\Commands;

use App\Support\PosProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompressPosImages extends Command
{
    protected $signature = 'images:compress-pos';
    protected $description = 'Shrink catalog images to 100KB and write POS thumbs so the register loads quickly';

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
                $edge = strpos($root, DIRECTORY_SEPARATOR.'product') !== false ? PosProductImage::ORIGINAL_EDGE : 640;
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
