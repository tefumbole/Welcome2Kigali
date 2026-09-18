<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'serial', 'name', 'email', 'phone', 'subject', 'message', 'ip', 'preferred_locale',
    ];
}
