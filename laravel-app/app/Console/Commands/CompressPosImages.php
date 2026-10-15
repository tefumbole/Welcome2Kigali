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
                if (PosProductImage::optimizeFile($file, strpos($root, DIRECTORY_SEPARATOR.'product') !== false ? PosProductImage::ORIGINAL_EDGE : 640)) {
                    $compressed++;
                }
                if (strpos($root, DIRECTORY_SEPARATOR.'product') !== false && PosProductImage::writeThumb($file, $name)) {
                    $thumbs++;
                }
            }
        }
        $this->info("Compressed {$compressed} originals and wrote {$thumbs} thumbs.");

        return 0;
    }
}
