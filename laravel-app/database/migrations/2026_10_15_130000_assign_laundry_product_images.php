<?php

use App\Support\PosProductImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignLaundryProductImages extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'image')) {
            return;
        }
        $icons = PosProductImage::seedLaundryIcons();
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

    public function down()
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'image')) {
            return;
        }
        foreach (array_keys(PosProductImage::laundryIconMap()) as $code) {
            DB::table('products')->where('code', $code)->update(['image' => 'zummXD2dvAtI.png']);
        }
    }
}
