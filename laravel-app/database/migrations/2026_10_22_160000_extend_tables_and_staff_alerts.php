<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExtendTablesAndStaffAlerts extends Migration
{
    public function up()
    {
        if (Schema::hasTable('restaurant_tables')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                if (! Schema::hasColumn('restaurant_tables', 'description')) {
                    $table->string('description')->nullable()->after('name');
                }
                if (! Schema::hasColumn('restaurant_tables', 'capacity')) {
                    $table->unsignedSmallInteger('capacity')->nullable()->after('description');
                }
            });
        }

        $now = now();
        foreach (['tables-index', 'tables-add', 'tables-edit', 'tables-delete'] as $name) {
            if (! DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        try {
            $ids = Permission::whereIn('name', ['tables-index', 'tables-add', 'tables-edit', 'tables-delete'])->pluck('id');
            foreach (Role::whereIn('id', [1, 2])->get() as $role) {
                foreach ($ids as $permissionId) {
                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $role->id)
                        ->where('permission_id', $permissionId)
                        ->exists();
                    if (! $exists) {
                        DB::table('role_has_permissions')->insert([
                            'permission_id' => $permissionId,
                            'role_id' => $role->id,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        // Prefer Send to Payments first; hide Send to Laundry (auto-routed).
        if (class_exists(\App\SiteSetting::class)) {
            $preferred = [
                'pay_later', 'cash', 'momo', 'visa', 'deposit', 'credit', 'group', 'cancel', 'recent',
            ];
            $order = \App\SiteSetting::getValue('pos_payments_order', null);
            if (! is_array($order) || $order === []) {
                \App\SiteSetting::setValue('pos_payments_order', $preferred);
            } else {
                $rest = array_values(array_filter($order, function ($key) {
                    return $key !== 'pay_later' && $key !== 'laundry';
                }));
                array_unshift($rest, 'pay_later');
                \App\SiteSetting::setValue('pos_payments_order', $rest);
            }
            $labels = \App\SiteSetting::getValue('pos_payments_labels', []);
            if (! is_array($labels)) {
                $labels = [];
            }
            if (empty($labels['pay_later'])) {
                $labels['pay_later'] = 'Send to Payments';
                \App\SiteSetting::setValue('pos_payments_labels', $labels);
            }
            $hidden = \App\SiteSetting::getValue('pos_payments_hidden', null);
            if ($hidden === null) {
                \App\SiteSetting::setValue('pos_payments_hidden', ['laundry', 'je']);
            } elseif (is_array($hidden) && ! in_array('laundry', $hidden, true)) {
                $hidden[] = 'laundry';
                \App\SiteSetting::setValue('pos_payments_hidden', array_values($hidden));
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('restaurant_tables')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                if (Schema::hasColumn('restaurant_tables', 'capacity')) {
                    $table->dropColumn('capacity');
                }
                if (Schema::hasColumn('restaurant_tables', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }
}
