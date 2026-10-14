<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ConvertLaundryCategoryProducts extends Migration
{
    public function up()
    {
        $ids = DB::table('categories')
            ->whereRaw('LOWER(TRIM(name)) IN (?, ?, ?)', ['laundry', 'services', 'service'])
            ->pluck('id')
            ->all();
        if (! $ids) {
            return;
        }
        DB::table('products')
            ->whereIn('category_id', $ids)
            ->whereIn('type', ['digital', 'service'])
            ->where('is_active', 1)
            ->update([
                'type' => 'laundry',
                'featured' => 1,
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
    }
}
