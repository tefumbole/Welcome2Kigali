<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipPayment extends Model
{
    protected $fillable = [
        'membership_id', 'plan_id', 'sale_id', 'payment_id', 'amount',
        'method', 'status', 'reference', 'campay_reference', 'pawapay_deposit_id', 'stripe_session_id', 'is_renewal',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_renewal' => 'boolean',
    ];

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }

    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id');
    }
}
