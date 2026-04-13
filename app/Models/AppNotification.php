<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';
    protected $appends = ['short_text'];

    protected $fillable = [
        'user_id',
        'send_to',
        'title',
        'notification',
        'link',
        'type',
        'type_id',
    ];

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->diffForHumans() : null;
    }
    public function getShortTextAttribute()
    {
        $short = mb_substr($this->notification, 0, 100);
        if (mb_strlen($this->notification) > 100) {
            $short .= "...";
        }
        return $short;
    }
}
