<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashInvoiceMembersAndDigitalMenu extends Migration
{
    public function up()
    {
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'debit_sale_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedInteger('debit_sale_id')->nullable()->index();
            });
        }

        $this->assignMembersGroup();
        $this->convertMenuCategoriesToDigital();
    }

    public function down()
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'debit_sale_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('debit_sale_id');
            });
        }
    }

    protected function assignMembersGroup()
    {
        if (! Schema::hasTable('customer_groups') || ! Schema::hasTable('memberships') || ! Schema::hasTable('customers')) {
            return;
        }

        $group = DB::table('customer_groups')->whereRaw('LOWER(TRIM(name)) = ?', ['members'])->first();
        if (! $group) {
            return;
        }

        if (Schema::hasTable('membership_settings')) {
            $now = date('Y-m-d H:i:s');
            $row = DB::table('membership_settings')->where('key', 'member_group_id')->first();
            if ($row) {
                DB::table('membership_settings')->where('key', 'member_group_id')->update([
                    'value' => (string) $group->id,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('membership_settings')->insert([
                    'key' => 'member_group_id',
                    'value' => (string) $group->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $customerIds = DB::table('memberships')
            ->whereIn('status', ['ACTIVE', 'EXPIRING'])
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
            ->unique()
            ->values()
            ->all();
        if ($customerIds) {
            DB::table('customers')->whereIn('id', $customerIds)->update([
                'customer_group_id' => $group->id,
            ]);
        }
    }

    protected function convertMenuCategoriesToDigital()
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            return;
        }

        $wanted = [
            'coffee & tea',
            'food',
            'fresh & detox juices',
            'iced & specialty',
            'laundry',
            'smoothies',
            'sweet',
            'tea & hot beverages',
        ];
        $ids = [];
        foreach (DB::table('categories')->get(['id', 'name']) as $category) {
            if (in_array($this->normalizeCategory($category->name), $wanted, true)) {
                $ids[] = (int) $category->id;
            }
        }
        if (! $ids) {
            return;
        }

        $productIds = DB::table('products')->whereIn('category_id', $ids)->pluck('id')->all();
        if (! $productIds) {
            return;
        }

        $productUpdate = [
            'type' => 'digital',
            'qty' => 0,
        ];
        if (Schema::hasColumn('products', 'requires_quantity')) {
            $productUpdate['requires_quantity'] = 0;
        }
        DB::table('products')->whereIn('id', $productIds)->update($productUpdate);
        if (Schema::hasTable('product_warehouse')) {
            DB::table('product_warehouse')->whereIn('product_id', $productIds)->update(['qty' => 0]);
        }
    }

    protected function normalizeCategory($name)
    {
        $name = html_entity_decode((string) $name, ENT_QUOTES, 'UTF-8');
        $name = strtolower(trim($name));
        $name = str_replace(['speciality', ' and '], ['specialty', ' & '], $name);
        $name = preg_replace('/\s*&\s*/', ' & ', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }
}
