<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipDocument extends Model
{
    protected $fillable = ['application_id', 'membership_id', 'doc_type', 'path', 'original_name'];

    public function application()
    {
        return $this->belongsTo(MembershipApplication::class, 'application_id');
    }
}
