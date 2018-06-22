<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LdapSession extends Model
{
    public $timestamps = false;

    protected $table = 'ldap_session';

    protected $fillable = [
        'user',
        'cn',
        'sid',
        'created',
        'expiry',
    ];

    protected $dates = [
        'created',
        'expiry',
    ];
}
