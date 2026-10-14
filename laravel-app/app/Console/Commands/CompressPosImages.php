<?php

namespace App\Console\Commands;

use App\Support\PosProductImage;
use Illuminate\Console\Command;

class CompressPosImages extends Command
{
    protected $signature = 'images:compress-pos';
    protected $description = 'Shrink catalog images and write POS thumbs so the register loads quickly';

    public function handle()
    {
        $roots = [
            public_path('images/product'),
            public_path('images/category'),
            public_path('images/brand'),
        ];
        $compressed = 0;
        $thumbs = 0;
        $skipped = 0;
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
                    $skipped++;
                    continue;
                }
                if (PosProductImage::compressOriginal($file)) {
                    $compressed++;
                }
                if (strpos($root, DIRECTORY_SEPARATOR.'product') !== false && PosProductImage::writeThumb($file, $name)) {
                    $thumbs++;
                } else {
                    $skipped++;
                }
            }
        }
        $this->info("Compressed {$compressed} originals and wrote {$thumbs} thumbs.");

        return 0;
    }
}
