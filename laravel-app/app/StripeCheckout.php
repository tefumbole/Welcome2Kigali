<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StripeCheckout extends Model
{
    protected $table = 'stripe_checkouts';

    protected $fillable = [
        'session_id', 'purpose', 'purpose_id', 'extra_ids', 'amount', 'currency',
        'paying_method', 'status', 'redirect_url', 'applied_at', 'user_id',
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
