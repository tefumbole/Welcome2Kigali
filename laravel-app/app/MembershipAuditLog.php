<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipAuditLog extends Model
{
    protected $fillable = ['membership_id', 'application_id', 'user_id', 'action', 'meta'];
}
