<?php

namespace App;

use App\Traits\NormalizesWhatsAppPhones;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use NormalizesWhatsAppPhones;

    protected $whatsappPhoneAttributes = ['phone_number'];

    protected $fillable =[
        "customer_group_id", "user_id", "name", "company_name",
        "email", "phone_number", "tax_no", "address", "city",
        "state", "postal_code", "country", "points", "deposit", "expense", "is_active", "credit_limit", "preferred_locale"
    ];

    public function user()
    {
    	return $this->belongsTo('App\User');
    }

    public function sales()
    {
    	return $this->hasMany('App\Sale');
    }

    /**
     * Records that still point at this customer. Deleting the customer
     * while any of these exist used to 500 the admin dashboard.
     */
    public function deleteBlockers()
    {
        $reasons = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')
            && \App\Sale::where('customer_id', $this->id)->exists()) {
            $reasons[] = 'sales';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('quotations')
            && \App\Quotation::where('customer_id', $this->id)->exists()) {
            $reasons[] = 'quotations';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('registrations')) {
            $regs = \Illuminate\Support\Facades\DB::table('registrations')->where(function ($q) {
                if ($this->user_id) {
                    $q->orWhere('user_id', $this->user_id);
                }
                $email = trim((string) $this->email);
                if ($email !== '') {
                    $q->orWhere('client_email', $email);
                }
                $phone = preg_replace('/\D/', '', (string) $this->phone_number);
                if (strlen($phone) >= 7) {
                    $q->orWhere('client_phone', 'like', '%'.substr($phone, -9).'%');
                }
            });
            if (($this->user_id || trim((string) $this->email) !== '' || strlen(preg_replace('/\D/', '', (string) $this->phone_number)) >= 7)
                && $regs->exists()) {
                $reasons[] = 'registrations';
            }
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('memberships')
            && \App\Membership::where('customer_id', $this->id)->exists()) {
            $reasons[] = 'memberships';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('membership_applications')
            && \App\MembershipApplication::where('customer_id', $this->id)->exists()) {
            $reasons[] = 'membership applications';
        }

        return $reasons;
    }
}
