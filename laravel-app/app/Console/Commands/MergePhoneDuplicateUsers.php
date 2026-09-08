<?php

namespace App\Console\Commands;

use App\Customer;
use App\Support\UserWorkspaces;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergePhoneDuplicateUsers extends Command
{
    protected $signature = 'w2k:merge-phone-duplicates
        {--phone= : Last 9 digits (or full number) to merge}
        {--keep= : ERP user id to keep}
        {--deactivate= : ERP user id to deactivate}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Merge duplicate ERP users that share a phone so one person has one login';

    public function handle()
    {
        $dry = (bool) $this->option('dry-run');
        $keepId = $this->option('keep');
        $dropId = $this->option('deactivate');

        if ($keepId && $dropId) {
            return $this->mergePair((int) $keepId, (int) $dropId, $dry) ? 0 : 1;
        }

        $phone = $this->option('phone');
        $groups = $this->duplicateGroups($phone);
        if ($groups->isEmpty()) {
            $this->info('No duplicate phone groups found.');

            return 0;
        }

        foreach ($groups as $tail => $users) {
            $this->line('Phone tail '.$tail.': '.$users->map(function ($u) {
                return '#'.$u->id.' '.$u->name.' role='.$u->role_id;
            })->implode(' | '));
            $keep = UserWorkspaces::preferStaff($users);
            foreach ($users as $user) {
                if ((int) $user->id === (int) $keep->id) {
                    continue;
                }
                $this->mergePair((int) $keep->id, (int) $user->id, $dry);
            }
        }

        return 0;
    }

    protected function mergePair($keepId, $dropId, $dry)
    {
        $keep = User::find($keepId);
        $drop = User::find($dropId);
        if (! $keep || ! $drop) {
            $this->error('User not found (keep='.$keepId.' deactivate='.$dropId.').');

            return false;
        }
        if ((int) $keep->id === (int) $drop->id) {
            $this->warn('Keep and deactivate are the same user.');

            return false;
        }

        $this->info(($dry ? '[dry-run] ' : '').'Keep #'.$keep->id.' '.$keep->name.'; deactivate #'.$drop->id.' '.$drop->name);

        if ($dry) {
            $count = Customer::where('user_id', $drop->id)->count();
            $this->line('  would move '.$count.' customer row(s) to user '.$keep->id);

            return true;
        }

        DB::transaction(function () use ($keep, $drop) {
            Customer::where('user_id', $drop->id)->update(['user_id' => $keep->id]);
            $drop->is_active = 0;
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_deleted')) {
                $drop->is_deleted = 1;
            }
            $drop->save();
            UserWorkspaces::ensureCustomer($keep);
        });

        $this->info('Merged. Customer rows now belong to #'.$keep->id);

        return true;
    }

    protected function duplicateGroups($phone)
    {
        $users = User::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->where('is_deleted', 0)->orWhere('is_deleted', false)->orWhereNull('is_deleted');
            })
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        if ($phone) {
            $digits = preg_replace('/\D+/', '', $phone);
            $tail = strlen($digits) >= 8 ? substr($digits, -9) : $digits;
            $users = $users->filter(function ($user) use ($tail) {
                $d = preg_replace('/\D+/', '', (string) $user->phone);

                return $d !== '' && substr($d, -9) === $tail;
            });
        }

        return $users->groupBy(function ($user) {
            $d = preg_replace('/\D+/', '', (string) $user->phone);

            return substr($d, -9);
        })->filter(function ($group) {
            return $group->count() > 1;
        });
    }
}
