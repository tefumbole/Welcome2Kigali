<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameServicesCategoryToLaundry extends Migration
{
    public function up()
    {
        $now = now();
        $serviceCats = DB::table('categories')
            ->whereRaw('LOWER(TRIM(name)) IN (?, ?, ?)', ['services', 'service', 'laundry'])
            ->orderBy('id')
            ->get();

        $laundryId = null;
        foreach ($serviceCats as $cat) {
            if (strcasecmp(trim($cat->name), 'Laundry') === 0) {
                $laundryId = (int) $cat->id;
                break;
            }
        }
        if (! $laundryId && $serviceCats->count()) {
            $first = $serviceCats->first();
            $laundryId = (int) $first->id;
            DB::table('categories')->where('id', $laundryId)->update([
                'name' => 'Laundry',
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }
        if (! $laundryId) {
            $payload = [
                'name' => 'Laundry',
                'image' => 'zummXD2dvAtI.png',
                'parent_id' => null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $columns = array_flip(Schema::getColumnListing('categories'));
            $laundryId = DB::table('categories')->insertGetId(array_intersect_key($payload, $columns));
        }

        foreach ($serviceCats as $cat) {
            if ((int) $cat->id === $laundryId) {
                if (strcasecmp(trim($cat->name), 'Laundry') !== 0) {
                    DB::table('categories')->where('id', $cat->id)->update([
                        'name' => 'Laundry',
                        'is_active' => 1,
                        'updated_at' => $now,
                    ]);
                }
                continue;
            }
            DB::table('products')->where('category_id', $cat->id)->update([
                'category_id' => $laundryId,
                'updated_at' => $now,
            ]);
            DB::table('categories')->where('id', $cat->id)->update([
                'is_active' => 0,
                'updated_at' => $now,
            ]);
        }

        DB::table('products')
            ->where('category_id', $laundryId)
            ->where('type', 'service')
            ->update([
                'type' => 'laundry',
                'featured' => 1,
                'updated_at' => $now,
            ]);

        DB::table('products')
            ->where('category_id', $laundryId)
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('featured')->orWhere('featured', 0);
            })
            ->update([
                'featured' => 1,
                'updated_at' => $now,
            ]);

        $unitId = DB::table('units')->where('unit_code', 'laundry')->value('id');
        if (! $unitId) {
            $serviceUnit = DB::table('units')->where('unit_code', 'service')->first();
            if ($serviceUnit) {
                $unitId = $serviceUnit->id;
            }
        }
        if ($unitId) {
            $unitUpdate = [
                'unit_id' => $unitId,
                'updated_at' => $now,
            ];
            $productCols = Schema::getColumnListing('products');
            if (in_array('sale_unit_id', $productCols, true)) {
                $unitUpdate['sale_unit_id'] = $unitId;
            }
            if (in_array('purchase_unit_id', $productCols, true)) {
                $unitUpdate['purchase_unit_id'] = $unitId;
            }
            DB::table('products')
                ->where('category_id', $laundryId)
                ->where(function ($q) {
                    $q->whereNull('unit_id')->orWhere('unit_id', 0);
                })
                ->update($unitUpdate);
        }
    }

    public function down()
    {
        // Keep Laundry as the live category name.
    }
}
