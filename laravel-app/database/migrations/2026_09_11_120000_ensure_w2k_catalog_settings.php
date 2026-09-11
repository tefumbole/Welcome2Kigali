<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureW2kCatalogSettings extends Migration
{
    public function up()
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'location')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('location')->nullable();
            });
        }

        if (Schema::hasTable('product_batches') && ! Schema::hasColumn('product_batches', 'location')) {
            Schema::table('product_batches', function (Blueprint $table) {
                $table->string('location')->nullable();
            });
        }

        $now = now();
        $this->ensureBrand($now);
        $pieceId = $this->ensureUnits($now);
        $foodId = $this->ensureCategories($now);
        $this->grantCatalogPermissions();
        $this->setGeneralSettingDefaults($foodId, $pieceId);
    }

    public function down()
    {
        // Keep catalog data and the location column.
    }

    private function ensureBrand($now)
    {
        if (! Schema::hasTable('brands')) {
            return;
        }
        $exists = DB::table('brands')->where('title', 'WC2K')->first();
        if ($exists) {
            if (! $exists->is_active) {
                DB::table('brands')->where('id', $exists->id)->update(['is_active' => 1, 'updated_at' => $now]);
            }

            return;
        }
        DB::table('brands')->insert([
            'title' => 'WC2K',
            'image' => null,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureUnits($now)
    {
        if (! Schema::hasTable('units')) {
            return null;
        }

        $units = [
            ['PC', 'Piece'],
            ['CUP', 'Cup'],
            ['PR', 'Pair'],
            ['BTL', 'Bottle'],
            ['BKT', 'Bucket'],
            ['GL', 'Glass'],
            ['PLT', 'Plate'],
            ['PK', 'Pack'],
            ['PCS', 'Portion'],
        ];

        $pieceId = null;
        foreach ($units as $unit) {
            $row = DB::table('units')->where('unit_code', $unit[0])->first();
            if (! $row) {
                $id = DB::table('units')->insertGetId([
                    'unit_code' => $unit[0],
                    'unit_name' => $unit[1],
                    'base_unit' => null,
                    'operator' => '*',
                    'operation_value' => 1,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $id = $row->id;
                if (! $row->is_active) {
                    DB::table('units')->where('id', $id)->update(['is_active' => 1, 'updated_at' => $now]);
                }
            }
            if ($unit[0] === 'PC') {
                $pieceId = $id;
            }
        }

        return $pieceId;
    }

    private function ensureCategories($now)
    {
        if (! Schema::hasTable('categories')) {
            return null;
        }

        $names = [
            'Coffee & Tea',
            'Iced & Specialty',
            'Tea & Hot Beverages',
            'Fresh & Detox Juices',
            'Smoothies',
            'Food',
        ];
        $foodId = null;
        foreach ($names as $name) {
            $row = DB::table('categories')->where('name', $name)->first();
            if (! $row) {
                $id = DB::table('categories')->insertGetId([
                    'name' => $name,
                    'parent_id' => 0,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $id = $row->id;
                if (! $row->is_active) {
                    DB::table('categories')->where('id', $id)->update(['is_active' => 1, 'updated_at' => $now]);
                }
            }
            if ($name === 'Food') {
                $foodId = $id;
            }
        }

        return $foodId;
    }

    private function grantCatalogPermissions()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $names = ['general_setting', 'brand', 'unit', 'tax', 'category', 'currency'];
        $permIds = [];
        foreach ($names as $name) {
            $perm = DB::table('permissions')->where('name', $name)->first();
            if (! $perm) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $id = $perm->id;
            }
            $permIds[] = $id;
        }

        $roleIds = DB::table('roles')
            ->whereIn('id', [1, 2, 4])
            ->orWhereIn('name', ['Admin', 'Owner', 'super_admin', 'Super Admin', 'admin'])
            ->pluck('id')
            ->unique();

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permId)
                    ->exists();
                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }

        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    private function setGeneralSettingDefaults($foodId, $pieceId)
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }
        $row = DB::table('general_settings')->orderByDesc('id')->first();
        if (! $row) {
            return;
        }
        $update = ['updated_at' => now()];
        if ($foodId && Schema::hasColumn('general_settings', 'category')) {
            $update['category'] = $foodId;
        }
        if ($pieceId && Schema::hasColumn('general_settings', 'unit')) {
            $update['unit'] = $pieceId;
        }
        DB::table('general_settings')->where('id', $row->id)->update($update);
    }
}
