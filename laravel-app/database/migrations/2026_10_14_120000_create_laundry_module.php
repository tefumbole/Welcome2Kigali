<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateLaundryModule extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('laundry_orders')) {
            Schema::create('laundry_orders', function (Blueprint $table) {
                $table->increments('id');
                $table->string('reference_no', 40)->unique();
                $table->unsignedInteger('sale_id')->nullable()->index();
                $table->unsignedInteger('customer_id')->index();
                $table->unsignedInteger('user_id')->nullable();
                $table->string('status', 32)->default('received')->index();
                $table->text('notes')->nullable();
                $table->unsignedInteger('cleaning_duration_hours')->nullable();
                $table->dateTime('cleaning_started_at')->nullable();
                $table->dateTime('ready_at')->nullable();
                $table->string('qr_token', 64)->unique();
                $table->string('collect_token', 64)->nullable()->unique();
                $table->string('collector_name')->nullable();
                $table->string('signature_path')->nullable();
                $table->dateTime('signed_at')->nullable();
                $table->dateTime('collected_at')->nullable();
                $table->dateTime('collect_sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('laundry_order_items')) {
            Schema::create('laundry_order_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('laundry_order_id')->index();
                $table->unsignedInteger('product_id')->nullable()->index();
                $table->unsignedInteger('product_sale_id')->nullable();
                $table->string('name');
                $table->decimal('qty', 12, 2)->default(1);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        $now = now();

        $categoryId = DB::table('categories')->where('name', 'Laundry')->value('id');
        if (! $categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Laundry',
                'image' => 'zummXD2dvAtI.png',
                'parent_id' => null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $unitId = DB::table('units')->where('unit_code', 'laundry')->value('id');
        if (! $unitId) {
            $unitId = DB::table('units')->insertGetId([
                'unit_code' => 'laundry',
                'unit_name' => 'Item',
                'base_unit' => null,
                'operator' => '*',
                'operation_value' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $samples = [
            ['code' => 'LND-SUIT', 'name' => 'Suit', 'price' => 8000, 'details' => 'Suit (jacket and trousers) dry cleaning.'],
            ['code' => 'LND-SHIRT', 'name' => 'Shirt', 'price' => 2500, 'details' => 'Shirt wash and press.'],
            ['code' => 'LND-TROUSER', 'name' => 'Trouser', 'price' => 3500, 'details' => 'Trousers wash and press.'],
        ];
        foreach ($samples as $row) {
            if (DB::table('products')->where('code', $row['code'])->where('is_active', 1)->exists()) {
                continue;
            }
            $payload = [
                'name' => $row['name'],
                'code' => $row['code'],
                'type' => 'laundry',
                'barcode_symbology' => 'C128',
                'brand_id' => null,
                'category_id' => $categoryId,
                'unit_id' => $unitId,
                'purchase_unit_id' => $unitId,
                'sale_unit_id' => $unitId,
                'cost' => 0,
                'price' => $row['price'],
                'qty' => 0,
                'alert_quantity' => 0,
                'image' => 'zummXD2dvAtI.png',
                'product_details' => $row['details'],
                'is_active' => 1,
                'featured' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $columns = array_flip(Schema::getColumnListing('products'));
            DB::table('products')->insert(array_intersect_key($payload, $columns));
        }

        $permNames = [
            'laundry_module',
            'laundry.received',
            'laundry.cleaning',
            'laundry.ready',
            'laundry.collect',
        ];
        foreach ($permNames as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            foreach (Role::all() as $role) {
                $roleName = strtolower((string) $role->name);
                if ($role->id == 1 || in_array($roleName, ['admin', 'owner', 'super admin'], true)) {
                    if (! $role->hasPermissionTo($name)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('laundry_order_items');
        Schema::dropIfExists('laundry_orders');
    }
}
