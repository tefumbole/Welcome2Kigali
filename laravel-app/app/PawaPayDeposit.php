<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PawaPayDeposit extends Model
{
    protected $table = 'pawapay_deposits';

    protected $fillable = [
        'deposit_id', 'purpose', 'purpose_id', 'extra_ids', 'amount', 'currency',
        'phone', 'provider', 'paying_method', 'status', 'raw_status',
        'redirect_url', 'applied_at', 'user_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'applied_at' => 'datetime',
    ];

    public function extraIds()
    {
        $raw = trim((string) $this->extra_ids);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
