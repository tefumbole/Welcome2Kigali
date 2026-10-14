<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureDefaultCreditDebitAccounts extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }
        $now = now();
        $deptId = Schema::hasColumn('accounts', 'department_id')
            ? (DB::table('departments')->orderBy('id')->value('id') ?: null)
            : null;
        $columns = array_flip(Schema::getColumnListing('accounts'));

        $credit = DB::table('accounts')->where('account_no', '001')->first();
        if (! $credit) {
            $payload = [
                'account_no' => '001',
                'name' => 'Credit',
                'initial_balance' => 0,
                'total_balance' => 0,
                'note' => 'Default credit account',
                'is_default' => 1,
                'is_default_debit' => 0,
                'is_active' => 1,
                'department_id' => $deptId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('accounts')->insert(array_intersect_key($payload, $columns));
        } else {
            DB::table('accounts')->where('id', $credit->id)->update(array_intersect_key([
                'name' => $credit->name ?: 'Credit',
                'is_default' => 1,
                'is_active' => 1,
                'updated_at' => $now,
            ], $columns));
        }

        $debit = DB::table('accounts')->where('account_no', '002')->first();
        if (! $debit) {
            $payload = [
                'account_no' => '002',
                'name' => 'Debit',
                'initial_balance' => 0,
                'total_balance' => 0,
                'note' => 'Default debit account',
                'is_default' => 0,
                'is_default_debit' => 1,
                'is_active' => 1,
                'department_id' => $deptId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('accounts')->insert(array_intersect_key($payload, $columns));
        } else {
            DB::table('accounts')->where('id', $debit->id)->update(array_intersect_key([
                'name' => $debit->name ?: 'Debit',
                'is_default_debit' => 1,
                'is_active' => 1,
                'updated_at' => $now,
            ], $columns));
        }

        $creditId = DB::table('accounts')->where('account_no', '001')->value('id');
        $debitId = DB::table('accounts')->where('account_no', '002')->value('id');
        if ($creditId && Schema::hasColumn('accounts', 'is_default')) {
            DB::table('accounts')->where('id', '!=', $creditId)->update(['is_default' => 0]);
        }
        if ($debitId && Schema::hasColumn('accounts', 'is_default_debit')) {
            DB::table('accounts')->where('id', '!=', $debitId)->update(['is_default_debit' => 0]);
        }
    }

    public function down()
    {
    }
}
