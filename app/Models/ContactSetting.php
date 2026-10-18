<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSetting extends Model
{
    protected $fillable = [
        'email',
        'phone',
        'support_hours',
        'office_address',
        'social_telegram',
        'social_twitter',
        'social_facebook',
        'social_youtube',
    ];
}
