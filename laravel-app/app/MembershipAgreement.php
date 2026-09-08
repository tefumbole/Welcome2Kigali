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
