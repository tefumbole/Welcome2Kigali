<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureDefaultPosWarehouseBiller extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('warehouses') || ! Schema::hasTable('billers')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $warehouseId = DB::table('warehouses')->where('is_active', 1)->orderBy('id')->value('id');
        if (! $warehouseId) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'name' => 'Main',
                'phone' => '0793761617',
                'email' => 'hello@welcome2kigali.com',
                'address' => 'Kigali',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $billerId = DB::table('billers')->where('is_active', 1)->orderBy('id')->value('id');
        if (! $billerId) {
            $row = [
                'name' => 'Welcome 2 Kigali',
                'company_name' => 'Welcome 2 Kigali Expats Club',
                'email' => 'hello@welcome2kigali.com',
                'phone_number' => '0793761617',
                'address' => 'Kigali',
                'city' => 'Kigali',
                'country' => 'Rwanda',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('billers', 'state')) {
                $row['state'] = 'Kigali';
            }
            if (Schema::hasColumn('billers', 'postal_code')) {
                $row['postal_code'] = '';
            }
            $billerId = DB::table('billers')->insertGetId($row);
        }

        $groupId = Schema::hasTable('customer_groups')
            ? DB::table('customer_groups')->where('is_active', 1)->orderBy('id')->value('id')
            : null;
        if (! $groupId && Schema::hasTable('customer_groups')) {
            $group = [
                'name' => 'General',
                'percentage' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('customer_groups', 'discount_mode')) {
                $group['discount_mode'] = 'markup';
            }
            $groupId = DB::table('customer_groups')->insertGetId($group);
        }

        $customerId = Schema::hasTable('customers')
            ? DB::table('customers')->where('is_active', 1)->orderBy('id')->value('id')
            : null;
        if (! $customerId && Schema::hasTable('customers')) {
            $customer = [
                'name' => 'Walk-in Customer',
                'phone_number' => '0793761617',
                'address' => 'Kigali',
                'city' => 'Kigali',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('customers', 'customer_group_id') && $groupId) {
                $customer['customer_group_id'] = $groupId;
            }
            $customerId = DB::table('customers')->insertGetId($customer);
        }

        if (Schema::hasTable('pos_setting') && DB::table('pos_setting')->count() === 0 && $customerId) {
            DB::table('pos_setting')->insert([
                'id' => 1,
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'biller_id' => $billerId,
                'product_number' => 8,
                'keybord_active' => 0,
                'stripe_public_key' => null,
                'stripe_secret_key' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } elseif (Schema::hasTable('pos_setting')) {
            $pos = DB::table('pos_setting')->orderBy('id')->first();
            if ($pos) {
                $update = [];
                if (empty($pos->warehouse_id)) {
                    $update['warehouse_id'] = $warehouseId;
                }
                if (empty($pos->biller_id)) {
                    $update['biller_id'] = $billerId;
                }
                if (empty($pos->customer_id) && $customerId) {
                    $update['customer_id'] = $customerId;
                }
                if ($update) {
                    DB::table('pos_setting')->where('id', $pos->id)->update($update);
                }
            }
        }

        if (Schema::hasTable('general_settings')) {
            $gs = DB::table('general_settings')->orderBy('id', 'desc')->first();
            if ($gs) {
                $update = [];
                if (Schema::hasColumn('general_settings', 'default_warehouse_id') && empty($gs->default_warehouse_id)) {
                    $update['default_warehouse_id'] = $warehouseId;
                }
                if (Schema::hasColumn('general_settings', 'default_biller_id') && empty($gs->default_biller_id)) {
                    $update['default_biller_id'] = $billerId;
                }
                if ($update) {
                    DB::table('general_settings')->where('id', $gs->id)->update($update);
                }
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'warehouse_id')) {
            $userUpdate = ['warehouse_id' => $warehouseId];
            if (Schema::hasColumn('users', 'biller_id')) {
                $userUpdate['biller_id'] = $billerId;
            }
            DB::table('users')->where(function ($q) {
                $q->whereNull('warehouse_id')->orWhere('warehouse_id', 0);
            })->update($userUpdate);
        }

        if (Schema::hasTable('product_warehouse') && Schema::hasTable('products')) {
            $existing = DB::table('product_warehouse')->where('warehouse_id', $warehouseId)->pluck('product_id')->all();
            $existing = array_map('intval', $existing);
            $products = DB::table('products')->where('is_active', 1)->get(['id', 'qty']);
            foreach ($products as $product) {
                if (in_array((int) $product->id, $existing, true)) {
                    continue;
                }
                $row = [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'qty' => $product->qty !== null ? $product->qty : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('product_warehouse', 'price')) {
                    $row['price'] = null;
                }
                DB::table('product_warehouse')->insert($row);
            }
        }
    }

    public function down()
    {
        // Keep operational defaults.
    }
}
