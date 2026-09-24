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
            ."1. Membership is personal and non-transferable.\n"
            ."2. Active members receive the published member discount and any free member benefits while membership is ACTIVE.\n"
            ."3. Fees are set by the Club and may change; you pay the fee in force at the time of registration or renewal.\n"
            ."4. Promotional memberships (including free trial periods) end on the stated expiry date unless you renew on a paid plan.\n"
            ."5. When membership expires or is suspended, member discounts and free benefits stop. You remain a customer at standard prices.\n"
            ."6. The Club may suspend membership for misuse of benefits or unpaid fees.\n"
            ."7. You agree that the Club may contact you on WhatsApp about your membership, renewals, and club notices.\n"
            ."8. Identification documents you upload are used only to verify membership.\n";
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
                'version' => '1.0',
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
            1 => ['title' => 'Personal membership', 'icon' => 'user'],
            2 => ['title' => 'Member benefits', 'icon' => 'percent'],
            3 => ['title' => 'Membership fees', 'icon' => 'dollar-sign'],
            4 => ['title' => 'Promotional memberships', 'icon' => 'clock'],
            5 => ['title' => 'Expiry and suspension', 'icon' => 'calendar'],
            6 => ['title' => 'Club rights', 'icon' => 'shield'],
            7 => ['title' => 'WhatsApp notices', 'icon' => 'message-circle'],
            8 => ['title' => 'Identification documents', 'icon' => 'file-text'],
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
