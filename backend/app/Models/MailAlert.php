<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailAlert extends Model
{
    protected $table = 'mail_alerts';

    protected $fillable = [
        'rule',
        'priority',
        'from_address',
        'subject',
        'snippet',
        'timestamp',
        'sent_at',
    ];
    

    protected $casts = [
        'timestamp' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
