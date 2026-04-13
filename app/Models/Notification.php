<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $table = 'notification_alerts';


    protected $fillable = [
        'device_id',
        'device_category_id',
        'sensor_id',
        'current_value',
        'min_value',
        'max_value',
        'message',
        'is_read'
    ];

    public function device()
    {
        return $this->belongsTo(Device::class,'device_id','device_id');
    }
}
