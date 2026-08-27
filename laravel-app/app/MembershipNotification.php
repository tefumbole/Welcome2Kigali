<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipNotification extends Model
{
    protected $fillable = ['membership_id', 'kind', 'channel', 'status', 'payload'];

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }
}
