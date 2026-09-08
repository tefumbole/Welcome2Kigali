<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipApplication extends Model
{
    protected $fillable = [
        'reference', 'plan_id', 'agreement_id', 'promotion_id', 'customer_id', 'beyond_user_id',
        'full_name', 'email', 'phone', 'company_name', 'id_type', 'id_number',
        'date_of_birth', 'id_expires_on', 'nationality', 'status', 'admin_note',
        'signature_image', 'signed_at', 'signed_agreement_version', 'submitted_ip',
    ];

    protected $dates = ['signed_at', 'date_of_birth', 'id_expires_on'];

    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id');
    }

    public function agreement()
    {
        return $this->belongsTo(MembershipAgreement::class, 'agreement_id');
    }

    public function documents()
    {
        return $this->hasMany(MembershipDocument::class, 'application_id');
    }

    public function membership()
    {
        return $this->hasOne(Membership::class, 'application_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function promotion()
    {
        return $this->belongsTo(MembershipPromotion::class, 'promotion_id');
    }
}
