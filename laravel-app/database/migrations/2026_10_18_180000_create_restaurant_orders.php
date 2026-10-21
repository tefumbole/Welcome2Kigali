<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateRestaurantOrders extends Migration
{
    public function up()
    {
        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'department')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('department', 20)->nullable()->after('parent_id');
            });
        }

        if (! Schema::hasTable('restaurant_tables')) {
            Schema::create('restaurant_tables', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('restaurant_orders')) {
            Schema::create('restaurant_orders', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sale_id')->unique();
                $table->string('source', 20)->default('pos');
                $table->string('service_type', 20)->default('take_away');
                $table->unsignedInteger('restaurant_table_id')->nullable();
                $table->unsignedInteger('placed_by_user_id')->nullable();
                $table->unsignedInteger('waitress_user_id')->nullable();
                $table->text('customer_comment')->nullable();
                $table->text('internal_note')->nullable();
                $table->string('token', 64)->unique();
                $table->string('checkout_token', 64)->nullable()->unique();
                $table->timestamp('review_sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('restaurant_order_items')) {
            Schema::create('restaurant_order_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('restaurant_order_id');
                $table->unsignedInteger('product_sale_id')->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->string('product_name');
                $table->string('department', 20);
                $table->double('qty')->default(1);
                $table->string('status', 20)->default('awaiting');
                $table->text('customer_comment')->nullable();
                $table->text('internal_note')->nullable();
                $table->unsignedInteger('accepted_by')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->unsignedInteger('estimate_minutes')->nullable();
                $table->unsignedInteger('original_estimate_minutes')->nullable();
                $table->timestamp('expected_ready_at')->nullable();
                $table->unsignedInteger('ready_by')->nullable();
                $table->timestamp('ready_at')->nullable();
                $table->unsignedInteger('delivered_by')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->unsignedInteger('attempt_no')->default(1);
                $table->unsignedInteger('remake_of_id')->nullable();
                $table->timestamps();
                $table->index(['department', 'status']);
            });
        }

        if (! Schema::hasTable('restaurant_order_events')) {
            Schema::create('restaurant_order_events', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('restaurant_order_id');
                $table->unsignedInteger('restaurant_order_item_id')->nullable();
                $table->string('action', 40);
                $table->unsignedInteger('user_id')->nullable();
                $table->text('note')->nullable();
                $table->text('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('meal_rejections')) {
            Schema::create('meal_rejections', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('restaurant_order_id');
                $table->unsignedInteger('restaurant_order_item_id');
                $table->double('qty');
                $table->string('reason');
                $table->string('resolution', 40);
                $table->unsignedInteger('user_id')->nullable();
                $table->unsignedInteger('remake_item_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_reviews')) {
            Schema::create('order_reviews', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sale_id')->unique();
                $table->string('guest_name');
                $table->string('country');
                $table->unsignedTinyInteger('delivery_time');
                $table->unsignedTinyInteger('food_quality');
                $table->unsignedTinyInteger('environment');
                $table->unsignedTinyInteger('services');
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'user_id')) {
            try {
                DB::statement('ALTER TABLE sales MODIFY user_id INT NULL');
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasTable('categories')) {
            $this->seedDepartments();
        }
        if (Schema::hasTable('restaurant_tables') && DB::table('restaurant_tables')->count() === 0) {
            $now = date('Y-m-d H:i:s');
            for ($i = 1; $i <= 8; $i++) {
                DB::table('restaurant_tables')->insert([
                    'name' => 'Table '.$i,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        $this->seedPermissions();
    }

    public function down()
    {
        Schema::dropIfExists('order_reviews');
        Schema::dropIfExists('meal_rejections');
        Schema::dropIfExists('restaurant_order_events');
        Schema::dropIfExists('restaurant_order_items');
        Schema::dropIfExists('restaurant_orders');
        Schema::dropIfExists('restaurant_tables');
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'department')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
    }

    protected function seedDepartments()
    {
        foreach (['Food' => 'food', 'Drinks' => 'drinks'] as $name => $department) {
            $existing = DB::table('categories')->where('name', $name)->first();
            if (! $existing) {
                $this->insertCategory($name, $department);
            } elseif (empty($existing->department)) {
                DB::table('categories')->where('id', $existing->id)->update(['department' => $department]);
            }
        }

        $hasService = Schema::hasColumn('categories', 'service');
        foreach (DB::table('categories')->get() as $category) {
            if (! empty($category->department)) {
                continue;
            }
            $department = $this->guessDepartment($category->name, $hasService ? ($category->service ?? null) : null);
            if ($department) {
                DB::table('categories')->where('id', $category->id)->update(['department' => $department]);
            }
        }
    }

    protected function insertCategory($name, $department)
    {
        $now = date('Y-m-d H:i:s');
        $row = [
            'name' => $name,
            'parent_id' => null,
            'department' => $department,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $cols = array_flip(Schema::getColumnListing('categories'));
        DB::table('categories')->insert(array_intersect_key($row, $cols));
    }

    protected function guessDepartment($name, $service = null)
    {
        $hay = ' '.strtolower((string) $name).' ';
        $drinks = ['coffee', 'espresso', 'latte', 'cappuccino', 'mocha', 'tea', 'juice', 'smoothie', 'beer', 'wine', 'cocktail', 'spirit', 'whisk', 'soda', 'beverage', 'drink', 'water'];
        $food = ['food', 'meal', 'breakfast', 'lunch', 'dinner', 'starter', 'dessert', 'grill', 'pizza', 'burger', 'steak', 'chicken', 'fish', 'pastr', 'snack', 'bakery', 'salad', 'soup', 'rice', 'sandwich', 'cake'];
        foreach ($drinks as $word) {
            if (strpos($hay, $word) !== false) {
                return 'drinks';
            }
        }
        foreach ($food as $word) {
            if (strpos($hay, $word) !== false) {
                return 'food';
            }
        }
        if ($service === 'coffee') {
            return 'drinks';
        }
        if ($service === 'restaurant') {
            return 'food';
        }

        return null;
    }

    protected function seedPermissions()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $names = ['kitchen-orders', 'bar-orders', 'waiter-orders', 'order-logs', 'meal-rejections'];
        foreach (array_merge($names, ['sales-add', 'sales-index']) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $kitchen = $this->role('Kitchen', 'Kitchen queue, estimates and readiness');
        $bar = $this->role('Bartender', 'Bar queue, estimates and readiness');
        $waiter = $this->role('Waiter', 'Order placement, ready items and delivery');

        $this->grant($kitchen, ['kitchen-orders', 'meal-rejections']);
        $this->grant($bar, ['bar-orders', 'meal-rejections']);
        $this->grant($waiter, ['waiter-orders', 'meal-rejections', 'sales-index', 'sales-add']);

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $label = strtolower(trim((string) $role->name));
            if (in_array($label, ['admin', 'administrator', 'super admin', 'superadmin'], true)) {
                $this->grant($role, $names);
            }
        }

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Exception $e) {
        }
    }

    protected function role($name, $description)
    {
        $role = Role::where('name', $name)->where('guard_name', 'web')->first();
        if ($role) {
            return $role;
        }

        try {
            return Role::create([
                'name' => $name,
                'guard_name' => 'web',
                'is_active' => 1,
            ]);
        } catch (\Exception $e) {
            DB::table('roles')->insert([
                'name' => $name,
                'guard_name' => 'web',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return Role::where('name', $name)->where('guard_name', 'web')->first();
        }
    }

    protected function grant($role, array $names)
    {
        if (! $role) {
            return;
        }
        foreach ($names as $name) {
            try {
                if (! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            } catch (\Exception $e) {
            }
        }
    }
}
