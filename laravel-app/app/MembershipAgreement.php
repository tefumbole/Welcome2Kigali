<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipAgreement extends Model
{
    protected $fillable = ['version', 'title', 'body', 'is_current', 'created_by'];

    protected $casts = ['is_current' => 'boolean'];

    public static function current()
    {
        return static::where('is_current', 1)->orderByDesc('id')->first();
    }

    public static function defaultBody()
    {
        return "Welcome 2 Kigali Expats Club Membership Terms\n\n"
            ."1. Membership is non-transferable.\n"
            ."2. If you upgrade from one membership plan to another, you forfeit the current month when upgrading from a 1-month plan to a quarterly plan, or from a quarterly plan to an annual plan.\n"
            ."3. Members receive a 10% discount on all items at Welcome 2 Kigali, including food, drinks, dress, laundry, supermarket, priority parking, and more.\n"
            ."4. Members have free access to the pool and PlayStation.\n";
    }

    /**
     * Recreate or reactivate the current EULA if the table was emptied.
     */
    public static function ensureCurrent()
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('membership_agreements')) {
            return null;
        }
        $current = static::current();
        if ($current) {
            if ((string) $current->version !== '2.0') {
                $current->version = '2.0';
                $current->title = 'Welcome 2 Kigali Expats Club Membership Agreement';
                $current->body = static::defaultBody();
                $current->save();
            }

            return $current;
        }

        $now = now();
        $row = \Illuminate\Support\Facades\DB::table('membership_agreements')->orderByDesc('id')->first();
        if ($row) {
            \Illuminate\Support\Facades\DB::table('membership_agreements')->update(['is_current' => 0]);
            \Illuminate\Support\Facades\DB::table('membership_agreements')->where('id', $row->id)->update([
                'is_current' => 1,
                'updated_at' => $now,
            ]);

            return static::current();
        }

        try {
            \Illuminate\Support\Facades\DB::table('membership_agreements')->insert([
                'version' => '2.0',
                'title' => 'Welcome 2 Kigali Expats Club Membership Agreement',
                'body' => static::defaultBody(),
                'is_current' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not restore membership agreement: '.$e->getMessage());
        }

        return static::current();
    }

    /**
     * Numbered clauses for the Alpha Bridge–style agreement viewer.
     *
     * @return array
     */
    public function presentedClauses()
    {
        $meta = [
            1 => ['title' => 'Non-transferable', 'icon' => 'user'],
            2 => ['title' => 'Plan upgrades', 'icon' => 'refresh-cw'],
            3 => ['title' => 'Member discount', 'icon' => 'percent'],
            4 => ['title' => 'Pool and PlayStation', 'icon' => 'gift'],
        ];

        $clauses = [];
        $body = trim((string) $this->body);
        if (preg_match_all('/^\s*(\d+)\.\s*(.+)$/m', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $row) {
                $n = (int) $row[1];
                $info = isset($meta[$n]) ? $meta[$n] : ['title' => 'Clause '.$n, 'icon' => 'file-text'];
                $clauses[] = [
                    'n' => (string) $n,
                    'title' => $info['title'],
                    'icon' => $info['icon'],
                    'body' => trim($row[2]),
                ];
            }
        }

        if (! $clauses) {
            $clauses[] = [
                'n' => '1',
                'title' => 'Membership terms',
                'icon' => 'file-text',
                'body' => $body,
            ];
        }

        return $clauses;
    }
}
