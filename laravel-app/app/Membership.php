<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    const ACTIVE_STATUSES = ['ACTIVE', 'EXPIRING'];

    protected $fillable = [
        'number', 'customer_id', 'application_id', 'plan_id', 'promotion_id',
        'previous_customer_group_id', 'status', 'is_promotional',
        'starts_at', 'expires_at', 'qr_token', 'renew_token', 'confirmation_pdf',
    ];

    protected $dates = ['starts_at', 'expires_at'];

    protected $casts = ['is_promotional' => 'boolean'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id');
    }

    public function application()
    {
        return $this->belongsTo(MembershipApplication::class, 'application_id');
    }

    public function payments()
    {
        return $this->hasMany(MembershipPayment::class);
    }

    public function redemptions()
    {
        return $this->hasMany(MembershipBenefitRedemption::class);
    }

    public function documents()
    {
        return $this->hasMany(MembershipDocument::class);
    }

    public function isBenefitActive()
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }
}
